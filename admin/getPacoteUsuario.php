<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'terapeuta') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado.'
    ]);
    exit;
}

require_once __DIR__ . '/../conexao.php';
header('Content-Type: application/json; charset=utf-8');

$usuarioId = isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : 0;
if ($usuarioId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Paciente inválido.'
    ]);
    exit;
}

$stmt = $conn->prepare('SELECT id, total_sessoes, sessoes_usadas FROM pacotes WHERE usuario_id = ? ORDER BY id DESC LIMIT 1');
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível preparar a consulta.'
    ]);
    exit;
}

$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$stmt->bind_result($pacoteId, $total, $usadas);

if ($stmt->fetch()) {
    $total = (int) $total;
    $usadas = (int) $usadas;
    $restantes = $total - $usadas;
    if ($restantes < 0) {
        $restantes = 0;
    }

    echo json_encode([
        'success' => true,
        'pacote' => [
            'id' => (int) $pacoteId,
            'total' => $total,
            'usadas' => $usadas,
            'restantes' => $restantes,
        ],
    ]);
} else {
    echo json_encode([
        'success' => true,
        'pacote' => null,
    ]);
}

$stmt->close();
