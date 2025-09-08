<?php
require_once __DIR__ . '/../conexao.php';
header('Content-Type: text/plain; charset=utf-8');

echo "Arquivo carregado: " . __FILE__ . "\n";
echo "conn definido? " . (isset($conn) ? "sim" : "não") . "\n";
echo "tipo de conn: " . (isset($conn) ? get_class($conn) : "n/a") . "\n";

if (isset($conn) && $conn instanceof mysqli) {
    $r = $conn->query("SELECT 1 AS ok");
    $row = $r->fetch_assoc();
    echo "Ping SQL: " . $row['ok'] . "\n";
} else {
    echo "ERRO: \$conn ausente ou inválido.\n";
}
