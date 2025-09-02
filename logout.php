<?php
session_start();
session_unset();
session_destroy();
header('Location: registrar.html'); // ou outro arquivo público de login
exit;
?>
