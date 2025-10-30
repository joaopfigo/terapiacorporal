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
    max-width: 1120px;
    margin: 0 auto;
    padding: 0 40px 64px;
    min-height: calc(100vh - 220px);
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 28px;
  }
  .header {
    background: linear-gradient(135deg, var(--primary) 0%, #1d7f63 100%);
    color: #fff;
    width: 100%;
    box-shadow: 0 8px 32px #1d9a7718;
  }
  .header-content {
    max-width: 1120px;
    margin: 0 auto;
    padding: 60px 40px 52px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .header-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2.4rem, 4vw, 3.2rem);
    letter-spacing: 1.2px;
    margin: 0;
    font-weight: 700;
  }
  .header-subtitle {
    font-size: 1.18rem;
    font-weight: 500;
    color: #e9f7f1;
    letter-spacing: 0.4px;
    margin: 0;
  }
  .welcome {
    font-size: 1.12rem;
    font-family: Roboto,sans-serif;
    color: #315950;
    text-align: left;
    margin-bottom: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
  }
  .menu-vertical {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 26px;
    margin: 0;
    width: 100%;
  }
  .menu-btn {
    border: none;
    background: var(--card-bg);
    border-radius: 24px;
    padding: 28px 32px;
    font-weight: 700;
    font-size: 1.17rem;
    color: var(--primary-dark);
    box-shadow: var(--shadow);
    transition: transform 0.18s ease, background 0.18s ease, color 0.16s, box-shadow 0.18s;
    cursor: pointer;
    outline: none;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 10px;
    letter-spacing: 0.4px;
    border: 2.5px solid transparent;
    text-align: left;
  }
  .menu-btn:hover, .menu-btn:focus {
    background: var(--accent);
    color: var(--primary);
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border: 2.5px solid var(--primary);
  }
  .menu-btn:active {
    background: #c5f7e1;
    color: var(--primary-dark);
  }
  .menu-icon {
    font-size: 2rem;
  }
  .menu-title {
    font-size: 1.26rem;
    font-weight: 700;
  }
  .menu-description {
    font-size: 0.98rem;
    font-weight: 500;
    color: #4f6b64;
    letter-spacing: 0.2px;
  }
  .logout-btn {
    margin-top: 42px;
    font-size: 1.04rem;
    background: var(--danger);
    color: #fff;
    font-weight: 600;
    border-radius: 14px;
    border: none;
    padding: 15px 24px;
    box-shadow: 0 6px 18px #0002;
    cursor: pointer;
    transition: background 0.18s ease, transform 0.18s ease;
    align-self: flex-start;
  }
  .logout-btn:hover {
    background: #d94b4b;
    transform: translateY(-2px);
  }
  .logout-btn:active { background: #ba3232; transform: translateY(0); }
  /* Mobile-first improvements */
  @media (max-width: 600px) {
    .header-content { padding: 18vw 9vw 10vw; }
    .header-title { font-size: 1.8rem; }
    .header-subtitle { font-size: 1.02rem; }
    .admin-app { max-width: 100vw; padding: 0 7vw 22vw; gap: 18vw; min-height: auto; }
    .menu-vertical { display: flex; flex-direction: column; gap: 12vw; }
    .menu-btn {
      font-size: 1.12rem;
      border-radius: 9vw;
      padding: 6vw 7vw;
      align-items: center;
      text-align: center;
    }
    .menu-icon { font-size: 1.8rem; }
    .menu-description { font-size: 0.96rem; }
    .logout-btn { padding: 5vw 7vw; font-size: 1.02rem; border-radius: 8vw; align-self: stretch; }
    .encerrar-btn { max-width: none; width: 100%; border-radius: 8vw; }
  }
  .encerrar-btn {
    margin-top: 24px;
    background: #f0f5f4;
    color: #3f5f58;
    border: none;
    border-radius: 14px;
    padding: 14px 24px;
    font-size: 1rem;
    font-weight: 600;
    box-shadow: 0 4px 12px #0001;
    width: 100%;
    max-width: 320px;
    cursor: pointer;
    transition: background 0.16s ease, color 0.13s ease, transform 0.16s ease;
    align-self: flex-start;
  }
  .encerrar-btn:hover {
    background: #dff0ed;
    color: var(--primary-dark);
    transform: translateY(-2px);
  }
</style>
</head>
<body>
<header class="header">
  <div class="header-content">
    <h1 class="header-title">Painel Administrativo</h1>
    <p class="header-subtitle">Bem-vinda ao seu centro de gestão terapêutica.</p>
  </div>
</header>
<div class="admin-app">
  <div class="welcome">Escolha uma área para gerenciar:</div>
  <div class="menu-vertical">
    <button class="menu-btn" onclick="location.href='agenda.php'">
      <span class="menu-icon" aria-hidden="true">🗓️</span>
      <span class="menu-title">Agenda</span>
      <span class="menu-description">Visualize sessões, confirme presenças e ajuste horários.</span>
    </button>
    <button class="menu-btn" onclick="location.href='pacientes.php'">
      <span class="menu-icon" aria-hidden="true">👥</span>
      <span class="menu-title">Pacientes</span>
      <span class="menu-description">Acesse históricos, contatos e notas de acompanhamento.</span>
    </button>
    <button class="menu-btn" onclick="location.href='blog.php'">
      <span class="menu-icon" aria-hidden="true">📝</span>
      <span class="menu-title">Blog</span>
      <span class="menu-description">Crie conteúdos inspiradores e mantenha sua comunidade engajada.</span>
    </button>
    <button class="menu-btn" onclick="location.href='precos.php'">
      <span class="menu-icon" aria-hidden="true">💳</span>
      <span class="menu-title">Preços</span>
      <span class="menu-description">Atualize planos, valores e pacotes promocionais.</span>
    </button>
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
