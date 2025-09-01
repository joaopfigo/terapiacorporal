<?php
session_start();
require_once '../conexao.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'terapeuta') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$paciente_id = isset($data['paciente_id']) ? (int)$data['paciente_id'] : 0;
$quantidade = isset($data['quantidade']) ? (int)$data['quantidade'] : 0;

if ($paciente_id <= 0 || ($quantidade !== 5 && $quantidade !== 10)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO pacotes (usuario_id, total_sessoes, sessoes_usadas) VALUES (?, ?, 0)");
$stmt->bind_param('ii', $paciente_id, $quantidade);
if ($stmt->execute()) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar pacote.']);
}
$stmt->close();
$conn->close();
