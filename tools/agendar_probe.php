<?php
require_once __DIR__ . '/../conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('America/Sao_Paulo');

function post($url, $data) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
  ]);
  $out = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);
  return [$out, $err];
}

// Ajuste estes 3 valores para algo real do seu BD/UI:
$servico_id = 2;                // ex.: "Massoterapia" (confirme no phpMyAdmin)
$data       = date('Y-m-d', strtotime('+1 day'));
$hora       = '14:00';
$duracao    = '50';             // 15/30/50/90 — escolha uma com preço > 0
$payload = [
  'servico_id' => $servico_id,
  'data'       => $data,
  'hora'       => $hora,
  'duracao'    => $duracao,
  // 'add_reflexo' => 1,        // opcional
];

list($out, $err) = post('https://terapiacorporalsistemica.com.br/agendar.php', $payload);
header('Content-Type: text/plain; charset=utf-8');
echo "RESP:\n$out\n\nCURL_ERR:\n$err\n";
