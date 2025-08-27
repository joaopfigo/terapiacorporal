<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    http_response_code(403); exit;
}
require_once '../conexao.php';

$eventos = [];
$sql = "SELECT a.id, a.data_horario, a.status, a.usuario_id, u.nome as paciente
        FROM agendamentos a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.status IN ('Confirmado','Concluido','Indisponível')";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $eventos[] = [
        'id' => $row['id'],
        'title' => $row['paciente'] ?? 'Indisponível',
        'start' => $row['data_horario'],
        'status' => $row['status'],
        'paciente_id' => $row['usuario_id']
    ];
}
header('Content-Type: application/json');
echo json_encode($eventos);
