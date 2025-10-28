<?php
// Painel de administração de preços (admin/precos.php)
// João, código profissional, seguro, limpo e comentado. Acesse SQL real e scripts relacionados para garantir aderência.

session_start();
require_once 'conexao.php'; // ajuste o caminho se necessário



// Buscar valores atuais
$precos = [
    'quick_15' => '', 'quick_30' => '',
    'padrao_50' => '', 'padrao_90' => '',
    'escalda' => '', 'pacote5' => '', 'pacote10' => ''
];
$res = $conn->query("SELECT preco_15, preco_30 FROM especialidades WHERE nome = 'Quick Massage' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $precos['quick_15'] = $row['preco_15'];
    $precos['quick_30'] = $row['preco_30'];
}
$res = $conn->query("SELECT preco_50, preco_90 FROM especialidades WHERE nome != 'Quick Massage' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $precos['padrao_50'] = $row['preco_50'];
    $precos['padrao_90'] = $row['preco_90'];
}
$res = $conn->query("SELECT preco_escalda FROM especialidades WHERE nome = 'Escalda Pés' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $precos['escalda'] = $row['preco_escalda'];
}

$res = $conn->query("SELECT pacote5, pacote10 FROM especialidades");
while ($row = $res->fetch_assoc()) {
    $precos['pacote5'] = $row['pacote5'];
    $precos['pacote10'] = $row['pacote10'];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
      <link rel="icon" type="image/png" href="/favicon-transparente.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pacotes de Sessões | Consultório de Terapia Corporal Sistêmica</title>
  <!-- TODO O STYLE DO INDEX.HTML: cole abaixo -->
  <style>
    /* Cole todo o <style> do seu index.html aqui */
    html, body {
      width: 100%;
      min-width: 0;
      max-width: 100vw;
      box-sizing: border-box;
      overflow-x: hidden;
    }
    *, *::before, *::after {
      box-sizing: inherit;
    }
    :root {
      --cor-principal: #57643a;
      --cor-secundaria: #e3decf;
      --cor-destaque: #b79b63;
      --cor-btn: #f9b233;
      --cor-faq: #b79b63;
      --fonte: 'Montserrat', Arial, sans-serif;
    }
    body {
  margin: 0;
  font-family: var(--fonte);
  background: var(--cor-secundaria);
  color: #333;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
main {
  flex: 1 0 auto;
}
.footer {
  flex-shrink: 0;
}

    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--cor-principal);
      color: #fff;
      padding: 1.4rem 3vw 1.2rem;
      height: auto;
      min-height: 140px;
      box-shadow: 0 2px 16px #0003;
      z-index: 1000;
    }
    .navbar-logo-central {
      height: 100%;
      display: flex;
      align-items: center;
    }
    .navbar-logo-central img {
      height: 150px;
      width: auto;
      max-width: 210px;
      object-fit: contain;
      display: block;
      margin-right: 12px;
      margin-top: 6px;
    }
    .navbar-menu {
      display: flex;
      gap: 1.4rem;
      list-style: none;
      margin: 0;
      padding: 0;
      align-items: center;
    }
    .navbar-menu a {
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      font-size: 1rem;
      transition: color 0.18s;
    }
    .navbar-menu a:hover {
      color: #ffd970;
    }
    .navbar-toggle {
      display: none;
      background: none;
      border: none;
      color: #fff;
      font-size: 2.2rem;
      cursor: pointer;
      margin-left: auto;
    }
    @media (max-width: 1050px) {
      .navbar-menu {
        position: fixed;
        top: 140px;
        right: 0;
        width: 84vw;
        max-width: 420px;
        height: 100vh;
        background: var(--cor-principal);
        flex-direction: column;
        align-items: flex-start;
        gap: 1.6rem;
        padding: 2.4rem 2rem;
        box-shadow: -3px 0 24px #0003;
        transform: translateX(110%);
        transition: transform 0.28s;
        z-index: 999;
      }
      .navbar-menu.open {
        transform: translateX(0%);
      }
      .navbar-toggle {
        display: block;
        font-size: 2.6rem;
      }
      .navbar-menu a {
        font-size: 1.8rem;
        padding: 0.8rem 0;
        width: 100%;
        font-weight: 700;
      }
    }
    .container-pacotes {
      max-width: 540px;
      margin: 60px auto 0 auto;
      padding: 36px 24px 38px 24px;
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 2px 20px #b79b6335;
      text-align: left;
    }
    .container-pacotes h2 {
      color: #29455c;
      margin-bottom: 1.6em;
      font-size: 2rem;
      font-family: 'Playfair Display', serif;
      text-align: center;
    }
    .pacote-bloco {
      border: 2px solid #f9b233;
      background: #fffaf1;
      border-radius: 20px;
      padding: 1.2em 1em 1.1em 1em;
      margin-bottom: 2.2em;
      text-align: center;
      box-shadow: 0 2px 12px #b79b6322;
    }
    .pacote-bloco strong {
      font-size: 1.3rem;
      color: #b37e00;
      display: block;
      margin-bottom: 0.6em;
    }
    .pacote-bloco button {
      background: #f7c340;
      color: #fff;
      border: none;
      padding: 0.85em 2em;
      font-size: 1.17rem;
      font-weight: 700;
      border-radius: 22px;
      cursor: pointer;
      box-shadow: 0 2px 8px #b79b6325;
      margin-top: 1.1em;
      transition: background 0.16s;
    }
    .pacote-bloco button:hover {
      background: #e6a400;
    }
    .voltar-inicio {
      display: block;
      text-align: center;
      margin-top: 30px;
      font-size: 1.04rem;
      color: #856302;
      text-decoration: underline;
      font-weight: 600;
    }
    .footer {
  text-align: center;
  background: var(--cor-principal);
  color: #fff;
  padding: 1.2rem;
  font-size: 1rem;
  letter-spacing: 0.05em;
  margin-top: 48px;
  width: 100vw;
  left: 0;
  bottom: 0;
}
@media (max-width: 650px) {
  .footer {
    font-size: 0.93rem;
    padding: 1.1rem 2vw;
    margin-top: 30px;
  }
}
    @media (max-width: 650px) {
  .container-pacotes {
    max-width: 97vw;
    margin: 36px 1vw 0 1vw;
    padding: 22px 3vw 30px 3vw;
    border-radius: 13px;
    box-shadow: 0 2px 10px #b79b6322;
  }
  .pacote-bloco {
    padding: 1em 0.3em 0.9em 0.3em;
    font-size: 0.97em;
  }
  .pacote-bloco button {
    font-size: 1em;
    padding: 0.7em 1.1em;
  }
  .container-pacotes h2 {
    font-size: 1.25rem;
    margin-bottom: 1em;
  }
  .voltar-inicio {
    margin-top: 22px;
    font-size: 0.96rem;
  }
}
@media (max-width: 400px) {
  .container-pacotes {
    padding: 7vw 2vw 18vw 2vw;
    border-radius: 7px;
  }
  .navbar-logo-central img {
    max-width: 170px;
    height: 130px;
    margin-top: 4px;
  }
  .pacote-bloco button {
    font-size: 0.96em;
    padding: 0.6em 0.6em;
  }
}
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>
  <nav class="navbar">
    <div class="navbar-logo-central">
      <img src="img/logo.png" alt="Consultório de Terapia Corporal Sistêmica" />
    </div>
    <button class="navbar-toggle" aria-label="Abrir menu">&#9776;</button>
    <ul class="navbar-menu">
      <li><a href="index.html">Home</a></li>
      <li><a href="espaco.html">O Espaço</a></li>
      <li><a href="servicos.html">Serviços</a></li>
      <li><a href="blog.html">Blog</a></li>
      <li><a href="contato.html">Contato</a></li>
      <li id="li-perfil-login"><a href="registrar.html"><i class="fa-solid fa-user"></i> Login</a></li>
    </ul>
  </nav>

  <main>
    <div class="container-pacotes">
      <h2>Escolha seu pacote de sessões</h2>
      <div class="pacote-bloco">
        <strong>5 sessões por R$ <?= htmlspecialchars($precos['pacote5']) ?></strong>
        <button onclick="compraPacote(5)">Comprar pacote de 5 sessões</button>
      </div>
      <div class="pacote-bloco">
        <strong>10 sessões por R$ <?= htmlspecialchars($precos['pacote10']) ?></strong>
        <button onclick="compraPacote(10)">Comprar pacote de 10 sessões</button>
      </div>
      <a href="index.html" class="voltar-inicio">← Voltar para a página inicial</a>
      <p style="font-size:1.05rem;text-align:center;color:#8a730b; margin-top:24px">O pacote deve ser pago na primeira sessão após a compra.</p>
    </div>
  </main>

  <footer class="footer">
    <p>Consultório de Terapia Corporal Sistêmica</p>
  </footer>

  <script>
    // Menu responsivo sanduíche
    const navbarToggle = document.querySelector('.navbar-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    navbarToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('open');
    });
    // Fechar menu ao clicar em um item
    document.querySelectorAll('.navbar-menu a').forEach(link => {
      link.addEventListener('click', () => {
        navbarMenu.classList.remove('open');
      });
    });
    function compraPacote(qtd) {
  fetch('comprarPacote.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ quantidade: qtd })
  })
  .then(resp => resp.json())
  .then(data => {
    if (data.sucesso) {
      alert("Seu pacote foi reservado! Pagamento na próxima consulta.");

      // Checa se veio de agendamento (parâmetro na URL)
      const params = new URLSearchParams(window.location.search);
      if (params.get('origem') === 'agendamento') {
        window.location.href = "agendamento.php?pacote_sucesso=1";
      } else {
        window.location.href = "perfil.html";
      }
    } else {
      alert("Erro ao reservar pacote: " + (data.mensagem || "Tente novamente."));
    }
  });
}

    // Exemplo: após resposta de compra bem-sucedida

    // --- Troca "Perfil" ou "Login" no menu, conforme login ---
    fetch('getPerfil.php', { credentials: 'include' })
      .then(res => res.ok ? res.json() : Promise.reject())
      .then(data => {
          if (data.usuario && data.usuario.nome) {
              // Usuário logado
              document.getElementById('li-perfil-login').innerHTML =
                  '<a href="perfil.html"><i class="fa-solid fa-user"></i> Perfil</a>';
          } else {
              // Não logado
              document.getElementById('li-perfil-login').innerHTML =
                  '<a href="registrar.html"><i class="fa-solid fa-user"></i> Login</a>';
          }
      })
      .catch(() => {
          // Não logado ou erro de sessão
          document.getElementById('li-perfil-login').innerHTML =
              '<a href="registrar.html"><i class="fa-solid fa-user"></i> Login</a>';
      });

  </script>

</body>
</html>

