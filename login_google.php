<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Caminho correto do autoload do Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    echo json_encode(['success' => false, 'message' => 'Biblioteca Google não encontrada']);
    exit;
}

$google_token = $_POST['credential'] ?? null;

if (!$google_token) {
    echo json_encode(['success' => false, 'message' => 'Token não enviado']);
    exit;
}

// Troque pelo seu CLIENT_ID do Google
define('GOOGLE_CLIENT_ID', '1051863779401-v2k79mfrrut3c0ffebc1466utn46443c.apps.googleusercontent.com');

$client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);

try {
    $payload = $client->verifyIdToken($google_token);
    if ($payload) {
        $email = $payload['email'] ?? '';
        $nome = $payload['name'] ?? '';

        // Lógica de registro/login do usuário aqui
        echo json_encode(['success' => true, 'email' => $email, 'nome' => $nome]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Token Google inválido!']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    exit;
}

