<?php
require __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

$firestore = new FirestoreClient([
    'projectId' => 'provedores-pelotas',
]);

// Cria um documento na coleção 'usuarios' com o ID 'usuario_teste'
$document = $firestore->collection('usuarios')->document('usuario_teste');
$document->set([
    'nome' => 'João',
    'idade' => 30,
]);

echo "Documento criado com sucesso!\n";
