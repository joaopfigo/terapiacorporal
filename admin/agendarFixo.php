<?php
// admin/agendarFixo.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';

// Parâmetros recebidos do form
$visitante = isset($_POST['visitante']) && $_POST['visitante'] === '1';
$usuario_id = $visitante ? 0 : intval($_POST['usuario_id'] ?? 0); // paciente
$data_inicio = $_POST['data_inicio'] ?? '';
$horario     = $_POST['horario'] ?? '';
$dia_semana  = intval($_POST['dia_semana'] ?? 0); // 1=segunda ... 7=domingo
$duracao     = intval($_POST['duracao'] ?? 60);
$especialidade_id = intval($_POST['especialidade_id'] ?? 1); // ajuste conforme seu sistema
$repeticoes = intval($_POST['repeticoes'] ?? 0);
$adicional_reflexo = isset($_POST['adicional_reflexo_fixo']) ? 1 : 0;
$usar_pacote = !$visitante && isset($_POST['usar_pacote']) && $_POST['usar_pacote'] === '1';
$pacote_id = $usar_pacote ? intval($_POST['pacote_id'] ?? 0) : 0;

$guest_name = trim($_POST['guest_name'] ?? '');
$guest_email = trim($_POST['guest_email'] ?? '');
$guest_phone = trim($_POST['guest_phone'] ?? '');
$guest_nascimento = trim($_POST['guest_nascimento'] ?? '');
$idade_visitante = null;

if ($visitante) {
    if ($guest_name === '' || $guest_email === '' || $guest_phone === '') {
        header('Location: agenda.php?msg=erro_visitante'); exit;
    }
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        header('Location: agenda.php?msg=email_invalido'); exit;
    }
    if ($guest_nascimento !== '') {
        $dtNascimento = DateTime::createFromFormat('Y-m-d', $guest_nascimento);
        if (!$dtNascimento || $dtNascimento->format('Y-m-d') !== $guest_nascimento) {
            header('Location: agenda.php?msg=data_invalida'); exit;
        }
        $idade_visitante = $dtNascimento->diff(new DateTime('now'))->y;
    }
} elseif (!$usuario_id) {
    header('Location: agenda.php?msg=erro_param'); exit;
}

if ($usar_pacote && (!$pacote_id || !$usuario_id)) {
    header('Location: agenda.php?msg=pacote_invalido'); exit;
}

if (
    !$data_inicio ||
    !$horario ||
    !$dia_semana ||
    $dia_semana < 1 ||
    $dia_semana > 7 ||
    $repeticoes < 1
) {
    header('Location: agenda.php?msg=erro_param'); exit;
}

$inicio = DateTime::createFromFormat('Y-m-d', $data_inicio);
if (!$inicio) {
    header('Location: agenda.php?msg=erro_param'); exit;
}

$horaObj = DateTime::createFromFormat('H:i', $horario) ?: DateTime::createFromFormat('H:i:s', $horario);
if (!$horaObj) {
    header('Location: agenda.php?msg=erro_param'); exit;
}

$horaFormatada = $horaObj->format('H:i:s');

$diaAtual = (int) $inicio->format('N');
$offset = ($dia_semana - $diaAtual + 7) % 7;
if ($offset) {
    $inicio->modify('+' . $offset . ' days');
}

$datasGeradas = [];
for ($i = 0; $i < $repeticoes; $i++) {
    $ocorrencia = clone $inicio;
    if ($i > 0) {
        $ocorrencia->modify('+' . $i . ' week');
    }
    $datasGeradas[] = $ocorrencia;
}

$totalGerado = count($datasGeradas);
$agendados = 0;
$agendamentosCriados = [];

