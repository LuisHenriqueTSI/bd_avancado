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

// Agrupar por ano
$assinantesPorAno = [];

foreach ($docs as $doc) {
    if ($doc->exists() && isset($doc['mensuracao'], $doc['qt'])) {
        $ano = substr($doc['mensuracao'], 0, 4);
        $qt = $doc['qt'];

        if (!isset($assinantesPorAno[$ano])) {
            $assinantesPorAno[$ano] = 0;
        }
        $assinantesPorAno[$ano] += $qt;
    }
}

ksort($assinantesPorAno); // Ordenar os anos

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gráfico de Assinantes por Ano</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        #linechart {
            width: 100%;
            height: 600px;
        }
    </style>
</head>
<body>

<h2>Assinantes de Internet por Ano</h2>

<div id="linechart"></div>

<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            ['Ano', 'Assinantes'],
            <?php foreach ($assinantesPorAno as $ano => $total): ?>
            ['<?= $ano ?>', <?= $total ?>],
            <?php endforeach; ?>
        ]);

        var options = {
            title: 'Crescimento de Assinantes de Internet (Pelotas)',
            curveType: 'function',
            legend: { position: 'bottom' },
            hAxis: {
                title: 'Ano',
                format: '####'
            },
            vAxis: {
                title: 'Número de Assinantes'
            }
        };

        var chart = new google.visualization.LineChart(document.getElementById('linechart'));
        chart.draw(data, options);
    }
</script>

</body>
</html>
