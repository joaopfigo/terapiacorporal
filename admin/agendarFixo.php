<?php
// admin/agendarFixo.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';

// Parâmetros recebidos do form
$usuario_id = intval($_POST['usuario_id'] ?? 0); // paciente
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim    = $_POST['data_fim'] ?? '';
$horario     = $_POST['horario'] ?? '';
$dia_semana  = intval($_POST['dia_semana'] ?? 0); // 1=segunda ... 7=domingo
$duracao     = intval($_POST['duracao'] ?? 60);
$especialidade_id = intval($_POST['especialidade_id'] ?? 1); // ajuste conforme seu sistema

if (!$usuario_id || !$data_inicio || !$data_fim || !$horario || !$dia_semana) {
    header('Location: agenda.php?msg=erro_param'); exit;
}

$inicio = new DateTime($data_inicio);
$fim    = new DateTime($data_fim);
$fim->setTime(23,59,59);

$agendados = 0;
for($data=$inicio; $data <= $fim; $data->modify('+1 day')) {
    if ((int)$data->format('N') == $dia_semana) {
        // Monta datetime completo
        $data_horario = $data->format('Y-m-d') . ' ' . $horario;
        // Verifica conflito (opcional)
        $stmt = $conn->prepare("SELECT COUNT(*) as qtd FROM agendamentos WHERE data_horario = ? AND status IN ('Confirmado','Indisponível')");
        $stmt->bind_param('s', $data_horario);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r['qtd'] == 0) {
            $stmt2 = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, status, criado_em) VALUES (?, ?, ?, ?, 'Confirmado', NOW())");
            $stmt2->bind_param('iisis', $usuario_id, $especialidade_id, $data_horario, $duracao);
            $stmt2->execute();
            $agendados++;
        }
    }
}
header('Location: agenda.php?msg=fixos&total=' . $agendados);
exit;
