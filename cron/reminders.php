<?php
// cron/reminders.php
require_once __DIR__ . '/../lib/db_bootstrap.php';
require_once __DIR__ . '/../lib/wa_hooks.php';

date_default_timezone_set('America/Sao_Paulo');

if (!wa_is_enabled()) {
    exit; // desliga via WA_ENABLED=0 no .env
}

$conn = db();                          // retorna mysqli do seu projeto
@$conn->query("SET time_zone = '-03:00'"); // garante TZ na sessão MySQL (NOW()/CURDATE())

// ATENÇÃO: use exatamente o status que seu admin grava (ex.: 'Confirmado' ou 'CONFIRMADO')
$sql = "SELECT id
        FROM agendamentos
        WHERE DATE(data_horario) = DATE(CURDATE() + INTERVAL 1 DAY)
          AND status = 'Confirmado'";

if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        notifyPatientReminder((int)$row['id']);  // usa lib/wa_hooks.php
    }
    $res->free();
} else {
    error_log('cron/reminders: query failed: ' . $conn->error);
}
