<?php
// Arquivo: admin/confirmarAgendamento.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE agendamentos SET status = 'Confirmado' WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        // Opcional: enviar email p/ paciente avisando
        header('Location: agendamentos.php?msg=confirmado'); exit;
    } else {
        header('Location: agendamentos.php?msg=erro'); exit;
    }
} else {
    header('Location: agendamentos.php'); exit;
}
