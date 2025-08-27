<?php
include 'conexao.php';
session_start();

$agendamento_id = $_POST['agendamento_id'] ?? null;
if (!$agendamento_id) die("AGENDAMENTO_INVALIDO");

$conn->begin_transaction();
try {
    // Marca como cancelado
    $stmt = $conn->prepare("UPDATE agendamentos SET status = 'Cancelada' WHERE id = ?");
    $stmt->bind_param("i", $agendamento_id);
    $stmt->execute();
    $stmt->close();

    // Verifica se usou pacote
    $stmt = $conn->prepare("SELECT pacote_id FROM uso_pacote WHERE agendamento_id = ?");
    $stmt->bind_param("i", $agendamento_id);
    $stmt->execute();
    $stmt->bind_result($pacote_id);
    if ($stmt->fetch()) {
        $stmt->close();
        // Devolve sessão ao pacote
        $stmt = $conn->prepare("UPDATE pacotes SET sessoes_usadas = sessoes_usadas - 1 WHERE id = ?");
        $stmt->bind_param("i", $pacote_id);
        $stmt->execute();
        $stmt->close();
        // Remove registro de uso
        $stmt = $conn->prepare("DELETE FROM uso_pacote WHERE agendamento_id = ?");
        $stmt->bind_param("i", $agendamento_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
    }

    $conn->commit();
    echo "SUCESSO";
} catch (Exception $e) {
    $conn->rollback();
    die("ERRO_CANCELAR: " . $e->getMessage());
}
?>