<?php
// lib/db_bootstrap.php
function db(): mysqli {
    static $cached = null;
    global $conn;

    if ($cached instanceof mysqli) {
        return $cached;
    }

    if (!($conn instanceof mysqli)) {
        require_once __DIR__ . '/../conexao.php'; // deve definir $conn = new mysqli(...)
        if (!($conn instanceof mysqli) && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $conn = $GLOBALS['conn'];
        }
    }

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new RuntimeException('Falha ao carregar $conn de conexao.php');
    }

    // opcional: garantir timezone no MySQL para NOW()/DATE_ADD do cron
    @$conn->query("SET time_zone = '-03:00'");
    $cached = $conn;

    return $cached;
}
