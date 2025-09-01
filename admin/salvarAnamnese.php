+15
-4

<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';

$agendamento_id = intval($_POST['agendamento_id'] ?? 0);
$anamnese = trim($_POST['anamnese'] ?? '');
$usuario_id = 0;
if ($agendamento_id > 0) {
    $stmt = $conn->prepare("SELECT usuario_id FROM agendamentos WHERE id = ?");
    $stmt->bind_param("i", $agendamento_id);
    $stmt->execute();
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
    $stmt->close();
}

if ($agendamento_id > 0 && $anamnese) {
    $stmt = $conn->prepare("INSERT INTO anamneses (agendamento_id, anamnese, data_escrita) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE anamnese=?, updated_at=NOW()");
    $stmt->bind_param("iss", $agendamento_id, $anamnese, $anamnese);
    $stmt->execute();
    $stmt->close();
}

header("Location: paciente.php?id=$usuario_id");
exit;
?>
