<?php
require_once __DIR__ . '/../lib/wa_hooks.php';

date_default_timezone_set('America/Sao_Paulo');

if (!wa_is_enabled()) {
    return;
}

$pdo = db();
$sql = "SELECT id FROM agendamentos WHERE DATE(data_horario) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND status = 'Confirmado'";
$st  = $pdo->query($sql);
foreach ($st as $row) {
    notifyPatientReminder((int)$row['id']);
}
