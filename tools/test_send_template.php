<?php
// tools/test_send_template.php
// Testa envio de TEMPLATE via seu servidor (usa lib/wa.php e o .env do servidor)

require_once __DIR__ . '/../lib/wa.php';
header('Content-Type: application/json; charset=utf-8');

// Exemplo de uso:
// /tools/test_send_template.php?to=5531999654279&name=hello_world
// /tools/test_send_template.php?to=5531999654279&name=consulta_lembrete&service=Acupuntura&date=05/09/2025%2014:00

$to   = $_GET['to']   ?? '';
$name = $_GET['name'] ?? 'hello_world';

if (!$to) {
    http_response_code(400);
    echo json_encode(['error' => 'Informe ?to=55DDDNUMERO (E.164)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Idioma: hello_world é en_US; os seus templates serão pt_BR
$lang = ($name === 'hello_world') ? 'en_US' : 'pt_BR';

// Monte variáveis conforme o template
$vars = [];
if ($name === 'consulta_recusa') {
    $service = $_GET['service'] ?? 'Acupuntura';
    $vars = [$service]; // {{1}}
} elseif ($name === 'consulta_confirmacao' || $name === 'consulta_lembrete') {
    $service = $_GET['service'] ?? 'Acupuntura';
    $date    = $_GET['date']    ?? date('d/m/Y H:i', time() + 3600);
    $vars = [$service, $date]; // {{1}}, {{2}}
}
// hello_world NÃO recebe variáveis → $vars = []

// Envia: helper usa BODY com variáveis (se houver)
$res = wa_send_template_simple($to, $name, $vars, $lang);

// Retorno
echo json_encode([
    'request'  => ['to' => $to, 'name' => $name, 'lang' => $lang, 'vars' => $vars],
    'response' => $res
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
