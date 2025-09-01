<?php
session_start();
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'terapeuta') {
    header('Location: /index.html');
    exit;
}
readfile('landing.html');
