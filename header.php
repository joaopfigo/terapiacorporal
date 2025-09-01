<?php
session_start();
$isAdmin = isset($_SESSION['usuario_id']) && ($_SESSION['tipo'] ?? '') === 'terapeuta';
$isUser  = isset($_SESSION['usuario_id']) && !$isAdmin;
?>
<div class="header-admin">
    <div id="li-perfil-login">
        <?php if ($isAdmin): ?>
            <a href="/admin/index.php">Painel Adm.</a>
        <?php elseif ($isUser): ?>
            <a href="/perfil.html">Perfil</a>
        <?php else: ?>
            <a href="/registrar.html">Login</a>
        <?php endif; ?>
    </div>
    <div class="menu-horizontal"><!-- botões --></div>
</div>
