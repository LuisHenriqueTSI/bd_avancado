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

// Obter todos os documentos
$docs = $collecRef->documents();

// Inicializar dados
$dados = [];
$tecnologias = [];

foreach ($docs as $doc) {
    if ($doc->exists() && isset($doc['mensuracao'], $doc['qt'], $doc['tecnologia'])) {
        $ano = substr($doc['mensuracao'], 0, 4);
        $tec = $doc['tecnologia'];
        $qt = $doc['qt'];

        // Guardar tecnologia
        $tecnologias[$tec] = true;

        // Inicializar ano
        if (!isset($dados[$ano])) {
            $dados[$ano] = [];
        }

        // Inicializar tecnologia para o ano
        if (!isset($dados[$ano][$tec])) {
            $dados[$ano][$tec] = 0;
        }

        // Somar
        $dados[$ano][$tec] += $qt;
    }
}

ksort($dados);
ksort($tecnologias); // Ordem alfabética das tecnologias

// Preparar dados para gráfico
$tecnologiasList = array_keys($tecnologias);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gráfico por Tecnologia</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        #areachart {
            width: 100%;
            height: 600px;
        }
    </style>
</head>
<body>

<h2>Assinantes por Tecnologia (Área Acumulada)</h2>

<div id="areachart"></div>

<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            ['Ano', <?= '"' . implode('","', $tecnologiasList) . '"' ?>],
            <?php foreach ($dados as $ano => $valores): ?>
            ['<?= $ano ?>',
                <?php foreach ($tecnologiasList as $tec): ?>
                <?= $valores[$tec] ?? 0 ?>,
                <?php endforeach; ?>
            ],
            <?php endforeach; ?>
        ]);

        var options = {
            title: 'Assinantes por Tecnologia ao longo dos Anos',
            isStacked: true,
            hAxis: {
                title: 'Ano',
                format: '####'
            },
            vAxis: {
                title: 'Número de Assinantes'
            },
            areaOpacity: 0.7,
            legend: { position: 'bottom' }
        };

        var chart = new google.visualization.AreaChart(document.getElementById('areachart'));
        chart.draw(data, options);
    }
</script>

</body>
</html>