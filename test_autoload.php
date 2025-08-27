<?php
require_once __DIR__ . '/vendor/autoload.php';

if (class_exists('Google_Client')) {
    echo 'Google_Client CARREGADO!';
} else {
    echo 'Google_Client NÃO encontrado!';
}