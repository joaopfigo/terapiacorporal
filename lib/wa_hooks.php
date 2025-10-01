<?php
// lib/wa_hooks.php
require_once __DIR__ . '/wa.php';
require_once __DIR__ . '/db_bootstrap.php';
require_once __DIR__ . '/booking_helpers.php';

function wa_is_enabled(): bool {
    $v = getenv('WA_ENABLED');
    return !($v === '0' || strtolower((string)$v) === 'false');
}

function getBookingBasic(int $bookingId): ?array {
    $conn = db();
    $sql = "SELECT a.id, a.data_horario, a.status,
                   a.especialidade_id, a.servicos_csv,
                   e.nome AS servico,
                   COALESCE(u.nome, a.nome_visitante) AS paciente_nome,
                   COALESCE(u.telefone, a.telefone_visitante) AS paciente_tel
            FROM agendamentos a
            JOIN especialidades e ON e.id = a.especialidade_id
            LEFT JOIN usuarios u  ON u.id = a.usuario_id
            WHERE a.id = ?
            LIMIT 1";
    $st = $conn->prepare($sql);
    $st->bind_param('i', $bookingId);
    $st->execute();
    $res = $st->get_result();
    $r   = $res ? $res->fetch_assoc() : null;
    $st->close();
    if ($r) {
        [$tituloServicos, $listaServicos] = descreverServicos(
            $conn,
            (int) $r['especialidade_id'],
            $r['servicos_csv'],
            $r['servico']
        );
        $r['servico'] = $tituloServicos;
        $r['servicos_lista'] = $listaServicos;
    }

    return $r ?: null;
}

function notifyTherapistNewBooking(int $bookingId): bool {
    if (!wa_is_enabled()) return false;
    $r = getBookingBasic($bookingId);
    if (!$r) return false;
    $therapist = getenv('WA_DEFAULT_THERAPIST_NUMBER');
    if (!$therapist) return false;

    // Abre janela (sandbox)
    wa_send_template_simple($therapist, 'hello_world', [], 'en_US');

    $msg = "Novo agendamento:\n".
           "Serviço: {$r['servico']}\n".
           "Quando: ".br_datetime($r['data_horario'])."\n".
           "Paciente: {$r['paciente_nome']}\n".
           "Telefone: ".e164($r['paciente_tel']);
    $res = wa_send_text($therapist, $msg);
    return !isset($res['error']);
}

function notifyPatientBookingStatus(int $bookingId, bool $approved): bool {
    if (!wa_is_enabled()) return false;
    $r = getBookingBasic($bookingId);
    if (!$r) return false;

    $to = $r['paciente_tel'];

    if ($approved) {
        // Template com APENAS {{1}} = serviço (pt_BR)
        $tpl  = 'consulta_confirmacao';
        $lang = 'pt_BR';
        $vars = [$r['servico']];
    } else {
        // Template com APENAS {{1}} = serviço (en_US)
        $tpl  = 'consulta_recusa';
        $lang = 'en';
        $vars = [$r['servico']];
    }

    $res = wa_send_template_simple($to, $tpl, $vars, $lang);
    return !isset($res['error']);
}

function notifyPatientReminder(int $bookingId): bool {
    if (!wa_is_enabled()) return false;
    $r = getBookingBasic($bookingId);
    if (!$r) return false;

    $to   = $r['paciente_tel'];
    $lang = 'pt_BR';
    $tpl  = 'consulta_lembrete';

    // Template com APENAS {{1}} = serviço
    $vars = [$r['servico']];

    $res = wa_send_template_simple($to, $tpl, $vars, $lang);
    return !isset($res['error']);
}
