<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}
require_once '../conexao.php';

// Recebe dados em JSON
$data = json_decode(file_get_contents('php://input'), true);
$agendamento_id = intval($data['agendamento_id'] ?? 0);
$anamnese = trim($data['anamnese'] ?? '');

if ($agendamento_id > 0 && $anamnese !== '') {
    $stmt = $conn->prepare("INSERT INTO anamneses (agendamento_id, anamnese, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE anamnese = VALUES(anamnese), updated_at = NOW()");
    $stmt->bind_param('is', $agendamento_id, $anamnese);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['sucesso' => $ok]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
}
$conn->close();
