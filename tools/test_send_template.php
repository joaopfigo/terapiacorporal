<?php
// tools/test_send_template.php
// Teste de envio de TEMPLATE via seu servidor (usa lib/wa.php e o .env do servidor)
require_once __DIR__ . '/../lib/wa.php';
header('Content-Type: application/json; charset=utf-8');

$to   = $_GET['to']   ?? '';
$name = $_GET['name'] ?? '';
$lang = $_GET['lang'] ?? null;

if ($to === '' || $name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Informe ?to=55DDDNUMERO&name=template_name'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Idioma default por template (pode sobrescrever com &lang=)
if ($lang === null) {
    $n = strtolower($name);
    if ($n === 'hello_world')        $lang = 'en_US';
    elseif ($n === 'consulta_recusa') $lang = 'en_US';
    else                              $lang = 'pt_BR';
}

// Monte as variáveis SOMENTE com o que vier na query string
// Suporta ?vars[]=...&vars[]=... OU atalhos ?service=...&date=...
$vars = [];
if (isset($_GET['vars'])) {
    $raw = $_GET['vars'];
    if (is_array($raw)) {
        foreach ($raw as $v) $vars[] = trim((string)$v);
    } else {
        $vars[] = trim((string)$raw);
    }
} else {
    if (isset($_GET['service'])) $vars[] = trim((string)$_GET['service']);
    if (isset($_GET['date']))    $vars[] = trim((string)$_GET['date']); // só se você mandar
}

// Envia
if (function_exists('wa_send_template_simple')) {
    $res = wa_send_template_simple($to, $name, $vars, $lang);
} else {
    // Fallback se não existir o helper
    $components = [];
    if ($vars) {
        $components[] = [
            'type' => 'body',
            'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => $v], $vars),
        ];
    }
    $res = wa_send_template($to, $name, $lang, $components);
}

echo json_encode([
    'request'  => ['to'=>$to,'name'=>$name,'lang'=>$lang,'vars'=>$vars],
    'response' => $res
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
<?php
// tools/test_send_template.php
// Teste de envio de TEMPLATE via seu servidor (usa lib/wa.php e o .env do servidor)
require_once __DIR__ . '/../lib/wa.php';
header('Content-Type: application/json; charset=utf-8');

$to   = $_GET['to']   ?? '';
$name = $_GET['name'] ?? '';
$lang = $_GET['lang'] ?? null;

if ($to === '' || $name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Informe ?to=55DDDNUMERO&name=template_name'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Idioma default por template (pode sobrescrever com &lang=)
if ($lang === null) {
    $n = strtolower($name);
    if ($n === 'hello_world')        $lang = 'en_US';
    elseif ($n === 'consulta_recusa') $lang = 'en_US';
    else                              $lang = 'pt_BR';
}

// Monte as variáveis SOMENTE com o que vier na query string
// Suporta ?vars[]=...&vars[]=... OU atalhos ?service=...&date=...
$vars = [];
if (isset($_GET['vars'])) {
    $raw = $_GET['vars'];
    if (is_array($raw)) {
        foreach ($raw as $v) $vars[] = trim((string)$v);
    } else {
        $vars[] = trim((string)$raw);
    }
} else {
    if (isset($_GET['service'])) $vars[] = trim((string)$_GET['service']);
    if (isset($_GET['date']))    $vars[] = trim((string)$_GET['date']); // só se você mandar
}

// Envia
if (function_exists('wa_send_template_simple')) {
    $res = wa_send_template_simple($to, $name, $vars, $lang);
} else {
    // Fallback se não existir o helper
    $components = [];
    if ($vars) {
        $components[] = [
            'type' => 'body',
            'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => $v], $vars),
        ];
    }
    $res = wa_send_template($to, $name, $lang, $components);
}

echo json_encode([
    'request'  => ['to'=>$to,'name'=>$name,'lang'=>$lang,'vars'=>$vars],
    'response' => $res
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
