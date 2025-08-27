<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';

$usuario_id = intval($_POST['usuario_id'] ?? 0);
$anamnese = trim($_POST['anamnese'] ?? '');

if ($usuario_id > 0 && $anamnese) {
    $stmt = $conn->prepare("INSERT INTO anamneses (usuario_id, anamnese, criado_em) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $usuario_id, $anamnese);
    $stmt->execute();
}

header("Location: paciente.php?id=$usuario_id");
exit;
