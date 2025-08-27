<?php
// admin/concluirAgendamento.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    http_response_code(403); exit;
}
require_once '../conexao.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE agendamentos SET status = 'Concluido' WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo 'OK';
    } else {
        http_response_code(500); echo 'Erro';
    }
    exit;
} else {
    http_response_code(400); echo 'Requisição inválida'; exit;
}
