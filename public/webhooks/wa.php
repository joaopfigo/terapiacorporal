<?php
/**
 * Configure this URL in the Meta Developer portal:
 * https://developers.facebook.com/apps/<APP_ID>/webhooks/
 * Use https://yourdomain.com/public/webhooks/wa.php as the callback URL
 * and set the verify token to WA_VERIFY_TOKEN from your environment.
 */

require_once __DIR__ . '/../../lib/wa.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode'] ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === getenv('WA_VERIFY_TOKEN')) {
        http_response_code(200);
        echo $challenge;
    } else {
        http_response_code(403);
        echo 'Verification failed';
    }
    exit;
}

// POST: save raw payload and acknowledge
$raw = file_get_contents('php://input');

$inboxDir = __DIR__ . '/../../storage/inbox';
if (!is_dir($inboxDir)) {
    mkdir($inboxDir, 0777, true);
}
file_put_contents($inboxDir . '/wa_' . date('Ymd_His') . '.json', $raw);

$data = json_decode($raw, true);
if ($data && !empty($data['entry'])) {
    foreach ($data['entry'] as $entry) {
        foreach ($entry['changes'] as $change) {
            $value = $change['value'];
            if (!empty($value['messages'])) {
                // TODO: handle incoming messages
            }
            if (!empty($value['statuses'])) {
                // TODO: handle status updates (delivered/read)
            }
        }
    }
}

http_response_code(200);
echo 'EVENT_RECEIVED';
