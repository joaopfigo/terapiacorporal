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
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agendamento Online | Terapias</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
    :root {
      --cor-principal: #57643a;
      --cor-nav: #57643a;
      --cor-destaque: #f7e6bc;
      --cor-fundo: #fcfaf7;
      --cor-texto: #2f2d2a;
    }
    body {
      background: var(--cor-fundo);
      font-family: 'Roboto', Arial, sans-serif;
      margin: 0;
      color: var(--cor-texto);
    }
    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--cor-nav);
      color: #fff;
      padding: 0.4rem 2vw;
      height: 72px;
      min-height: 60px;
      position: relative;
      z-index: 1000;
    }
    .navbar-logo-central span {
      font-family: 'Dancing Script', cursive;
      font-size: 1.52rem;
      color: #fff2c6;
      font-weight: 700;
      letter-spacing: 0.02em;
      margin-left: 10px;
      text-shadow: 0 2px 7px #b79b6350;
      transition: color 0.18s;
    }

    /* LOGO CENTRAL */
    .navbar-logo-central {
      height: 100%;
      display: flex;
      align-items: center;
    }
    .navbar-logo-central img {
      height: 200px; /* Aqui aumentei um pouco a logo, ajuste conforme seu gosto, mas sem passar de 72px */
      width: auto;
      max-width: 210px;
      object-fit: contain;
      display: block;
      margin-right: 12px;
    }

    /* MENU */
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

    /* Botão do menu sanduíche só aparece em telas pequenas */
    .navbar-toggle {
      display: none;
      background: none;
      border: none;
      color: #fff;
      font-size: 2.2rem;
      cursor: pointer;
      margin-left: auto;
    }

    /* RESPONSIVO */
    @media (max-width: 1050px) {
      .navbar-menu {
        position: fixed;
        top: 72px;
        right: 0;
        width: 68vw;
        max-width: 320px;
        height: 100vh;
        background: var(--cor-principal);
        flex-direction: column;
        align-items: flex-start;
        gap: 1.2rem;
        padding: 2.4rem 1.3rem;
        box-shadow: -2px 0 18px #2222;
        transform: translateX(110%);
        transition: transform 0.28s;
        z-index: 999;
      }
      .navbar-menu.open {
        transform: translateX(0%);
      }
      .navbar-toggle {
        display: block;
      }
    }

    @media (max-width: 700px) {
      .navbar { padding: 0.3rem 2vw; height: 54px; }
      .navbar-logo-central img { height: 150px; max-width: 105px; }
      .navbar-menu { gap: 0.6rem; padding: 1.2rem 2vw; top: 54px; }
    }

    .container {
      max-width: 670px;
      background: #fff;
      margin: 38px auto;
      border-radius: 26px;
      box-shadow: 0 6px 40px rgba(160,130,64,0.08);
      padding: 36px 6vw 24px 6vw;
    }
    #success-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 78vh;
      animation: fadeIn 0.7s;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px);}
      to { opacity: 1; transform: translateY(0);}
    }
    .success-icon {
      animation: popIn 0.5s;
    }
    @keyframes popIn {
      0% { transform: scale(0.7); opacity: 0; }
      80% { transform: scale(1.1); opacity: 1; }
      100% { transform: scale(1); }
    }
    h1 {
      color: var(--cor-principal);
      font-size: 2.1rem;
      font-family: 'Playfair Display', serif;
      margin: 0 0 15px 0;
      font-weight: 700;
      text-align: center;
    }
    .step {
      margin-bottom: 31px;
      border-radius: 13px;
      background: #fcfaf7;
      box-shadow: 0 0 0 1.5px #f2f0ea;
      padding: 23px 17px 15px 17px;
    }
    h2 {
      font-size: 1.13rem;
      margin: 0 0 14px 0;
      color: var(--cor-principal);
      font-weight: 700;
      font-family: 'Roboto', Arial, sans-serif;
      letter-spacing: 0.01em;
    }
    .btn-form {
      background: var(--cor-destaque);
      color: var(--cor-principal);
      border: none;
      padding: 11px 28px;
      border-radius: 8px;
      font-size: 1.09rem;
      font-weight: 700;
      cursor: pointer;
      margin-bottom: 8px;
      box-shadow: 0 2px 10px rgba(160,130,64,0.09);
      transition: background 0.2s;
    }
    .btn-form:hover {
      background: #fff3dc;
    }
    .obs {
      font-size: 0.98rem;
      color: #a48e52;
      margin-top: 5px;
    }
    .treatments {
      display: grid;
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 14px 20px;
      margin-bottom: 7px;
    }
    .treatment {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 10px 4px 16px 4px;
      border: none;
      border-radius: 15px;
      background: #f9f6f1;
      box-shadow: 0 2px 10px rgba(123,80,151,0.08);
      cursor: pointer;
      transition: box-shadow 0.17s, background 0.16s;
      outline: none;
    }
    .treatment.selected, .treatment:hover {
      box-shadow: 0 0 0 2px var(--cor-principal) inset, 0 4px 24px rgba(123,80,151,0.06);
      background: #f5eefc;
    }
    .treatment img {
      width: 72px;
      height: 72px;
      margin-bottom: 7px;
      border-radius: 14px;
      object-fit: cover;
      background: #fff;
      box-shadow: 0 1px 6px rgba(123,80,151,0.07);
      border: 1px solid #ede5fa;
    }
    .treatment span {
      font-size: 1.04rem;
      color: #6d5d7a;
      font-weight: 600;
      text-align: center;
    }
    .durations label, .combo label {
      display: inline-block;
      margin-right: 21px;
      font-size: 1.01rem;
      margin-bottom: 9px;
      color: #4e4360;
      cursor: pointer;
    }
    .promo {
      background: #f6f0fd;
      border-left: 4px solid #e7d2ff;
      margin-top: 11px;
      padding: 9px 13px 7px 16px;
      border-radius: 9px;
      margin-bottom: 8px;
      font-size: 0.98rem;
    }
    .promo h3 {
      color: #a788d9;
      margin: 0 0 6px 0;
      font-size: 1.01rem;
      font-weight: 700;
    }
    .combo label { margin-right: 17px; }
    .calendar-section {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 7px;
    }
    .slots {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 4px;
    }
    .slot {
      padding: 7px 17px;
      border-radius: 7px;
      border: 1.3px solid #cdb3ea;
      background: #faf6ff;
      color: #7b5097;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.16s, color 0.16s;
    }
    .slot.selected, .slot:hover {
      background: #a788d9;
      color: #fff;
      border-color: #a788d9;
    }
    .booking-option {
      background: #f9f6f1;
      padding: 17px 13px 12px 13px;
      border-radius: 10px;
      max-width: 390px;
      margin-top: 3px;
    }
    .btn-login {
      background: #efe0fa;
      color: #7b5097;
      border: none;
      padding: 8px 19px;
      border-radius: 7px;
      font-size: 1.06rem;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-login .mini {
      font-size: 0.87rem;
      color: #bca870;
      margin-left: 4px;
    }
    .or {
      text-align: center;
      margin: 13px 0 8px 0;
      color: #bca870;
      font-weight: 500;
    }
    .guest-form label {
      display: block;
      font-size: 0.98rem;
      color: #7862a5;
      margin-bottom: 3px;
      margin-top: 8px;
    }
    .guest-form input[type="text"],
    .guest-form input[type="email"],
    .guest-form input[type="tel"] {
      width: 100%;
      border: 1.1px solid #e7d2ff;
      border-radius: 6px;
      padding: 8px 10px;
      font-size: 1rem;
      margin-top: 2px;
      background: #f6f0fd;
    }
    .phone-input { display: flex; align-items: center; gap: 7px; }
    .phone-input .flag { font-size: 1.1em; margin-right: 1px; }
    .chk { margin-top: 5px; font-size: 0.97rem; color: #7862a5; }
    .btn-continue, #btn-agendar {
      margin-top: 10px;
      background: #7b5097;
      color: #fff;
      border: none;
      border-radius: 7px;
      font-size: 1.11rem;
      font-weight: bold;
      padding: 13px 0;
      width: 100%;
      cursor: pointer;
      box-shadow: 0 2px 18px rgba(130,110,160,0.06);
      transition: background .16s;
      letter-spacing: 0.01em;
    }
    .btn-continue:hover, #btn-agendar:hover {
      background: #a788d9;
    }
    #btn-agendar[disabled] {
      background: #d7cae6;
      color: #f1edf6;
      cursor: not-allowed;
    }
    @media (max-width: 800px) {
      .container { padding: 10px 1vw 14px 1vw; max-width: 98vw; }
    }
     .form-btn-area {
      text-align: right;
      margin-top: 17px;
    }
    .btn-voltar {
      background: #ede6c6;
      color: #7b5097;
      border: none;
      border-radius: 5px;
      padding: 8px 15px;
      font-size: .99rem;
      font-weight: bold;
      margin-right: 7px;
      cursor: pointer;
      transition: background 0.14s;
    }
    .btn-voltar:hover {
      background: #e2d6b8;
    }
    /* Adicional responsivo para formulários e selects */
    .formulario-fields textarea, .formulario-fields select {
      width: 100%;
      font-size: 1rem;
      border-radius: 7px;
      padding: 8px 11px;
      margin-top: 4px;
      border: 1.1px solid #e7d2ff;
      background: #f6f0fd;
      resize: vertical;
    }
    @media (max-width: 700px) {
      .form-btn-area { text-align: center; }
      .btn-voltar { margin-bottom: 10px; }
    }
    #aviso-tratamento-toast {
      position: fixed;
      left: 50%;
      z-index: 9999;
      background: linear-gradient(90deg, #4466b3 0%, #36c 100%);
      color: #fff;
      font-size: 1.08rem;
      font-weight: 600;
      padding: 13px 28px;
      border-radius: 18px;
      box-shadow: 0 3px 16px rgba(44,76,180,0.19);
      opacity: 0;
      transition: opacity 0.45s, top 0.24s;
      pointer-events: none;
      text-align: center;
      min-width: 210px;
      max-width: 95vw;
    }
    #aviso-tratamento-toast.show {
      opacity: 1;
      pointer-events: auto;
    }
    @media (max-width: 700px) {
      #aviso-tratamento-toast {
        top: 10px !important;
        left: 50% !important;
        right: auto;
        bottom: auto !important;
        transform: translateX(-50%);
        font-size: 1.02rem;
        padding: 11px 14vw;
      }
    }
    .treatment.block-hover:not(.selected):hover,
    .treatment.block-hover:not(.selected):active,
    .treatment.block-hover:not(.selected):focus {
      background: inherit !important;
      box-shadow: none !important;
      border-color: #ddd !important;
      color: inherit !important;
      filter: none !important;
      outline: none !important;
      cursor: not-allowed !important;
    }
  </style>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
