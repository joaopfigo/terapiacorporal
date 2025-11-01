<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autenticado.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$agendamentoId = isset($dados['agendamento_id']) ? (int) $dados['agendamento_id'] : 0;
if ($agendamentoId <= 0) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'agendamento_id inválido.']);
    exit;
}

require_once 'conexao.php';

$usuarioId = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare(
    'UPDATE anamneses an ' .
    'INNER JOIN agendamentos ag ON ag.id = an.agendamento_id ' .
    'SET an.visualizada_em = NOW() ' .
    'WHERE an.agendamento_id = ? AND ag.usuario_id = ?'
);
$stmt->bind_param('ii', $agendamentoId, $usuarioId);

if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível marcar como visualizada.']);
    $conn->close();
    exit;
}
$stmt->close();

$stmtBusca = $conn->prepare(
    'SELECT an.visualizada_em ' .
    'FROM anamneses an ' .
    'INNER JOIN agendamentos ag ON ag.id = an.agendamento_id ' .
    'WHERE an.agendamento_id = ? AND ag.usuario_id = ?'
);
$stmtBusca->bind_param('ii', $agendamentoId, $usuarioId);
$stmtBusca->execute();
$stmtBusca->bind_result($visualizadaEm);

if ($stmtBusca->fetch()) {
    echo json_encode([
        'sucesso' => true,
        'visualizada_em' => $visualizadaEm,
    ]);
} else {
    http_response_code(404);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Registro não encontrado.']);
}

$stmtBusca->close();
$conn->close();
