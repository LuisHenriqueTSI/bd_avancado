<?php

require __DIR__ . '/../vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;

$configParams = [
    'keyFilePath' => __DIR__ . '/../credentials/firebase_credentials.json',
    'projectId' => 'provedores-pelotas',
];

$db = new FirestoreClient($configParams);
$collecRef = $db->collection('Provedores');

// 1. Buscar todas as datas únicas
$allDocs = $collecRef->documents();
$datas = [];

foreach ($allDocs as $doc) {
    if ($doc->exists() && isset($doc['mensuracao'])) {
        $datas[] = $doc['mensuracao'];
    }
}
$datas_unicas = array_unique($datas);
rsort($datas_unicas); // mais recentes primeiro

// 2. Determinar data selecionada
$data_escolhida = $_GET['data'] ?? null;
if (!$data_escolhida || !in_array($data_escolhida, $datas_unicas)) {
    $data_escolhida = $datas_unicas[0] ?? null; // usar mais recente
}

$data_prov = [];
if ($data_escolhida) {
    $data_prov = $collecRef
        ->where('mensuracao', '=', $data_escolhida)
        ->orderBy('empresa')
        ->documents();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Provedores de Internet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        form {
            margin-bottom: 20px;
            text-align: center;
        }
        select {
            padding: 5px 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        h1 {
            text-align: center;
        }
    </style>
</head>
<body>

<h1>Provedores de Internet</h1>

<!-- 3. Formulário de seleção de data -->
<form method="get">
    <label for="data">Selecione a data:</label>
    <select name="data" id="data" onchange="this.form.submit()">
        <?php foreach ($datas_unicas as $data): ?>
            <option value="<?= htmlspecialchars($data) ?>" <?= $data === $data_escolhida ? 'selected' : '' ?>>
                <?= htmlspecialchars($data) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<h2 style="text-align:center;">Dados de <?= htmlspecialchars($data_escolhida) ?></h2>

<?php if (!empty($data_prov)): ?>
    <table>
        <tr>
            <th>Empresa</th>
            <th>Quantidade</th>
            <th>Tecnologia</th>
            <th>Tipo de Produto</th>
            <th>Velocidade (Mbps)</th>
        </tr>
        <?php foreach ($data_prov as $reg_prov): ?>
            <?php if ($reg_prov->exists()): ?>
                <tr>
                    <td><?= htmlspecialchars($reg_prov['empresa'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($reg_prov['qt'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($reg_prov['tecnologia'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($reg_prov['tproduto'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($reg_prov['velocidade'] ?? 'N/A') ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="text-align: center;">Nenhum dado encontrado para a data selecionada.</p>
<?php endif; ?>

</body>
</html>