</head>
<body>
  <nav class="navbar">
    <div class="navbar-logo-central">
      <img src="img/logo.png" alt="Consultório de Terapia Corporal Sistêmica" />
      <span>Virlene Figueiredo</span>
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

  <div class="container" id="agendamento-container">
    <h1>Agendamento Online</h1>
    <div class="step" id="step-formulario">
      <h2>1. Preencha o formulário de queixas. O que você está sentindo?</h2>
      <button class="btn-form" id="btn-formulario">Acesso ao formulário</button>
      <div class="obs">Caso não preencha, entende-se que seu objetivo é apenas relaxamento.</div>
    </div>
    <div id="formulario-section" class="step" style="display:none;">
      <form id="form-reclamacoes">
        <div class="formulario-fields">
          <label for="principal_desconforto">Descreva seu principal desconforto ou dor:</label>
          <textarea id="principal_desconforto" name="principal_desconforto" rows="3"></textarea>

          <label for="queixa_secundaria">Caso tenha outras queixas, descreva aqui:</label>
          <textarea id="queixa_secundaria" name="queixa_secundaria" rows="2"></textarea>

          <label for="tempo_desconforto">Há quanto tempo sente esse desconforto?</label>
          <input type="text" id="tempo_desconforto" name="tempo_desconforto">

          <label for="classificacao_dor">Como classifica sua dor?</label>
          <input type="text" id="classificacao_dor" name="classificacao_dor">

          <label for="tratamento_medico">Já procurou tratamento médico? Se sim, qual?</label>
          <textarea id="tratamento_medico" name="tratamento_medico" rows="2"></textarea>
        </div>

        <div class="formulario-fields" style="margin-top: 25px;">
          <h2>Condições de saúde (marque as opções que se aplicam):</h2>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <label><input type="checkbox" name="check1"> Sob cuidados de um médico/terapeuta</label>
            <label><input type="checkbox" name="check2"> Toma medicação com prescrição médica</label>
            <label><input type="checkbox" name="check3"> Grávida ou tentando engravidar</label>
            <label><input type="checkbox" name="check4"> Lesão em alguma parte do corpo</label>
            <label><input type="checkbox" name="check5"> Torcicolo</label>
            <label><input type="checkbox" name="check6"> Dores na coluna/costas (qual parte)</label>
            <label><input type="checkbox" name="check7"> Câimbras</label>
            <label><input type="checkbox" name="check8"> Distensões</label>
            <label><input type="checkbox" name="check9"> Fraturas</label>
            <label><input type="checkbox" name="check10"> Edemas</label>
            <label><input type="checkbox" name="check11"> Dores em outras partes do corpo (quais)</label>
            <label><input type="checkbox" name="check12"> Cirurgias recentes ou passadas</label>
            <label><input type="checkbox" name="check13"> Problemas de pele/couro cabeludo</label>
            <label><input type="checkbox" name="check14"> Indigestão (azia, refluxo, gastrite)</label>
            <label><input type="checkbox" name="check15"> Problemas intestinais</label>
            <label><input type="checkbox" name="check16"> Prisão de Ventre/Diarreia</label>
            <label><input type="checkbox" name="check17"> Má circulação</label>
            <label><input type="checkbox" name="check18"> Trombose</label>
            <label><input type="checkbox" name="check19"> Problemas Cardíacos</label>
            <label><input type="checkbox" name="check20"> Pressão alta/baixa</label>
            <label><input type="checkbox" name="check21"> Artrite/Reumatismo</label>
            <label><input type="checkbox" name="check22"> Asma</label>
            <label><input type="checkbox" name="check23"> Alergias</label>
            <label><input type="checkbox" name="check24"> Rinite/Sinusite</label>
            <label><input type="checkbox" name="check25"> Diabetes</label>
            <label><input type="checkbox" name="check26"> Colesterol alto</label>
            <label><input type="checkbox" name="check27"> Epilepsia</label>
            <label><input type="checkbox" name="check28"> Osteoporose</label>
            <label><input type="checkbox" name="check29"> Câncer</label>
            <label><input type="checkbox" name="check30"> Doença contagiosa ou infecciosa</label>
            <label><input type="checkbox" name="check31"> Sono constante</label>
          </div>
        </div>

        <div class="formulario-fields" style="margin-bottom: 15px;">
          <h2 style="margin-top: 35px;">Estado emocional</h2>
          <fieldset style="border: none; margin-bottom: 20px;">
            <legend>Ansiedade (1 = leve / 5 = intensa):</legend>
            <label><input type="radio" name="ansiedade" value="1"> 1</label>
            <label><input type="radio" name="ansiedade" value="2"> 2</label>
            <label><input type="radio" name="ansiedade" value="3"> 3</label>
            <label><input type="radio" name="ansiedade" value="4"> 4</label>
            <label><input type="radio" name="ansiedade" value="5"> 5</label>
          </fieldset>

          <fieldset style="border: none; margin-bottom: 20px;">
            <legend>Tristeza:</legend>
            <label><input type="radio" name="tristeza" value="1"> 1</label>
            <label><input type="radio" name="tristeza" value="2"> 2</label>
            <label><input type="radio" name="tristeza" value="3"> 3</label>
            <label><input type="radio" name="tristeza" value="4"> 4</label>
            <label><input type="radio" name="tristeza" value="5"> 5</label>
          </fieldset>

          <fieldset style="border: none; margin-bottom: 20px;">
            <legend>Raiva:</legend>
            <label><input type="radio" name="raiva" value="1"> 1</label>
            <label><input type="radio" name="raiva" value="2"> 2</label>
            <label><input type="radio" name="raiva" value="3"> 3</label>
            <label><input type="radio" name="raiva" value="4"> 4</label>
            <label><input type="radio" name="raiva" value="5"> 5</label>
          </fieldset>

          <fieldset style="border: none; margin-bottom: 20px;">
            <legend>Preocupação/Pensamentos:</legend>
            <label><input type="radio" name="preocupacao" value="1"> 1</label>
            <label><input type="radio" name="preocupacao" value="2"> 2</label>
            <label><input type="radio" name="preocupacao" value="3"> 3</label>
            <label><input type="radio" name="preocupacao" value="4"> 4</label>
            <label><input type="radio" name="preocupacao" value="5"> 5</label>
          </fieldset>

          <fieldset style="border: none; margin-bottom: 20px;">
            <legend>Medo:</legend>
            <label><input type="radio" name="medo" value="1"> 1</label>
            <label><input type="radio" name="medo" value="2"> 2</label>
            <label><input type="radio" name="medo" value="3"> 3</label>
            <label><input type="radio" name="medo" value="4"> 4</label>
            <label><input type="radio" name="medo" value="5"> 5</label>
          </fieldset>

          <fieldset style="border: none; margin-bottom: 20px;">
            <legend>Raiva/irritação:</legend>
            <label><input type="radio" name="irritacao" value="1"> 1</label>
            <label><input type="radio" name="irritacao" value="2"> 2</label>
            <label><input type="radio" name="irritacao" value="3"> 3</label>
            <label><input type="radio" name="irritacao" value="4"> 4</label>
            <label><input type="radio" name="irritacao" value="5"> 5</label>
          </fieldset>

          <fieldset style="border: none; margin-bottom: 20px;">
            <legend>Angústia:</legend>
            <label><input type="radio" name="angustia" value="1"> 1</label>
            <label><input type="radio" name="angustia" value="2"> 2</label>
            <label><input type="radio" name="angustia" value="3"> 3</label>
            <label><input type="radio" name="angustia" value="4"> 4</label>
            <label><input type="radio" name="angustia" value="5"> 5</label>
          </fieldset>
        </div>

        <div class="formulario-fields" style="margin-top: 25px;">
          <input type="checkbox" id="termo" name="termo" required>
          <label for="termo">
            Afirmo ter lido e discutido as informações acima com meu terapeuta. Estou ciente de que o tratamento não é diagnóstico e nem mesmo substituto para o meu tratamento médico. Afirmo não ter omitido nenhuma informação concernente à minha saúde que devesse ter sido revelada e me responsabilizo por comunicar à minha terapeuta qualquer alteração em minhas condições clínicas que possa ocorrer no futuro.
          </label>
        </div>

        <div class="form-btn-area">
          <button type="button" class="btn-voltar" id="voltar-agendamento">Voltar</button>
          <button type="submit" class="btn-continue">Enviar</button>
        </div>
      </form>

      
    </div>
    <div id="formulario-feedback" class="step" style="display:none; text-align:center;">
      <h2 style="color:#30795b;">Resposta salva!</h2>
      <p style="margin-bottom:18px;">Seu formulário d,e queixas foi salvo com sucesso.<br>Pode editar sempre que desejar antes de finalizar o agendamento.</p>
      <button class="btn-form" id="btn-editar-formulario">Editar resposta</button>
    </div>

    <div class="step" id="step-tratamento">
      <h2>2. Escolha o(s) tratamento(s) que deseja realizar (no máximo dois):</h2>
      <div class="treatments">
        <div class="treatment" data-id="1">
          <img src="https://images.pexels.com/photos/3822622/pexels-photo-3822622.jpeg?auto=compress&w=300&h=300&fit=crop" alt="Quick Massage">
          <span>Quick Massage</span>
        </div>
        <div class="treatment" data-id="2">
          <img src="https://images.pexels.com/photos/3997985/pexels-photo-3997985.jpeg?auto=compress&w=300&h=300&fit=crop" alt="Massoterapia">
          <span>Massoterapia</span>
        </div>
        <div class="treatment" data-id="3">
          <img src="https://images.pexels.com/photos/3182811/pexels-photo-3182811.jpeg?auto=compress&w=300&h=300&fit=crop" alt="Reflexologia Podal">
          <span>Reflexologia Podal</span>
        </div>
        <div class="treatment" data-id="4">
          <img src="https://images.pexels.com/photos/6621319/pexels-photo-6621319.jpeg?auto=compress&w=300&h=300&fit=crop" alt="Auriculoterapia">
          <span>Auriculoterapia</span>
        </div>
        <div class="treatment" data-id="5">
          <img src="img/ventosa.jpg" alt="Ventosa">
          <span>Ventosa</span>
        </div>
        <div class="treatment" data-id="6">
          <img src="img/acupuntura.jpg" alt="Acupuntura">
          <span>Acupuntura</span>
        </div>
        <div class="treatment" data-id="7">
          <img src="img/biomagnetismo.png" alt="Biomagnetismo">
          <span>Biomagnetismo</span>
        </div>
        <div class="treatment" data-id="8">
          <img src="img/reiki.jpg" alt="Reiki">
          <span>Reiki</span>
        </div>
      </div>
      <div id="aviso-tratamento" style="display:none; margin-top:10px; color:#d9534f; font-weight:600; text-align:center;"></div>

    </div>
    <div class="step" id="step-duracao">
      <h2>3. Duração</h2>
      <div class="durations">
        <label><input type="radio" name="duracao" value="50" data-preco="<?= htmlspecialchars($precos['padrao_50']) ?>"> 50 min — R$ <?= htmlspecialchars($precos['padrao_50']) ?></label>
        <label><input type="radio" name="duracao" value="90" data-preco="<?= htmlspecialchars($precos['padrao_90']) ?>"> 90 min — R$ <?= htmlspecialchars($precos['padrao_90']) ?></label>
        <label><input type="radio" name="duracao" id="duracao-pacote" value="pacote5" data-preco="0"> Utilizar pacote de sessões</label>
      </div>
      <div id="opcao-comprar-pacote" style="display:none;">
        <button type="button" onclick="window.location.href='pacotes.php?origem=agendamento'">Comprar pacote</button>
      </div>
      <div id="info-pacote"></div>


      <div class="promo">
  <h3>PROMOÇÃO: ACRESCENTE UM ESCALDA PÉS!</h3>
  <div class="combo">
    <label>
      <input type="checkbox" id="escalda-pes" onchange="calcularValorFinal();" name="escalda-pes" value="escalda" data-preco="<?= htmlspecialchars($precos['escalda']) ?>" > Quero adicionar escalda pés (+ R$ <?= htmlspecialchars($precos['escalda']) ?>)
    </label>
  </div>
