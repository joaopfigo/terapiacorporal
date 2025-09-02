<?php
// Simple .env loader (only runs if variables aren’t already in the environment)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $pairs = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    foreach ($pairs as $k => $v) {
        if (getenv($k) === false) {
            putenv("$k=$v");
        }
    }
}

/**
 * POST request to WhatsApp Cloud API.
 */
function wa_api_post(string $path, array $payload): array
{
    $phoneId = getenv('WA_PHONE_NUMBER_ID');
    $token   = getenv('WA_TOKEN');
    $url     = "https://graph.facebook.com/v20.0/{$phoneId}/" . ltrim($path, '/');

    $json = json_encode($payload);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        ],
        CURLOPT_POSTFIELDS     => $json,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $decoded = $response ? json_decode($response, true) : null;

    // Log request/response
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/wa_' . date('Ymd_His') . '.log';
    $logData = "URL: {$url}\nRequest:\n{$json}\nHTTP Code: {$httpCode}\nResponse:\n{$response}\nError:\n{$curlErr}\n";
    file_put_contents($logFile, $logData);

    if ($curlErr) {
        return ['error' => $curlErr, 'status' => 0];
    }
    if ($httpCode !== 200) {
        return ['error' => $decoded, 'status' => $httpCode];
    }
    return $decoded;
}

/** Send a text message. */
function wa_send_text(string $to, string $message): array
{
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'text',
        'text'              => ['body' => $message],
    ];
    return wa_api_post('messages', $payload);
}

/** Send a template message. */
function wa_send_template(string $to, string $templateName, string $lang, array $components = []): array
{
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'template',
        'template'          => [
            'name'       => $templateName,
            'language'   => ['code' => $lang],
            'components' => $components,
        ],
    ];
    return wa_api_post('messages', $payload);
}
