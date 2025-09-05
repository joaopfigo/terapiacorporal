<?php
// lib/db_bootstrap.php
function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    require_once __DIR__ . '/../conexao.php'; // deve definir $conn = new mysqli(...)
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new RuntimeException('Falha ao carregar $conn de conexao.php');
    }
    // opcional: garantir timezone no MySQL para NOW()/DATE_ADD do cron
    @$conn->query("SET time_zone = '-03:00'");
    return $conn;
}
