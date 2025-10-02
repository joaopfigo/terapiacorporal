<?php
header('Content-Type: application/json');

require_once __DIR__ . '/lib/firebase_config.php';

echo json_encode([
    'apiKey' => FIREBASE_API_KEY,
]);
