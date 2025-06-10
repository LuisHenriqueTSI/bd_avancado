<?php

require __DIR__ . '/../vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;

include "key.php";

$configParams = [
    'keyFilePath' => __DIR__ . '/../credentials/firebase_credentials.json',
    'projectId' => 'provedores-pelotas',
];

$db = new FirestoreClient($configParams);
$collecRef = $db->collection('Provedores');
$docs = $collecRef->documents();

$dadosAno = [];
$dadosPizza = [];
$datas = [];
$totalAssinantes = 0;
$tecnologias = [];
$ultimoCrescimento = 0;

foreach ($docs as $doc) {
    if (!$doc->exists() || !isset($doc['mensuracao'], $doc['qt'])) continue;

    $ano = substr($doc['mensuracao'], 0, 4);
    $tec = $doc['tecnologia'] ?? 'Desconhecida';
    $qt = (int) $doc['qt'];
    $mensuracao = $doc['mensuracao'];

    // Total por ano (gráfico de linha)
    $dadosAno[$ano] = ($dadosAno[$ano] ?? 0) + $qt;
    $totalAssinantes += $qt;

    // Coletar tecnologias
    if (!in_array($tec, $tecnologias)) {
        $tecnologias[] = $tec;
    }

    // Coletar data para saber qual é a mais recente
    $datas[] = $mensuracao;
}

// Calcular crescimento do último ano
function calcularPercentualCrescimento(array $dadosPorAno): ?float
{
    if (count($dadosPorAno) < 2) {
        return null;
    }

    $anosDisponiveis = array_keys($dadosPorAno);
    $anoAtual = end($anosDisponiveis);
    $anoAnterior = prev($anosDisponiveis);

    $valorAtual = $dadosPorAno[$anoAtual];
    $valorAnterior = $dadosPorAno[$anoAnterior];

    if ($valorAnterior == 0) {
        return null;
    }

    return (($valorAtual - $valorAnterior) / $valorAnterior) * 100;
}

rsort($datas);
$dataMaisRecente = $datas[0] ?? '';

// Gerar dados de pizza da data mais recente
foreach ($docs as $doc) {
    if ($doc['mensuracao'] === $dataMaisRecente) {
        $tec = $doc['tecnologia'] ?? 'Desconhecida';
        $qt = (int) $doc['qt'];
        $dadosPizza[$tec] = ($dadosPizza[$tec] ?? 0) + $qt;
    }
}

ksort($dadosAno);
$ultimoCrescimento = calcularPercentualCrescimento($dadosAno);

