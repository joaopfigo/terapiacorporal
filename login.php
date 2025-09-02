<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

if (empty($_POST['email']) || empty($_POST['senha'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Preencha email e senha.']);
    exit;
}

$email = $_POST['email'];
$senha = $_POST['senha'];

$stmt = $conn->prepare("SELECT id, nome, senha_hash, is_admin FROM usuarios WHERE email = ?");
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro no prepare: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $email);

if (!$stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao executar: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['tipo'] = $usuario['is_admin'] ? 'terapeuta' : 'usuario';
$_SESSION['usuario_nome'] = $usuario['nome'];

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'tipo'    => $_SESSION['tipo']
]);
exit;
?>
