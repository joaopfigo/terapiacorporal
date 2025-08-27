<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
    exit;
}

if (!isset($_FILES['foto'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Nenhuma foto enviada']);
    exit;
}

require_once 'conexao.php';

$id = $_SESSION['usuario_id'];
$foto = $_FILES['foto'];

$ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
$nome_arquivo = 'uploads/perfil_' . $id . '_' . time() . '.' . $ext;

if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

if (move_uploaded_file($foto['tmp_name'], $nome_arquivo)) {
    // Atualiza o caminho no banco
    $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
    $stmt->bind_param("si", $nome_arquivo, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => true, 'foto' => $nome_arquivo]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar foto']);
}
?>