</div>
<div class="step" id="step-horario">
  <h2>4. Escolha a data e o horário</h2>
  <div class="calendar-section">
    <div id="custom-calendar"></div>
    <div class="slots" id="slots-area"></div>
  </div>
</div>
    <div class="step" id="step-dados">
      <h2>5. Preencher dados</h2>
      <div class="booking-option" id="dados-usuario" style="display:none">
        <button class="btn-login">Você já está logado!</button>
      </div>
      <div class="booking-option" id="dados-guest" style="display:none">
        <div class="or">Preencha abaixo se não estiver logado</div>
        <div class="guest-form">
          <label for="guest-name">Nome</label>
          <input type="text" id="guest-name" name="guest-name" placeholder="Digite seu nome" required>
          <label for="guest-email">E-mail</label>
          <input type="email" id="guest-email" name="guest-email" placeholder="Seu e-mail" required>
                    <label for="guest-phone">Número de telefone</label>
          <div class="phone-input">
            <span class="flag">🇧🇷</span>
            <input type="tel" id="guest-phone" name="guest-phone" placeholder="+55" pattern="\+?\d{2,15}" required>
          </div>
          <label for="guest-nascimento">Data de nascimento</label>
          <input type="date" id="guest-nascimento" name="guest-nascimento" required>

          <label for="guest-sexo">Sexo</label>
          <select id="guest-sexo" name="guest-sexo" required>
            <option value="">Selecione...</option>
            <option value="feminino">Feminino</option>
            <option value="masculino">Masculino</option>
            <option value="outro">Outro</option>
            <option value="prefiro_nao_dizer">Prefiro não dizer</option>
          </select>

          <div class="chk">
            <input type="checkbox" id="criar-conta" name="criar-conta">
            <label for="criar-conta">Criar minha conta no site para acompanhar meu agendamento</label> 
          </div>

          <div id="campo-senhas" style="display:none; margin-top:12px;">
            <label for="guest-senha">Senha</label>
            <input type="password" id="guest-senha" name="guest-senha" autocomplete="new-password">
            <label for="guest-senha2">Confirmar senha</label>
            <input type="password" id="guest-senha2" name="guest-senha2" autocomplete="new-password">
          </div>
        </div>
      </div>
    </div>
    <div class="total-container" style="margin-top:20px;">
      <strong>Valor total da sessão: R$ <span id="valor-total">[valor padrão]</span></strong>
     <br>
     <small>O pagamento deve ser feito no dia da consulta através de pix, dinheiro, cartão de crédito ou débito.</small>
    </div>

    <button id="btn-agendar" class="btn-continue" style="margin:38px auto 0 auto; display:block; width:220px;">Agendar Sessão</button>
  </div>

  <script>
    let formularioEnviado = false;
    // HTML padrão (todas as opções exceto quick massage sozinho)
    const htmlDuracoesPadrao = `
      <label><input type="radio" name="duracao" value="50" data-preco="<?= htmlspecialchars($precos['padrao_50']) ?>"> 50 min — R$ <?= htmlspecialchars($precos['padrao_50']) ?></label>
      <label><input type="radio" name="duracao" value="90" data-preco="<?= htmlspecialchars($precos['padrao_90']) ?>"> 90 min — R$ <?= htmlspecialchars($precos['padrao_90']) ?></label>
      <label><input type="radio" name="duracao" id="duracao-pacote" value="pacote5" data-preco="0"> Utilizar pacote de sessões</label>
    `;

    // HTML especial para quick massage sozinho
    const htmlDuracoesQuick = `
  <label><input type="radio" name="duracao" value="15" data-preco="<?= htmlspecialchars($precos['quick_15']) ?>"> 15 min — R$ <?= htmlspecialchars($precos['quick_15']) ?></label>
  <label><input type="radio" name="duracao" value="30" data-preco="<?= htmlspecialchars($precos['quick_30']) ?>"> 30 min — R$ <?= htmlspecialchars($precos['quick_30']) ?></label>
  <label><input type="radio" name="duracao" id="duracao-pacote" value="pacote5" data-preco="0"> Utilizar pacote de sessões</label>
    `;
    function atualizarDuracoes() {
      const cards = document.querySelectorAll('.treatment.selected');
      const durationsDiv = document.querySelector('.durations');
      
      // Só "Quick Massage" selecionada
      if (
        cards.length === 1 &&
        cards[0].innerText.trim().toLowerCase() === "quick massage"
      ) {
        durationsDiv.innerHTML = htmlDuracoesQuick;
      } else {
        durationsDiv.innerHTML = htmlDuracoesPadrao;
      }
      // Recoloca os eventos nos radios (importante para funcionar o valor total!)
      document.querySelectorAll('input[name="duracao"]').forEach(function(radio) {
        radio.addEventListener('change', calcularValorFinal);
      });
      calcularValorFinal();
      atualizarPacoteAgendamento();
    }

    document.getElementById('btn-formulario').onclick = function() {
      document.getElementById('formulario-section').style.display = 'block';
      document.getElementById('step-formulario').style.display = 'none';
    };
    document.getElementById('voltar-agendamento').onclick = function() {
      if (formularioEnviado) {
        document.getElementById('formulario-section').style.display = 'none';
        document.getElementById('formulario-feedback').style.display = 'block';
      } else {
        document.getElementById('formulario-section').style.display = 'none';
        document.getElementById('step-formulario').style.display = '';
      }
    };

    document.getElementById('form-reclamacoes').onsubmit = function(e) {
      e.preventDefault();
      formularioEnviado = true; // Marca que foi enviado!
      document.getElementById('formulario-section').style.display = 'none';
      document.getElementById('formulario-feedback').style.display = 'block';
    };

    document.getElementById('btn-editar-formulario').onclick = function() {
      document.getElementById('formulario-feedback').style.display = 'none'; // esconde feedback
      document.getElementById('formulario-section').style.display = 'block'; // mostra o formulário
    };
    // Função calendário visual
    function renderCalendar(year, month) {
      const cal = document.getElementById('custom-calendar');
      cal.innerHTML = '';
      const dt = new Date(year, month, 1);
      const days = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
      let html = `<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;'>
        <button id='prev-month' style='background:none;border:none;font-size:1.3em;cursor:pointer;'>&lt;</button>
        <span style='font-weight:700;color:#7b5097;'>${dt.toLocaleString('pt-BR', {month:'long'})} ${year}</span>
        <button id='next-month' style='background:none;border:none;font-size:1.3em;cursor:pointer;'>&gt;</button>
      </div>`;
      html += `<div style='display:grid;grid-template-columns:repeat(7,1fr);gap:2px 0;font-weight:bold;'>${days.map(d=>`<span style='color:#bfa464'>${d}</span>`).join('')}</div>`;
      let firstDay = dt.getDay();
      let totalDays = new Date(year, month+1, 0).getDate();
      html += `<div style='display:grid;grid-template-columns:repeat(7,1fr);gap:4px 0;'>`;
      for(let i=0;i<firstDay;i++) html+="<span></span>";
      for(let d=1;d<=totalDays;d++){
  let btnDate = new Date(year, month, d, 0, 0, 0, 0);
  let now = new Date();
  now.setHours(0,0,0,0);
  let isToday = btnDate.getTime() === now.getTime();
  let isPast = btnDate.getTime() < now.getTime();
  let isSunday = btnDate.getDay() === 0;

  let disabled = isPast || isToday || isSunday;

  let dayClass = `cal-day${isToday ? " today" : ""}${disabled ? " cal-disabled" : ""}`;
  html += `<button class='${dayClass}' data-date='${year}-${(month+1).toString().padStart(2,'0')}-${d.toString().padStart(2,'0')}'
    style='margin:2px;padding:9px 0;border:none;background:${isToday||isPast||isSunday?'#eaeaea':'#fff9e2'};border-radius:8px;color:${isSunday?'#bdbdbd':'#756430'};font-size:1.02em;cursor:${disabled?'not-allowed':'pointer'};' 
    ${disabled?'disabled':''}>${d}</button>`;
}
      html += `</div>`;
      cal.innerHTML = html;
      document.getElementById('prev-month').onclick = ()=>renderCalendar(month===0?year-1:year, month===0?11:month-1);
      document.getElementById('next-month').onclick = ()=>renderCalendar(month===11?year+1:year, month===11?0:month+1);
      document.querySelectorAll('.cal-day').forEach(btn=>{
  btn.onclick = function(){
    if (btn.disabled) return; // Não faz nada se o botão está desabilitado
    document.querySelectorAll('.cal-day').forEach(b=>b.style.boxShadow='none');
    this.style.boxShadow='0 0 0 2px #7b5097 inset';
    const val = this.getAttribute('data-date');
    renderSlots(val);
  };
});
    }
    // Slots
    const horariosDisponiveis = {
      '2024-05-22': ["10:00","10:30","12:00","13:30","14:00","15:00"],
      '2024-05-23': ["09:30","11:00","13:00","14:30","16:00"],
      '2024-05-24': ["09:00","10:00","11:30","13:30","16:30"]
    };
    function renderSlots(val){
      const slotsArea = document.getElementById('slots-area');
      slotsArea.innerHTML = '';
      let slots = horariosDisponiveis[val] || ["09:00","10:30","13:00","15:00","16:30"];
      slots.forEach(h => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = 'slot';
        el.textContent = h;
        el.onclick = function() {
          document.querySelectorAll('.slot').forEach(b=>b.classList.remove('selected'));
          this.classList.add('selected');
        };
        slotsArea.appendChild(el);
      });
    }
    const now = new Date();
    renderCalendar(now.getFullYear(), now.getMonth());
  // O estado de login será determinado via AJAX abaixo, então inicialize como false
  let usuarioLogado = false;
  // O código correto para exibir os blocos será executado após a resposta do fetch('getPerfil.php')
    // Função para obter o serviço/tratamento selecionado