// Preparar dados para a tabela
$tabelaDados = [];
foreach ($docs as $doc) {
    if (!$doc->exists() || !isset($doc['mensuracao'], $doc['qt'])) continue;

    $ano = substr($doc['mensuracao'], 0, 4);
    $tec = $doc['tecnologia'] ?? 'Desconhecida';
    $qt = (int) $doc['qt'];

    if (isset($dadosAno[$ano]) && $dadosAno[$ano] > 0) {
        $participacao = round(($qt / $dadosAno[$ano]) * 100, 2);
        $tabelaDados[] = [
            'ano' => $ano,
            'tecnologia' => $tec,
            'qt' => $qt,
            'participacao' => $participacao
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Interativo - Assinantes em Pelotas</title>
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos mantidos iguais */
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            min-height: 100vh;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 0;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .subtitle {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .metric-card {
            grid-column: span 3;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 1200px) {
            .metric-card {
                grid-column: span 6;
            }
        }

        @media (max-width: 768px) {
            .metric-card {
                grid-column: span 12;
            }
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .metric-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .chart-card {
            grid-column: span 6;
            padding: 25px;
        }

        @media (max-width: 1200px) {
            .chart-card {
                grid-column: span 12;
            }
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
        }

        .chart-container {
            width: 100%;
            height: 400px;
        }

        .growth {
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 8px;
        }

        .growth.positive {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .growth.negative {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .filter-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
            gap: 10px;
        }

        select {
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: white;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(72, 149, 239, 0.2);
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px 0;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 100;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1><i class="fas fa-chart-line"></i> Assinantes de Internet em Pelotas</h1>
        <p class="subtitle">Análise do crescimento e distribuição por tecnologia dos provedores de internet na região</p>
    </header>

    <div class="filter-bar">
        <select id="filter-year">
            <option value="all">Todos os anos</option>
            <?php foreach(array_keys($dadosAno) as $ano): ?>
                <option value="<?= $ano ?>"><?= $ano ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filter-tech">
            <option value="all">Todas tecnologias</option>
            <?php foreach($tecnologias as $tec): ?>
                <option value="<?= $tec ?>"><?= $tec ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="dashboard-grid">
        <div class="card metric-card">
            <div class="metric-icon" style="background: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-value"><?= number_format($totalAssinantes, 0, ',', '.') ?></div>
            <div class="metric-label">Total de Assinantes</div>
        </div>

        <div class="card metric-card">
            <div class="metric-icon" style="background: var(--success);">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="metric-value"><?= count($dadosAno) ?></div>
            <div class="metric-label">Anos Analisados</div>
        </div>

        <div class="card metric-card">
            <div class="metric-icon" style="background: var(--accent);">
                <i class="fas fa-network-wired"></i>
            </div>
            <div class="metric-value"><?= count($tecnologias) ?></div>
            <div class="metric-label">Tecnologias</div>
        </div>

        <div class="card metric-card">
            <div class="metric-icon" style="background: var(--danger);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="metric-value">
                <?= $ultimoCrescimento !== null ? number_format($ultimoCrescimento, 2, ',', '.') . '%' : 'N/A' ?>
                <?php if ($ultimoCrescimento !== null): ?>
                    <span class="growth <?= $ultimoCrescimento >= 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-arrow-<?= $ultimoCrescimento >= 0 ? 'up' : 'down' ?>"></i>
                    </span>
                <?php endif; ?>
            </div>
            <div class="metric-label">Crescimento Anual</div>
        </div>

        <div class="card chart-card">
            <div class="chart-header">
                <h2 class="chart-title"><i class="fas fa-chart-line"></i> Crescimento de Assinantes por Ano</h2>
                <div>
                    <i class="fas fa-info-circle" style="color: var(--gray); cursor: help;"
                       title="Mostra a evolução do número total de assinantes ao longo dos anos"></i>
                </div>
            </div>
            <div class="chart-container" id="linechart"></div>
        </div>

        <div class="card chart-card">
            <div class="chart-header">
                <h2 class="chart-title"><i class="fas fa-chart-pie"></i> Distribuição por Tecnologia</h2>
                <div>
                    <span style="color: var(--gray); font-size: 0.9rem;"><?= $dataMaisRecente ?></span>
                    <i class="fas fa-info-circle" style="color: var(--gray); cursor: help; margin-left: 8px;"
                       title="Mostra a distribuição dos assinantes por tipo de tecnologia na última medição"></i>
                </div>
            </div>
            <div class="chart-container" id="piechart"></div>
        </div>

        <div class="card chart-card" style="grid-column: span 12;">
            <div class="chart-header">
                <h2 class="chart-title"><i class="fas fa-table"></i> Dados Detalhados por Ano</h2>
            </div>
            <div class="chart-container" id="tablechart"></div>
        </div>
    </div>

    <footer>
        <p>© <?= date('Y') ?> Dashboard de Provedores de Internet | Atualizado em <?= date('d/m/Y') ?></p>
    </footer>
</div>

<div class="tooltip" id="tooltip" style="display: none;"></div>

<script>
    google.charts.load('current', {'packages':['corechart', 'table']});
    google.charts.setOnLoadCallback(drawCharts);

    // Dados globais para filtragem
    const allData = {
        lineData: [
            ['Ano', 'Assinantes'],
            <?php foreach ($dadosAno as $ano => $total): ?>
            ['<?= $ano ?>', <?= $total ?>],
            <?php endforeach; ?>
        ],

        pieData: [
            ['Tecnologia', 'Assinantes'],
            <?php foreach ($dadosPizza as $tec => $qt): ?>
            ['<?= $tec ?>', <?= $qt ?>],
            <?php endforeach; ?>
        ],

        tableData: [
            ['Ano', 'Tecnologia', 'Assinantes', 'Participação'],
            <?php foreach ($tabelaDados as $dado): ?>
            ['<?= $dado['ano'] ?>', '<?= $dado['tecnologia'] ?>', <?= $dado['qt'] ?>, <?= $dado['participacao'] ?>],
            <?php endforeach; ?>
        ]
    };

    // Função para filtrar os dados
    function filterData(yearFilter, techFilter) {
        const filteredData = JSON.parse(JSON.stringify(allData));

        // Aplicar filtros na tabela
        if (yearFilter !== 'all' || techFilter !== 'all') {
            filteredData.tableData = [filteredData.tableData[0]]; // Mantém o cabeçalho

            for (let i = 1; i < allData.tableData.length; i++) {
                const row = allData.tableData[i];
                const matchesYear = yearFilter === 'all' || row[0] === yearFilter;
                const matchesTech = techFilter === 'all' || row[1] === techFilter;

                if (matchesYear && matchesTech) {
                    filteredData.tableData.push(row);
                }
            }
        }

        // Aplicar filtro no gráfico de linha (apenas por ano)
        if (yearFilter !== 'all') {
            filteredData.lineData = [filteredData.lineData[0]]; // Mantém o cabeçalho
            for (let i = 1; i < allData.lineData.length; i++) {
                if (allData.lineData[i][0] === yearFilter) {
                    filteredData.lineData.push(allData.lineData[i]);
                }
            }
        }

        // Aplicar filtro no gráfico de pizza (apenas por tecnologia)
        if (techFilter !== 'all') {
            filteredData.pieData = [filteredData.pieData[0]]; // Mantém o cabeçalho
            for (let i = 1; i < allData.pieData.length; i++) {
                if (allData.pieData[i][0] === techFilter) {
                    filteredData.pieData.push(allData.pieData[i]);
                }
            }
        }

        return filteredData;
    }

    function drawCharts(filteredData = allData) {
        drawLineChart(filteredData.lineData);
        drawPieChart(filteredData.pieData);
        drawTableChart(filteredData.tableData);
    }

    function drawLineChart(data) {
        var lineData = google.visualization.arrayToDataTable(data);

        var lineOptions = {
            curveType: 'function',
            legend: { position: 'none' },
            colors: ['#4361ee'],
            backgroundColor: 'transparent',
            chartArea: {
                backgroundColor: 'transparent',
                width: '85%',
                height: '75%'
            },
            hAxis: {
                title: 'Ano',
                titleTextStyle: {
                    color: '#6c757d'
                },
                textStyle: {
                    color: '#6c757d'
                }
            },
            vAxis: {
                title: 'Número de Assinantes',
                titleTextStyle: {
                    color: '#6c757d'
                },
                textStyle: {
                    color: '#6c757d'
                },
                format: 'short'
            },
            pointSize: 6,
            lineWidth: 3,
            animation: {
                duration: 1000,
                easing: 'out'
            },
            tooltip: {
                isHtml: true,
                trigger: 'selection'
            }
        };

        var lineChart = new google.visualization.LineChart(document.getElementById('linechart'));
        lineChart.draw(lineData, lineOptions);

        // Adicionar evento de hover personalizado
        google.visualization.events.addListener(lineChart, 'onmouseover', function(e) {
            const tooltip = document.getElementById('tooltip');
            const value = lineData.getValue(e.row, 1);
            tooltip.innerHTML = `<strong>${lineData.getValue(e.row, 0)}:</strong> ${value.toLocaleString()} assinantes`;
            tooltip.style.display = 'block';
            tooltip.style.left = (e.clientX + 10) + 'px';
            tooltip.style.top = (e.clientY + 10) + 'px';
        });

        google.visualization.events.addListener(lineChart, 'onmouseout', function() {
            document.getElementById('tooltip').style.display = 'none';
        });
    }

    function drawPieChart(data) {
        var pieData = google.visualization.arrayToDataTable(data);

        var pieOptions = {
            pieHole: 0.4,
            colors: ['#4361ee', '#3f37c9', '#4895ef', '#4cc9f0', '#f72585', '#7209b7'],
            backgroundColor: 'transparent',
            chartArea: {
                backgroundColor: 'transparent',
                width: '85%',
                height: '85%'
            },
            legend: {
                position: 'labeled',
                textStyle: {
                    color: '#6c757d'
                }
            },
            pieSliceText: 'value',
            tooltip: {
                text: 'percentage'
            },
            animation: {
                duration: 1000,
                easing: 'out',
                startup: true
            }
        };

        var pieChart = new google.visualization.PieChart(document.getElementById('piechart'));
        pieChart.draw(pieData, pieOptions);
    }

    function drawTableChart(data) {
        var tableData = google.visualization.arrayToDataTable(data);

        var tableOptions = {
            width: '100%',
            height: '100%',
            page: 'enable',
            pageSize: 10,
            allowHtml: true,
            cssClassNames: {
                tableRow: 'table-row',
                headerRow: 'table-header',
                oddTableRow: 'table-row-odd'
            },
            showRowNumber: false
        };

        var tableChart = new google.visualization.Table(document.getElementById('tablechart'));
        tableChart.draw(tableData, tableOptions);
    }

    function setupEventListeners() {
        document.getElementById('filter-year').addEventListener('change', function() {
            const yearFilter = this.value;
            const techFilter = document.getElementById('filter-tech').value;
            const filteredData = filterData(yearFilter, techFilter);
            drawCharts(filteredData);
        });

        document.getElementById('filter-tech').addEventListener('change', function() {
            const techFilter = this.value;
            const yearFilter = document.getElementById('filter-year').value;
            const filteredData = filterData(yearFilter, techFilter);
            drawCharts(filteredData);
        });
    }

    // Redesenhar gráficos quando a janela for redimensionada
    window.addEventListener('resize', function() {
        drawCharts();
    });

    // Configurar listeners após carregar os gráficos
    google.charts.setOnLoadCallback(function() {
        drawCharts();
        setupEventListeners();
    });
</script>
</body>
</html>