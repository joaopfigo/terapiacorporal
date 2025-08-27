<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$user_id = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);
$quantidade = isset($data['quantidade']) ? (int)$data['quantidade'] : 0;

if ($quantidade !== 5 && $quantidade !== 10) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Escolha um pacote válido.']);
    exit;
}

// Usa a conexão mysqli ($conn)
$stmt = $conn->prepare("INSERT INTO pacotes (usuario_id, total_sessoes, sessoes_usadas) VALUES (?, ?, 0)");
$stmt->bind_param("ii", $user_id, $quantidade);
if ($stmt->execute()) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar pacote.']);
}
$stmt->close();
$conn->close();
?>

