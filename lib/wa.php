<?php
// lib/wa.php

// Timezone para logs e formatação coerentes
date_default_timezone_set('America/Sao_Paulo');

// Simple .env loader (só preenche se não existir no env)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $pairs = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    foreach ($pairs as $k => $v) {
        if (getenv($k) === false) {
            putenv("$k=$v");
        }
    }
}

// Utilitários úteis no projeto
function br_datetime($str) { return date('d/m/Y H:i', strtotime($str)); }
function e164($tel) {
    $d = preg_replace('/\D+/', '', $tel ?? '');
    if ($d === '') return $d;
    if (strpos($d, '55') !== 0) $d = '55' . $d;
    return $d;
}

/**
 * POST request to WhatsApp Cloud API (v23.0).
 * Pequeno retry automático em 401/5xx (1 re-tentativa).
 */
function wa_api_post(string $path, array $payload): array
{
    $phoneId = getenv('WA_PHONE_NUMBER_ID');
    $token   = getenv('WA_TOKEN');
    $url     = "https://graph.facebook.com/v23.0/{$phoneId}/" . ltrim($path, '/');

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $try = 0;
    $maxTry = 2;
    $last = ['http' => 0, 'body' => null, 'curl' => null];

    do {
        $try++;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $decoded = $response ? json_decode($response, true) : null;

        // Log por tentativa
        $logFile = $logDir . '/wa_' . date('Ymd_His') . "_t{$try}.log";
        $logData = "URL: {$url}\nRequest:\n{$json}\nHTTP Code: {$httpCode}\nResponse:\n{$response}\nError:\n{$curlErr}\n";
        @file_put_contents($logFile, $logData);

        $last = ['http' => $httpCode, 'body' => $decoded, 'curl' => $curlErr];

        // sucesso
        if ($curlErr === '' && $httpCode === 200) {
            return $decoded ?? ['status' => 200];
        }

        // Retry apenas em 401/5xx
        $shouldRetry = ($httpCode === 401 || ($httpCode >= 500 && $httpCode <= 599));
        if ($shouldRetry && $try < $maxTry) {
            usleep(500000); // 0.5s
        } else {
            break;
        }
    } while ($try < $maxTry);

    // Erro final
    if ($last['curl']) {
        return ['error' => $last['curl'], 'status' => 0];
    }
    return ['error' => $last['body'], 'status' => $last['http']];
}

/** Envia texto simples (só funciona dentro da janela de 24h). */
function wa_send_text(string $to, string $message): array
{
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => e164($to),
        'type'              => 'text',
        'text'              => ['body' => $message],
    ];
    return wa_api_post('messages', $payload);
}

/** Envia template com components customizados (avançado). */
function wa_send_template(string $to, string $templateName, string $lang, array $components = []): array
{
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => e164($to),
        'type'              => 'template',
        'template'          => [
            'name'       => $templateName,
            'language'   => ['code' => $lang],
            'components' => $components,
        ],
    ];
    return wa_api_post('messages', $payload);
}

/** Helper: envia template só com variáveis de BODY (mais simples). */
function wa_send_template_simple(string $to, string $templateName, array $vars, string $lang = 'pt_BR'): array
{
    $components = [[
        'type'       => 'body',
        'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string)$v], $vars),
    ]];
    return wa_send_template($to, $templateName, $lang, $components);
}
