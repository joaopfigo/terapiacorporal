<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'terapeuta') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<!-- deploy-test: 2025-08-27 -->
<html lang="pt-br">
<head>
      <link rel="icon" type="image/png" href="/favicon-transparente.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel Administrativo</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
  :root {
    --main-bg: #f6f9fa;
    --primary: #1d9a77;
    --primary-dark: #175f4c;
    --accent: #ffe7bc;
    --danger: #e25b5b;
    --text: #232f2e;
    --card-bg: #fff;
    --card-radius: 22px;
    --input-radius: 13px;
    --shadow: 0 3px 18px #0002;
    --shadow-lg: 0 8px 32px #0003;
  }
  * { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; padding: 0; background: var(--main-bg); font-family: Roboto, Arial, sans-serif; color: var(--text); }
  body { min-height: 100vh; }
  .admin-app {
    max-width: 450px;
    margin: 0 auto;
    padding-bottom: 40px;
    min-height: 100vh;
    display: flex; flex-direction: column; justify-content: flex-start;
  }
.header {
  background: var(--primary);
  color: #fff;
  text-align: center;
  padding: 36px 0 18px 0;
  font-family: 'Playfair Display', serif;
  font-size: 2.15rem;
  letter-spacing: 1px;
  border-radius: 0;          /* Retangular, sem curva */
  box-shadow: 0 3px 18px #1d9a7712;
  margin-bottom: 32px;
  font-weight: 700;
  border-bottom: 1.5px solid #e4f0ec; /* Linha clara e sofisticada */
  position: static;
}
  .welcome {
    font-size: 1.15rem;
    font-family: Roboto,sans-serif;
    color: #204e46;
    text-align: center;
    margin-bottom: 26px;
    font-weight: 700;
    letter-spacing: 0.6px;
  }
  .menu-vertical {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin: 0 0 0 0;
    align-items: stretch;
    width: 100%;
  }
  .menu-btn {
    border: none;
    background: var(--card-bg);
    border-radius: 18px;
    padding: 21px 0;
    font-weight: 700;
    font-size: 1.17rem;
    color: var(--primary-dark);
    box-shadow: var(--shadow);
    transition: background 0.16s, color 0.16s, box-shadow 0.18s;
    cursor: pointer;
    outline: none;
    margin: 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    letter-spacing: 0.7px;
    border: 2.5px solid transparent;
  }
  .menu-btn:hover, .menu-btn:focus {
    background: var(--accent);
    color: var(--primary);
    box-shadow: 0 3px 18px #e2e2cc;
    border: 2.5px solid var(--primary);
  }
  .menu-btn:active {
    background: #c5f7e1;
    color: var(--primary-dark);
  }
  .logout-btn {
    margin-top: 42px;
    font-size: 1.06rem;
    background: var(--danger);
    color: #fff;
    font-weight: 600;
    border-radius: 11px;
    border: none;
    padding: 15px 0;
    box-shadow: 0 2px 8px #0001;
    cursor: pointer;
    transition: background 0.18s;
  }
  .logout-btn:active { background: #ba3232; }
  /* Mobile-first improvements */
  @media (max-width: 600px) {
    .admin-app { max-width: 100vw; padding-bottom: 22vw; }
    .header { font-size: 1.28rem; padding: 18vw 0 7vw 0; border-radius: 0 0 18vw 18vw; }
    .menu-vertical { gap: 12vw; }
    .menu-btn { font-size: 1.12rem; border-radius: 9vw; padding: 6vw 0; }
    .logout-btn { padding: 5vw 0; font-size: 1.02rem; border-radius: 8vw; }
  }
    .encerrar-btn {
  margin-top: 64px;
  background: #f6f7f8;
  color: #888;
  border: none;
  border-radius: 9px;
  padding: 12px 0;
  font-size: 1rem;
  font-weight: 500;
  box-shadow: 0 2px 5px #0001;
  width: 100%;
  cursor: pointer;
  transition: background 0.16s, color 0.13s;
  display: block;
}
.encerrar-btn:hover {
  background: #ffd972;
  color: #175f4c;
}
</style>
</head>
<body>
<div class="admin-app">
  <div class="header">Painel Administrativo</div>
  <div class="welcome">Bem-vinda ao Painel, Terapeuta!</div>
  <div class="menu-vertical">
    <button class="menu-btn" onclick="location.href='agenda.php'">Agenda</button>
    <button class="menu-btn" onclick="location.href='pacientes.php'">Pacientes</button>
    <button class="menu-btn" onclick="location.href='blog.php'">Blog</button>
    <button class="menu-btn" onclick="location.href='precos.php'">Preços</button>
  </div>
  <button class="logout-btn" onclick="location.href='../index.html'">Sair/Home</button>
  <button class="encerrar-btn" onclick="confirmLogout()">Encerrar Sessão</button>
</div>
<div id="logoutModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:#0007; z-index:9999; align-items:center; justify-content:center;">
  <div style="background:#fff; padding:36px 30px; border-radius:15px; box-shadow:0 7px 32px #0003; text-align:center; min-width: 250px;">
    <h2 style="font-size:1.2rem; color:#256d54; margin-bottom:20px;">Deseja realmente encerrar sua sessão?</h2>
    <button onclick="proceedLogout()" style="background:#e25b5b; color:#fff; font-weight:700; border:none; border-radius:7px; padding:10px 30px; font-size:1rem; margin-right:16px; cursor:pointer;">Sim, encerrar</button>
    <button onclick="closeLogoutModal()" style="background:#ececec; color:#256d54; font-weight:600; border:none; border-radius:7px; padding:10px 26px; font-size:1rem; cursor:pointer;">Cancelar</button>
  </div>
</div>
<script>
function confirmLogout() {
  document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
  document.getElementById('logoutModal').style.display = 'none';
}
function proceedLogout() {
  window.location.href = '../logout.php';
}
</script>
</body>
</html>
