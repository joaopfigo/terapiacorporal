<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    http_response_code(403); exit;
}
require_once '../conexao.php';
$data = $_POST['data_horario'] ?? '';
if ($data) {
    $stmt = $conn->prepare("INSERT INTO agendamentos (data_horario, status, duracao) VALUES (?, 'Indisponível', 60)");
    $stmt->bind_param('s', $data);
    $stmt->execute();
}
echo 'ok';
