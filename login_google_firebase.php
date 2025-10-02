<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$lifetime = 60 * 60 * 24 * 30; // 30 dias
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
ini_set('session.gc_maxlifetime', $lifetime);

session_start();

require_once 'conexao.php';

function respondJson($success, $message = null) {
    $payload = ['success' => $success];
    if ($message !== null) {
        $payload['message'] = $message;
    }

    echo json_encode($payload);
    exit;
}

$idToken = $_POST['idToken'] ?? null;
if (!$idToken) {
    respondJson(false, 'Token de autenticação não informado.');
}

$apiKey = 'AIzaSyDEZV1A7BxIY1CXpiZAn3RteuY0pNC9gsY';
$lookupUrl = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . urlencode($apiKey);

$requestBody = json_encode(['idToken' => $idToken]);

$ch = curl_init($lookupUrl);
if ($ch === false) {
    respondJson(false, 'Não foi possível iniciar a validação do login com Google.');
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $requestBody,
]);

$responseBody = curl_exec($ch);
if ($responseBody === false) {
    curl_close($ch);
    respondJson(false, 'Falha ao validar o login com Google. Tente novamente.');
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode($responseBody, true);
if (!is_array($decoded)) {
    respondJson(false, 'Resposta inválida da verificação do Google.');
}

if ($statusCode !== 200) {
    respondJson(false, 'Falha ao validar o login com Google. Tente novamente.');
}

if (empty($decoded['users'][0])) {
    respondJson(false, 'Usuário do Google não encontrado.');
}

$userInfo = $decoded['users'][0];
$email = $userInfo['email'] ?? null;
if (!$email) {
    respondJson(false, 'Não foi possível obter o e-mail do Google.');
}

$displayName = $userInfo['displayName'] ?? $email;
$localId = $userInfo['localId'] ?? null;

$stmt = $conn->prepare('SELECT id, nome FROM usuarios WHERE email = ?');
if (!$stmt) {
    respondJson(false, 'Erro ao preparar consulta de usuário.');
}

$stmt->bind_param('s', $email);
if (!$stmt->execute()) {
    $stmt->close();
    respondJson(false, 'Erro ao buscar usuário existente.');
}

$result = $stmt->get_result();
$existingUser = $result ? $result->fetch_assoc() : null;
$stmt->close();

$userId = null;
$userName = $displayName;

if ($existingUser) {
    $userId = (int) $existingUser['id'];
    $userName = $displayName ?: $existingUser['nome'];

    if ($displayName && $displayName !== $existingUser['nome']) {
        $updateStmt = $conn->prepare('UPDATE usuarios SET nome = ? WHERE id = ?');
        if ($updateStmt) {
            $updateStmt->bind_param('si', $displayName, $userId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
} else {
    try {
        $randomPassword = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $randomPassword = bin2hex(openssl_random_pseudo_bytes(16));
    }

    $senhaHash = password_hash($randomPassword, PASSWORD_DEFAULT);

    $insertStmt = $conn->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
    if (!$insertStmt) {
        respondJson(false, 'Erro ao preparar criação do usuário.');
    }

    $insertStmt->bind_param('sss', $displayName, $email, $senhaHash);
    if (!$insertStmt->execute()) {
        $insertStmt->close();
        respondJson(false, 'Erro ao criar usuário.');
    }

    $userId = $insertStmt->insert_id;
    $insertStmt->close();
}

if (!$userId) {
    respondJson(false, 'Não foi possível concluir o login com Google.');
}

session_regenerate_id(true);
$_SESSION['usuario_id'] = $userId;
$_SESSION['tipo'] = 'usuario';
$_SESSION['usuario_nome'] = $userName;
$_SESSION['firebase_local_id'] = $localId;

respondJson(true);
