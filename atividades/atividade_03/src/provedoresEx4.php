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

// Agrupar os dados por ano e porte
$dadosPorAno = [];

foreach ($docs as $doc) {
    if ($doc->exists() && isset($doc['mensuracao'], $doc['qt'], $doc['porte'])) {
        $ano = substr($doc['mensuracao'], 0, 4);
        $porte = $doc['porte'];
        $qt = $doc['qt'];

        if (!isset($dadosPorAno[$ano])) {
            $dadosPorAno[$ano] = ['grande' => 0, 'pequeno' => 0];
        }

        if ($porte == 2) {
            $dadosPorAno[$ano]['grande'] += $qt;
        } elseif ($porte == 3) {
            $dadosPorAno[$ano]['pequeno'] += $qt;
        }
    }
}

ksort($dadosPorAno); // Ordenar os anos
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Percentual de Clientes por Porte</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        table {
            border-collapse: collapse;
            width: 60%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px 12px;
            text-align: center;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>

<h2>Percentual de Clientes por Porte de Provedor (por Ano)</h2>

<table>
    <tr>
        <th>Ano</th>
        <th>% Grande Porte</th>
        <th>% Pequeno Porte</th>
    </tr>
    <?php foreach ($dadosPorAno as $ano => $dados):
        $total = $dados['grande'] + $dados['pequeno'];
        $percGrande = $total > 0 ? round(($dados['grande'] / $total) * 100, 2) : 0;
        $percPequeno = $total > 0 ? round(($dados['pequeno'] / $total) * 100, 2) : 0;
        ?>
        <tr>
            <td><?= htmlspecialchars($ano) ?></td>
            <td><?= $percGrande ?>%</td>
            <td><?= $percPequeno ?>%</td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
