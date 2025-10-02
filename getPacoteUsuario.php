<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => true, 'mensagem' => 'Usuário não autenticado.', 'sessoes_restantes' => 0]);
    exit;
}

$user_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT id, total_sessoes, sessoes_usadas FROM pacotes WHERE usuario_id = ? AND sessoes_usadas < total_sessoes ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($pacoteId, $total, $usadas);
if ($stmt->fetch()) {
    $sessoes_restantes = $total - $usadas;
    if ($sessoes_restantes <= 0) {
        $sessoes_restantes = 0;
        $pacoteId = null;
    }
} else {
    $sessoes_restantes = 0;
    $pacoteId = null;
}
$stmt->close();

echo json_encode([
    'sessoes_restantes' => $sessoes_restantes,
    'pacote_id' => $pacoteId,
]);
?>
