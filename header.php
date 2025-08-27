<?php
session_start();
$isAdmin = (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'terapeuta');
?>
<div class="header-admin">
  <div><?= $isAdmin ? "Painel Adm." : "Perfil" ?></div>
  <div class="menu-horizontal">
    <!-- coloque seus botões aqui -->
  </div>
</div>