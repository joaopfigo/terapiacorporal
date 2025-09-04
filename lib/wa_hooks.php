<?php
// lib/wa_hooks.php
require_once __DIR__ . '/wa.php';
require_once __DIR__ . '/../database/pdo.php';

function wa_is_enabled(): bool {
    $v = getenv('WA_ENABLED');
    return !($v === '0' || strtolower((string)$v) === 'false');
}

function getBookingBasic(int $bookingId): ?array {
    $pdo = db();
    $sql = "SELECT a.id, a.data_horario, a.status,
                   e.nome        AS servico,
                   u.nome        AS paciente_nome,
                   u.telefone    AS paciente_tel
            FROM agendamentos a
            JOIN especialidades e ON e.id = a.especialidade_id
            JOIN usuarios u  ON u.id = a.usuario_id
            WHERE a.id = :id
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $bookingId]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
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

    $to   = $r['paciente_tel'];
    $lang = 'pt_BR';

    if ($approved) {
        $tpl  = 'consulta_confirmacao';
        $vars = [$r['servico'], br_datetime($r['data_horario'])];
    } else {
        $tpl  = 'consulta_recusa';
        $vars = [$r['servico']];
    }
    $res = wa_send_template_simple($to, $tpl, $vars, $lang);
    return !isset($res['error']);
}

function notifyPatientReminder(int $bookingId): bool {
    if (!wa_is_enabled()) return false;
    $r = getBookingBasic($bookingId);
    if (!$r) return false;

    $to    = $r['paciente_tel'];
    $lang  = 'pt_BR';
    $tpl   = 'consulta_lembrete';
    $vars  = [$r['servico'], br_datetime($r['data_horario'])];

    $res = wa_send_template_simple($to, $tpl, $vars, $lang);
    return !isset($res['error']);
}
