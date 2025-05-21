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

$hashes = [];
$duplicadosRemovidos = 0;

foreach ($docs as $doc) {
    if (!$doc->exists()) continue;

    // Criar um hash único com base nos campos relevantes
    $dados = [
        'empresa' => $doc['empresa'] ?? '',
        'mensuracao' => $doc['mensuracao'] ?? '',
        'tecnologia' => $doc['tecnologia'] ?? '',
        'tproduto' => $doc['tproduto'] ?? '',
        'velocidade' => $doc['velocidade'] ?? '',
        'qt' => $doc['qt'] ?? '',
        'porte' => $doc['porte'] ?? '',
    ];

    $chave = md5(json_encode($dados));

    if (isset($hashes[$chave])) {
        // Registro duplicado, deletar
        $doc->reference()->delete();
        $duplicadosRemovidos++;
    } else {
        $hashes[$chave] = true;
    }
}

echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Limpeza de Duplicatas</title></head><body>";
echo "<h2>Registros duplicados removidos: $duplicadosRemovidos</h2>";
echo "</body></html>";