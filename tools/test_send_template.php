<?php
require_once __DIR__ . '/../lib/wa.php';

$to      = $_GET['to']      ?? '';
$name    = $_GET['name']    ?? '';
$service = $_GET['service'] ?? 'Sessão';
$date    = $_GET['date']    ?? '';

if (!$to || !$name || !$date) {
    echo "Usage: test_send_template.php?to=55DDDNUMBER&name=consulta_lembrete&service=Massagem&date=2025-09-02%2014:00";
    exit;
}

$components = [[
    'type'       => 'body',
    'parameters' => [
        ['type' => 'text', 'text' => $service],
        ['type' => 'text', 'text' => $date],
    ],
]];

header('Content-Type: application/json');
echo json_encode(wa_send_template($to, $name, 'pt_BR', $components));