function obterServicoSelecionado() {
  const selected = document.querySelectorAll('.treatment.selected');
  if (selected.length === 1) {
    return selected[0].getAttribute('data-id');
  }
  if (selected.length === 2) {
    return selected[0].getAttribute('data-id') + ',' + selected[1].getAttribute('data-id');
  }
  return null;
}

// Função para pegar a data escolhida no calendário
function obterDataSelecionada() {
  // Pega o botão de slot selecionado e lê o atributo data-date do calendário
  const btn = document.querySelector('.cal-day[style*="box-shadow"]');
  return btn ? btn.getAttribute('data-date') : null;
}

// Função para pegar o horário selecionado
function obterHoraSelecionada() {
  const slot = document.querySelector('.slot.selected');
  return slot ? slot.textContent.trim() : null;
}
document.getElementById('btn-agendar').onclick = function(e) {
  e.preventDefault();

  const duracao = document.querySelector('input[name="duracao"]:checked');
  const slot = document.querySelector('.slot.selected');
  const tratamento = obterServicoSelecionado();
  let guestValid = true;

  // Checa se bloco de guest está visível
  const guestVisible = document.getElementById('dados-guest').style.display !== 'none';

  if (guestVisible) {
    guestValid = [...document.querySelectorAll('#dados-guest input[required]')].every(f => f.value.trim() !== "");
    if (!tratamento || !duracao || !slot || !guestValid) {
      alert('Preencha todos os campos obrigatórios para agendar a sessão.');
      return;
    }
  } else {
    if (!tratamento || !duracao || !slot) {
      alert('Preencha todos os campos obrigatórios para agendar a sessão.');
      return;
    }
  }

      // Prepara os dados para envio
      const valor = (() => {
  let v = 0;
  const escaldaInput = document.getElementById('escalda-pes');
  if (duracao.value !== "pacote5" && duracao.value !== "pacote10") {
    v = parseFloat(duracao.dataset.preco);
  }
  if (escaldaInput.checked) v += parseFloat(escaldaInput.dataset.preco);
  return v;
})();
const dados = {
  servico_id: obterServicoSelecionado(),
  data: obterDataSelecionada(),
  hora: obterHoraSelecionada(),
  duracao: duracao.value,
  preco_final: valor // <-- Esse campo é o novo!
};


      // Se o bloco de guest estiver visível (usuário não logado), pega os campos obrigatórios de visitante
      if (document.getElementById('dados-guest').style.display !== 'none') {
          dados.guest_name = document.getElementById('guest-name').value;
          dados.guest_email = document.getElementById('guest-email').value;
          dados.guest_phone = document.getElementById('guest-phone').value;
          dados.guest_nascimento = document.getElementById('guest-nascimento').value;
          dados.guest_sexo = document.getElementById('guest-sexo').value;
          dados.criar_conta = document.getElementById('criar-conta').checked ? 1 : 0;
          if (dados.criar_conta) {
              dados.guest_senha = document.getElementById('guest-senha').value;
              dados.guest_senha2 = document.getElementById('guest-senha2').value;
          }
      }

      // Adicione aqui a coleta dos outros campos do seu formulário!

      // Envia ao PHP (AJAX)
      fetch('agendar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(dados),
        credentials: 'include'
      })
      .then(res => res.text())
      .then(res => {
  if (res.startsWith("SUCESSO")) {
    const partes = res.split('|');
    const agendamentoId = partes[1] || null;

    // Se o formulário de queixa foi preenchido, envie agora
    if (formularioEnviado && agendamentoId) {
      const formData = new FormData(document.getElementById('form-reclamacoes'));
      formData.append('agendamento_id', agendamentoId);
      fetch('salvarQueixa.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });
    }

    // Redireciona para a página de sessão marcada
    window.location.href = 'sessaoMarcada.html';
  } else if (res.includes("HORARIO_OCUPADO")) {
    alert("Horário já ocupado. Escolha outro.");
  } else if (res.includes("DADOS_INCOMPLETOS")) {
    alert("Preencha todos os dados obrigatórios.");
  } else if (res.includes("TERMO_NAO_ACEITO")) {
    alert("Você deve aceitar o termo para agendar.");
  } else if (res.includes("SERVICO_SEM_PRECO")) {
    alert("Serviço sem preço definido. Entre em contato.");
  } else if (res.includes("PRECO_INVALIDO")) {
    alert("Preço inválido para a duração selecionada");
  } else if (res.includes("ERRO_AGENDAR")) {
    alert("Erro ao agendar. Tente novamente.");
  } else {
    alert("Erro desconhecido: " + res);
  }
})
      .catch(erro => {
        alert('Erro ao conectar ao servidor. Tente novamente mais tarde.');
        console.error(erro);
      });
    };
    
  function mostrarAvisoTratamento(msg) {
    let aviso = document.getElementById('aviso-tratamento-toast');
    if (!aviso) {
      aviso = document.createElement('div');
      aviso.id = 'aviso-tratamento-toast';
      aviso.className = 'aviso-tratamento-toast';
      document.body.appendChild(aviso);
    }
    aviso.innerHTML = msg;
    aviso.classList.add('show');
    aviso.style.display = "block";
    // Mobile: gruda no topo, Desktop: mantém no centro inferior
    if (window.innerWidth < 700) {
      aviso.style.top = '10px';
      aviso.style.bottom = '';
      aviso.style.left = '50%';
      aviso.style.transform = 'translateX(-50%)';
    } else {
      aviso.style.top = '';
      aviso.style.bottom = '8vh';
      aviso.style.left = '50%';
      aviso.style.transform = 'translateX(-50%)';
    }
    // Fade-out automático:
    setTimeout(() => {
      aviso.classList.remove('show');
      setTimeout(() => { aviso.style.display = "none"; }, 450);
    }, 2500);
  }

    // Lógica robusta para seleção de até dois tratamentos
    function atualizarHoverBloqueado() {
      const selected = document.querySelectorAll('.treatment.selected');
      const treatments = document.querySelectorAll('.treatment');
      if (selected.length >= 2) {
        treatments.forEach(card => {
          if (!card.classList.contains('selected')) {
            card.classList.add('block-hover');
          }
        });
      } else {
        treatments.forEach(card => {
          card.classList.remove('block-hover');
        });
      }
    }

    // E, após cada clique, sempre rode:
    document.querySelectorAll('.treatment').forEach(card => {
  card.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const selected = document.querySelectorAll('.treatment.selected');
    if (this.classList.contains('selected')) {
      this.classList.remove('selected');
      atualizarHoverBloqueado();
      atualizarDuracoes(); // <-- Aqui!
      return;
    }
    if (selected.length >= 2) {
      mostrarAvisoTratamento(
        "Você só pode escolher até dois tratamentos.<br><span style='font-size:0.98em;font-weight:400;'>Para escolher outro, primeiro retire uma das opções já selecionadas.</span>"
      );
      atualizarHoverBloqueado();
      return;
    }
    this.classList.add('selected');
    atualizarHoverBloqueado();
    atualizarDuracoes(); // <-- Aqui!
  }, {passive: false});
});


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

    window.addEventListener('DOMContentLoaded', function() {
      atualizarDuracoes();
      const servicoEscolhido = localStorage.getItem('servicoSelecionado');
      if (servicoEscolhido) {
        // Considerando que cada tratamento tem um radio input ou card com texto igual ao nome do serviço
        document.querySelectorAll('.treatment, .tratamento, .servico-opcao, label').forEach(el => {
          if (
            el.innerText &&
            el.innerText.trim().toLowerCase() === servicoEscolhido.trim().toLowerCase()
          ) {
            // Caso seja radio:
            if (el.querySelector('input[type="radio"]')) {
              el.querySelector('input[type="radio"]').checked = true;
            }
            // Se for um card ou div que você usa para marcar visualmente:
            el.classList.add('selected');
            // Rola para o tratamento
            el.scrollIntoView({behavior: 'smooth', block: 'center'});
          }
        });
        // Limpa o LocalStorage para não afetar próximos acessos
        localStorage.removeItem('servicoSelecionado');
      }
    });

    function calcularValorFinal() {
      let valor = 0;
      const duracao = document.querySelector('input[name="duracao"]:checked');

      const escaldaInput = document.getElementById('escalda-pes');
      const escalda = escaldaInput.checked ? parseFloat(escaldaInput.dataset.preco) : 0;

      if (duracao && (duracao.value === 'pacote5' || duracao.value === 'pacote10')) {
        valor = 0;
      } else if (duracao) {
        valor = parseFloat(duracao.dataset.preco);
      }
      valor += escalda;

      // Atualiza o valor na tela
      document.getElementById('valor-total').textContent = valor === 0 ? '0,00' : valor.toFixed(2).replace('.', ',');

      // Mensagem de pagamento
      if (valor === 0) {
        document.querySelector('.total-container small').textContent = 
          "O valor desta consulta será debitado do seu pacote. Nenhum pagamento é necessário.";
      } else {
        document.querySelector('.total-container small').textContent =
          "O pagamento deve ser feito no dia da consulta através de Pix, dinheiro, cartão de crédito ou débito.";
      }
    }

    // Atualiza sempre que mudar as opções
    document.querySelectorAll('input[name="duracao"]').forEach(function(radio) {
      radio.addEventListener('change', calcularValorFinal);
    });
    document.getElementById('escalda-pes').addEventListener('change', calcularValorFinal);

    // Chama na inicialização
    calcularValorFinal();
    
    // Ao carregar a página, verifica sessões restantes do usuário
    let sessoesDisponiveis = 0;
    // Função reutilizável
    function atualizarPacoteAgendamento() {
      fetch('getPacoteUsuario.php')
        .then(res => res.json())
        .then((data) => {
          sessoesDisponiveis = data.sessoes_restantes || data.disponiveis || 0;

          const inputPacote = document.getElementById('duracao-pacote');
          if (!inputPacote) return; // Não existe opção de pacote, não faz nada
          const labelPacote = inputPacote.parentElement;

          // Remove info duplicada se recarregar
          const info = document.getElementById('pacote-info-agendamento');
          if (info) info.remove();

          // Exibe ou bloqueia
          if (sessoesDisponiveis > 0) {
            inputPacote.disabled = false;
            inputPacote.value = sessoesDisponiveis > 5 ? 'pacote10' : 'pacote5';
            labelPacote.innerHTML += ` <span id="pacote-info-agendamento" style="color:#30795b;">(${sessoesDisponiveis} sessões restantes)</span>`;
          } else {
            inputPacote.disabled = true;
            labelPacote.innerHTML += ` <span id="pacote-info-agendamento" style="color:#b48c1a;">(Sem sessões de pacote. <a href="pacotes.php" style="text-decoration:underline;">Comprar pacote</a>)</span>`;
            // Botão extra
            let promoDiv = document.querySelector('.promo');
            if (promoDiv && !document.getElementById('btn-comprar-pacote')) {
              promoDiv.innerHTML += `<div style="margin-top:8px;">
                <button id="btn-comprar-pacote" onclick="window.location.href='pacotes.php'" style="background:#b79b63;color:#fff;padding:7px 20px;border-radius:8px;font-size:1rem;border:none;cursor:pointer;">
                  Comprar pacote de sessões
                </button>
              </div>`;
            }
          }
        });
    }
    // Chama ao carregar a página:
    atualizarPacoteAgendamento();


    document.getElementById('criar-conta').addEventListener('change', function() {
      document.getElementById('campo-senhas').style.display = this.checked ? 'block' : 'none';
    });

    // (Opcional, mas recomendável) Validar se as senhas batem antes de enviar:
    document.querySelector('.guest-form').onsubmit = function(e) {
      if (document.getElementById('criar-conta').checked) {
        var senha = document.getElementById('guest-senha').value;
        var senha2 = document.getElementById('guest-senha2').value;
        if (!senha || !senha2 || senha !== senha2) {
          alert('As senhas não coincidem ou estão em branco.');
          document.getElementById('guest-senha').focus();
          e.preventDefault();
          return false;
        }
      }
    };

    // --- Troca "Perfil" ou "Login" no menu, conforme login ---
    fetch('getPerfil.php', { credentials: 'include' })
      .then(res => res.ok ? res.json() : Promise.reject())
      .then(data => {
          if (data.usuario && data.usuario.nome) {
              // Usuário logado: mostra bloco de usuário, esconde visitante
              document.getElementById('dados-usuario').style.display = 'block';
              document.getElementById('dados-guest').style.display = 'none';
          } else {
              // Não logado: mostra bloco de visitante, esconde usuário
              document.getElementById('dados-usuario').style.display = 'none';
              document.getElementById('dados-guest').style.display = 'block';
          }
          // Atualiza o menu também
          if (data.usuario && data.usuario.nome) {
              document.getElementById('li-perfil-login').innerHTML =
                  '<a href="perfil.html"><i class="fa-solid fa-user"></i> Perfil</a>';
          } else {
              document.getElementById('li-perfil-login').innerHTML =
                  '<a href="registrar.html"><i class="fa-solid fa-user"></i> Login</a>';
          }
      })
      .catch(() => {
          // Não logado ou erro de sessão
          document.getElementById('dados-usuario').style.display = 'none';
          document.getElementById('dados-guest').style.display = 'block';
          document.getElementById('li-perfil-login').innerHTML =
              '<a href="registrar.html"><i class="fa-solid fa-user"></i> Login</a>';
      });
    
</script>

</body>
</html>
