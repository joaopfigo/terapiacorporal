<?php
require_once __DIR__ . '/../lib/wa.php';

$to  = $_GET['to']  ?? '';
$msg = $_GET['msg'] ?? '';

if (!$to || !$msg) {
    echo "Usage: test_send_text.php?to=55DDDNUMBER&msg=Hello";
    exit;
}

header('Content-Type: application/json');
echo json_encode(wa_send_text($to, $msg));
