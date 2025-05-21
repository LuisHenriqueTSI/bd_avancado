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

foreach ($docs as $doc) {
    if (!$doc->exists() || !isset($doc['mensuracao'], $doc['qt'])) continue;

    $ano = substr($doc['mensuracao'], 0, 4);
    $tec = $doc['tecnologia'] ?? 'Desconhecida';
    $qt = (int) $doc['qt'];
    $mensuracao = $doc['mensuracao'];

    // Total por ano (gráfico de linha)
    $dadosAno[$ano] = ($dadosAno[$ano] ?? 0) + $qt;

    // Coletar data para saber qual é a mais recente
    $datas[] = $mensuracao;
}

rsort($datas);
$dataMaisRecente = $datas[0];

// Gerar dados de pizza da data mais recente
foreach ($docs as $doc) {
    if ($doc['mensuracao'] === $dataMaisRecente) {
        $tec = $doc['tecnologia'] ?? 'Desconhecida';
        $qt = (int) $doc['qt'];
        $dadosPizza[$tec] = ($dadosPizza[$tec] ?? 0) + $qt;
    }
}

ksort($dadosAno);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Assinantes em Pelotas</title>
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            padding: 20px;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .chart-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 30px auto;
            max-width: 900px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        #linechart, #piechart {
            width: 100%;
            height: 400px;
        }
    </style>
</head>
<body>

<h1>📊 Dashboard - Assinantes de Internet em Pelotas</h1>

<div class="chart-box">
    <h2>📈 Crescimento Total por Ano</h2>
    <div id="linechart"></div>
</div>

<div class="chart-box">
    <h2>🥧 Distribuição por Tecnologia (<?= $dataMaisRecente ?>)</h2>
    <div id="piechart"></div>
</div>

<script>
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        // Gráfico de linha
        var lineData = google.visualization.arrayToDataTable([
            ['Ano', 'Assinantes'],
            <?php foreach ($dadosAno as $ano => $total): ?>
            ['<?= $ano ?>', <?= $total ?>],
            <?php endforeach; ?>
        ]);
        var lineOptions = {
            curveType: 'function',
            legend: { position: 'bottom' },
            colors: ['#2c3e50']
        };
        new google.visualization.LineChart(document.getElementById('linechart')).draw(lineData, lineOptions);

        // Gráfico de pizza
        var pieData = google.visualization.arrayToDataTable([
            ['Tecnologia', 'Assinantes'],
            <?php foreach ($dadosPizza as $tec => $qt): ?>
            ['<?= $tec ?>', <?= $qt ?>],
            <?php endforeach; ?>
        ]);
        var pieOptions = {
            is3D: true,
            colors: ['#3498db', '#2ecc71', '#e67e22', '#9b59b6', '#e74c3c']
        };
        new google.visualization.PieChart(document.getElementById('piechart')).draw(pieData, pieOptions);
    }
</script>

</body>
</html>
