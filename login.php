<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
if ($row = $result->fetch_assoc()) {
    if (isset($row['senha_hash']) && !empty($row['senha_hash'])) {
        if (password_verify($senha, $row['senha_hash'])) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario_nome'] = $row['nome'];
            $_SESSION['tipo'] = ($row['is_admin'] == 1 ? 'terapeuta' : 'usuario');
            $redirect = ($_SESSION['tipo'] === 'terapeuta') ? '/admin/index.php' : '/perfil.html';
            header("Location: $redirect");
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Senha incorreta!']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro: senha não cadastrada no banco!']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado!']);
}
$stmt->close();
$conn->close();
?>
