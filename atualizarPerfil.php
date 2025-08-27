<?php
include 'conexao.php';
session_start();
header('Content-Type: application/json');


if (!isset($_SESSION['usuario_id'])) exit("NAO_LOGADO");


$usuario_id = $_SESSION['usuario_id'];
$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$nascimento = $_POST['nascimento']; // (YYYY-MM-DD)
$sexo = $_POST['sexo'] ?? null;
$novaSenha = $_POST['nova_senha'] ?? null;

// Verifica duplicação de email (se o usuário alterou o email para um já existente de outro)
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ?");
$stmt->bind_param("si", $email, $usuario_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "EMAIL_EM_USO";
    exit;
}
$stmt->close();

// Atualiza o usuário, com ou sem senha
if ($novaSenha) {
    $hash = password_hash($novaSenha, PASSWORD_BCRYPT);
    $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?, nascimento=?, sexo=?, senha_hash=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $nome, $email, $telefone, $nascimento, $sexo, $hash, $usuario_id);
} else {
    $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?, nascimento=?, sexo=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $nome, $email, $telefone, $nascimento, $sexo, $usuario_id);
}

if ($stmt->execute()) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'ERRO']);
}
$stmt->close();
?>
