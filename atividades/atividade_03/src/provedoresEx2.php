<?php

require __DIR__ . '/../vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;

$configParams = [
    'keyFilePath' => __DIR__ . '/../credentials/firebase_credentials.json',
    'projectId' => 'provedores-pelotas',
];

$db = new FirestoreClient($configParams);
$collecRef = $db->collection('Provedores');

// ---------- FUNÇÃO DE CORREÇÃO DOS DADOS DE 2010 -----------
function corrigirErro2010($collecRef) {
    $docs = $collecRef->documents();

    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $data = $doc['mensuracao'];
            $qt = $doc['qt'] ?? 0;

            // Verifica se é do ano de 2010 e qt > 20
            if (strpos($data, '2010') === 0 && $qt > 20) {
                $new_qt = $qt + 1;
                echo "Corrigindo {$doc['empresa']} ({$data}): {$qt} → {$new_qt}<br>";
                $doc->reference()->update([
                    ['path' => 'qt', 'value' => $new_qt]
                ]);
            }
        }
    }
}

// Chama a função para corrigir
corrigirErro2010($collecRef);

// ---------- VISUALIZAÇÃO DE RESULTADOS (opcional) -----------
// Pega data mais recente para exibir
$docs = $collecRef->orderBy('mensuracao', 'DESC')->limit(1)->documents();
$datamaxima = $docs->rows()[0]['mensuracao'];

$data_prov = $collecRef
    ->where('mensuracao', '=', $datamaxima)
    ->orderBy('empresa')
    ->documents();

// Agrupa por empresa somando qt
$empresas = [];

foreach ($data_prov as $doc) {
    if ($doc->exists()) {
        $empresa = $doc['empresa'] ?? 'Desconhecida';
        $qt = $doc['qt'] ?? 0;
        if (!isset($empresas[$empresa])) {
            $empresas[$empresa] = 0;
        }
        $empresas[$empresa] += $qt;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Correção 2010</title>
    <style>
        table { border-collapse: collapse; width: 50%; margin: 20px auto; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #3498db; color: white; }
        body { font-family: Arial, sans-serif; }
        h2 { text-align: center; }
    </style>
</head>
<body>

<h2>Provedores - Quantidade de Clientes (<?= htmlspecialchars($datamaxima) ?>)</h2>

<table>
    <tr>
        <th>Empresa</th>
        <th>Quantidade</th>
    </tr>
    <?php foreach ($empresas as $empresa => $quantidade): ?>
        <tr>
            <td><?= htmlspecialchars($empresa) ?></td>
            <td><?= htmlspecialchars($quantidade) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
