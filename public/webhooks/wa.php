<?php
/**
 * Callback de Webhook do WhatsApp Cloud API
 * URL: https://seu-dominio.com.br/public/webhooks/wa.php
 * Verificação: usa WA_VERIFY_TOKEN (defina via .env ou SetEnv no .htaccess)
 */

require_once __DIR__ . '/../../lib/wa.php';

function g($key, $default = '') {
    // Lê a chave com ponto OU com underline (compat)
    if (isset($_GET[$key])) return $_GET[$key];
    $alt = str_replace('.', '_', $key);
    if (isset($_GET[$alt])) return $_GET[$alt];
    return $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = g('hub.mode');
    $token     = g('hub.verify_token');
    $challenge = g('hub.challenge');

    if ($mode === 'subscribe' && $token === getenv('WA_VERIFY_TOKEN')) {
        header('Content-Type: text/plain'); // importante
        http_response_code(200);
        echo $challenge;                     // sem espaços extras!
    } else {
        http_response_code(403);
        echo 'Verification failed';
    }
    exit;
}

// POST: salva o payload e responde 200 rápido
$raw = file_get_contents('php://input');

$inboxDir = __DIR__ . '/../../storage/inbox';
if (!is_dir($inboxDir)) {
    mkdir($inboxDir, 0775, true);
}
file_put_contents($inboxDir . '/wa_' . date('Ymd_His') . '.json', $raw);

// processamento opcional (não bloqueie a resposta)
$data = json_decode($raw, true);
if ($data && !empty($data['entry'])) {
    foreach ($data['entry'] as $entry) {
        foreach ($entry['changes'] as $change) {
            $value = $change['value'] ?? [];
            if (!empty($value['messages'])) {
                // TODO: tratar mensagens recebidas
            }
            if (!empty($value['statuses'])) {
                // TODO: tratar status (delivered/read)
            }
        }
    }
}

http_response_code(200);
echo 'EVENT_RECEIVED';