try {
    $conn->begin_transaction();

    $pacoteInfo = null;
    if ($usar_pacote) {
        $stmtPacote = $conn->prepare("SELECT id, total_sessoes, sessoes_usadas FROM pacotes WHERE id = ? AND usuario_id = ? LIMIT 1 FOR UPDATE");
        if (!$stmtPacote) {
            throw new Exception('PACOTE_INVALIDO');
        }
        $stmtPacote->bind_param('ii', $pacote_id, $usuario_id);
        $stmtPacote->execute();
        $stmtPacote->bind_result($pacoteIdDb, $totalSessoes, $sessoesUsadas);
        if (!$stmtPacote->fetch()) {
            $stmtPacote->close();
            throw new Exception('PACOTE_INVALIDO');
        }
        $stmtPacote->close();

        $restantes = (int) $totalSessoes - (int) $sessoesUsadas;
        if ($restantes < $repeticoes) {
            throw new Exception('PACOTE_INSUFICIENTE');
        }

        $pacoteInfo = [
            'id' => (int) $pacoteIdDb,
            'restantes' => $restantes,
            'total' => (int) $totalSessoes,
        ];
    }

    foreach ($datasGeradas as $dataOcorrencia) {
        $data_horario = $dataOcorrencia->format('Y-m-d') . ' ' . $horaFormatada;
        $stmt = $conn->prepare("SELECT COUNT(*) as qtd FROM agendamentos WHERE data_horario = ? AND status IN ('Confirmado','Indisponivel','Indisponível')");
        if (!$stmt) {
            throw new Exception('ERRO_VALIDACAO');
        }
        $stmt->bind_param('s', $data_horario);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!isset($r['qtd']) || $r['qtd'] != 0) {
            continue;
        }

        if ($visitante) {
            $stmt2 = $conn->prepare("INSERT INTO agendamentos (usuario_id, nome_visitante, email_visitante, telefone_visitante, idade_visitante, especialidade_id, data_horario, duracao, adicional_reflexo, status, criado_em) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmado', NOW())");
            if (!$stmt2) {
                throw new Exception('ERRO_INSERIR');
            }
            $stmt2->bind_param('sssiisii', $guest_name, $guest_email, $guest_phone, $idade_visitante, $especialidade_id, $data_horario, $duracao, $adicional_reflexo);
        } else {
            $stmt2 = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, adicional_reflexo, status, criado_em) VALUES (?, ?, ?, ?, ?, 'Confirmado', NOW())");
            if (!$stmt2) {
                throw new Exception('ERRO_INSERIR');
            }
            $stmt2->bind_param('iisii', $usuario_id, $especialidade_id, $data_horario, $duracao, $adicional_reflexo);
        }

        if (!$stmt2->execute()) {
            $stmt2->close();
            throw new Exception('ERRO_INSERIR');
        }

        $agendados++;
        $agendamentosCriados[] = $stmt2->insert_id;
        $stmt2->close();
    }

    if ($usar_pacote && $agendados > 0 && $pacoteInfo) {
        $stmtUpdate = $conn->prepare("UPDATE pacotes SET sessoes_usadas = sessoes_usadas + ? WHERE id = ?");
        if (!$stmtUpdate) {
            throw new Exception('ERRO_PACOTE');
        }
        $stmtUpdate->bind_param('ii', $agendados, $pacoteInfo['id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $stmtUso = $conn->prepare("INSERT INTO uso_pacote (pacote_id, agendamento_id) VALUES (?, ?)");
        if ($stmtUso) {
            $pacoteIdUso = $pacoteInfo['id'];
            $agendamentoIdUso = 0;
            $stmtUso->bind_param('ii', $pacoteIdUso, $agendamentoIdUso);
            foreach ($agendamentosCriados as $idAgendamento) {
                $agendamentoIdUso = $idAgendamento;
                $stmtUso->execute();
            }
            $stmtUso->close();
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $erro = $e->getMessage();
    if ($erro === 'PACOTE_INSUFICIENTE') {
        header('Location: agenda.php?msg=pacote_insuficiente');
        exit;
    }
    if ($erro === 'PACOTE_INVALIDO') {
        header('Location: agenda.php?msg=pacote_invalido');
        exit;
    }
    header('Location: agenda.php?msg=erro_agendamento');
    exit;
foreach ($datasGeradas as $dataOcorrencia) {
    $data_horario = $dataOcorrencia->format('Y-m-d') . ' ' . $horaFormatada;
    $stmt = $conn->prepare("SELECT COUNT(*) as qtd FROM agendamentos WHERE data_horario = ? AND status IN ('Confirmado','Indisponivel','Indisponível')");
    $stmt->bind_param('s', $data_horario);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!isset($r['qtd']) || $r['qtd'] != 0) {
        continue;
    }

    if ($visitante) {
        $stmt2 = $conn->prepare("INSERT INTO agendamentos (usuario_id, nome_visitante, email_visitante, telefone_visitante, idade_visitante, especialidade_id, data_horario, duracao, adicional_reflexo, status, criado_em) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmado', NOW())");
        $stmt2->bind_param('sssiisii', $guest_name, $guest_email, $guest_phone, $idade_visitante, $especialidade_id, $data_horario, $duracao, $adicional_reflexo);
    } else {
        $stmt2 = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, adicional_reflexo, status, criado_em) VALUES (?, ?, ?, ?, ?, 'Confirmado', NOW())");
        $stmt2->bind_param('iisii', $usuario_id, $especialidade_id, $data_horario, $duracao, $adicional_reflexo);
    }

    $stmt2->execute();
    $stmt2->close();
    $agendados++;
}

header('Location: agenda.php?msg=fixos&total=' . $totalGerado . '&criados=' . $agendados);
exit;
