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
if ($res instanceof mysqli_result) {
    if ($row = $res->fetch_assoc()) {
        $precos['quick_15'] = $row['preco_15'];
        $precos['quick_30'] = $row['preco_30'];
    }
    $res->free();
} else {
    error_log(mysqli_error($conn));
}

$res = $conn->query("SELECT preco_50, preco_90 FROM especialidades WHERE nome != 'Quick Massage' LIMIT 1");
if ($res instanceof mysqli_result) {
    if ($row = $res->fetch_assoc()) {
        $precos['padrao_50'] = $row['preco_50'];
        $precos['padrao_90'] = $row['preco_90'];
    }
    $res->free();
} else {
    error_log(mysqli_error($conn));
}

$res = $conn->query("SELECT preco_escalda FROM especialidades WHERE nome = 'Escalda Pés' LIMIT 1");
if ($res instanceof mysqli_result) {
    if ($row = $res->fetch_assoc()) {
        $precos['escalda'] = $row['preco_escalda'];
    }
    $res->free();
} else {
    error_log(mysqli_error($conn));
}

$res = $conn->query("SELECT pacote5, pacote10 FROM especialidades");
if ($res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) {
        $precos['pacote5'] = $row['pacote5'];
        $precos['pacote10'] = $row['pacote10'];
    }
    $res->free();
} else {
    error_log(mysqli_error($conn));
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
      background: var(--cor-principal);
      color: #fff;
      padding: 0.8rem 3vw;
      height: 96px;
      min-height: 72px;
      box-shadow: 0 2px 16px #0003;
      z-index: 1000;
    }
    .navbar-logo-central {
      height: 100%;
      display: flex;
      align-items: center;
      padding-top: 24px;
    }
    .navbar-logo-central img {
      height: 220px;
      width: auto;
      max-width: 325px;
      object-fit: contain;
      display: block;
      margin-right: 12px;
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

    /* RESPONSIVO */
    @media (max-width: 1050px) {
      .navbar-menu {
        position: fixed;
        top: 96px;
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
        font-size: 1.2rem;
        line-height: 1.6;
        padding: 0.6rem 0;
        width: 100%;
        font-weight: 700;
      }
    }

    @media (max-width: 700px) {
      .navbar {
        padding: 0.8rem 4vw;
        height: 96px;
        min-height: 72px;
      }
      .navbar-logo-central img {
        height: 120px;
        max-width: 160px;
      }
      .navbar-menu {
        gap: 0.6rem;
        padding: 1.8rem 2vw;
        top: 96px;
      }
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
    .durations label.duracao-disabled {
      opacity: 0.6;
      cursor: not-allowed;
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
    .aviso-disponibilidade {
      display: none;
      width: 100%;
      box-sizing: border-box;
      padding: 10px 14px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.95rem;
    }
    .aviso-disponibilidade.aviso-info {
      background: #e8f4ff;
      color: #0c4a60;
    }
    .aviso-disponibilidade.aviso-alerta {
      background: #fff3cd;
      color: #664d03;
    }
    .aviso-disponibilidade.aviso-erro {
      background: #f8d7da;
      color: #842029;
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
    .cal-day.cal-selected {
      box-shadow: 0 0 0 2px #7b5097 inset !important;
    }
    .slots-empty {
      font-size: 0.95rem;
      color: #7b5097;
      font-weight: 600;
      margin: 6px 0;
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
          <input type="checkbox" id="termo" name="termo">
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
    let sessoesDisponiveis = 0;
    let pacoteId = null;
    const mensagemPagamentoPadraoEl = document.querySelector('.total-container small');
    const mensagemPagamentoPadrao = mensagemPagamentoPadraoEl
      ? mensagemPagamentoPadraoEl.textContent.trim()
      : 'O pagamento deve ser feito no dia da consulta através de Pix, dinheiro, cartão de crédito ou débito.';
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
    
    function obterPrecoNumerico(elemento) {
      if (!elemento) {
        return 0;
      }
      const bruto = elemento.getAttribute('data-preco');
      if (!bruto) {
        return 0;
      }

      const texto = String(bruto).trim();
      if (!texto) {
        return 0;
      }

      const normalizado = texto.includes(',')
        ? texto.replace(/\./g, '').replace(',', '.')
        : texto;

      const numero = Number(normalizado);
      return Number.isFinite(numero) ? numero : 0;
    }

    function formatarValorMonetario(valor) {
      return Number(valor).toFixed(2).replace('.', ',');
    }

    function calcularValorFinal() {
      const servicosSelecionados = getServicosSelecionados();
      const duracaoSelecionada = document.querySelector('input[name="duracao"]:checked');
      const escaldaCheckbox = document.getElementById('escalda-pes');
      const totalSpan = document.getElementById('valor-total');
      const mensagemPagamento = document.querySelector('.total-container small');
      const avisoTratamento = document.getElementById('aviso-tratamento');
      const infoPacote = document.getElementById('info-pacote');

      let valor = 0;
      let usaPacote = false;
      let mensagemAviso = '';
      let mensagemPacote = '';

      if (duracaoSelecionada) {
        usaPacote = duracaoSelecionada.value === 'pacote5' || duracaoSelecionada.value === 'pacote10';
        if (usaPacote) {
          if (pacoteId) {
            const sessoesRestantesDepois = Math.max((sessoesDisponiveis || 0) - 1, 0);
            const palavraSessao = sessoesRestantesDepois === 1 ? 'sessão' : 'sessões';
            mensagemPacote = `Será debitada 1 sessão do seu pacote. Restarão ${sessoesRestantesDepois} ${palavraSessao}.`;
          } else {
            mensagemPacote = 'Nenhum pacote ativo identificado para utilizar esta opção.';
          }
        } else {
          valor = obterPrecoNumerico(duracaoSelecionada);
        }
      }

      let valorEscalda = 0;
      if (escaldaCheckbox && escaldaCheckbox.checked) {
        valorEscalda = obterPrecoNumerico(escaldaCheckbox);
        valor += valorEscalda;
      }

      if (valorEscalda > 0) {
        const mensagemEscalda = `Escalda pés adicionado (+R$ ${formatarValorMonetario(valorEscalda)}).`;
        mensagemAviso = mensagemAviso ? `${mensagemAviso} ${mensagemEscalda}` : mensagemEscalda;
      }

      if (totalSpan) {
        totalSpan.textContent = formatarValorMonetario(valor);
      }

      if (mensagemPagamento) {
        if (usaPacote && pacoteId) {
          mensagemPagamento.textContent = 'O valor desta consulta será debitado do seu pacote. Nenhum pagamento é necessário.';
        } else if (valor > 0) {
          mensagemPagamento.textContent = mensagemPagamentoPadrao;
        } else {
          mensagemPagamento.textContent = mensagemPagamentoPadrao;
        }
      }

      if (avisoTratamento) {
        if (mensagemAviso) {
          avisoTratamento.textContent = mensagemAviso;
          avisoTratamento.style.display = 'block';
        } else {
          avisoTratamento.textContent = '';
          avisoTratamento.style.display = 'none';
        }
      }

      if (infoPacote) {
        if (mensagemPacote) {
          infoPacote.textContent = mensagemPacote;
          infoPacote.style.display = 'block';
        } else {
          infoPacote.textContent = '';
          infoPacote.style.display = 'none';
        }
      }

      return valor;
    }
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
    const calendarSection = document.querySelector('.calendar-section');
    const calendarElement = document.getElementById('custom-calendar');
    const slotsArea = document.getElementById('slots-area');

    if (slotsArea) {
      slotsArea.innerHTML = '<p class="slots-empty">Selecione uma data no calendário para ver os horários disponíveis.</p>';
    }

    const statusChavesDisponibilidade = ['pendente', 'confirmado', 'concluido', 'indisponivel'];

    const agora = new Date();
    const disponibilidadeState = {
      horariosPadrao: [],
      datas: {},
      datasBloqueadas: new Set(),
      carregado: false,
      carregando: false,
      erro: null,
    };

    const calendarioState = {
      ano: agora.getFullYear(),
      mes: agora.getMonth(),
      dataSelecionada: null,
      horarioSelecionado: null,
    };

    let disponibilidadeRefreshTimer = null;

    const avisoDisponibilidade = (() => {
      let aviso = document.getElementById('aviso-disponibilidade');
      if (aviso) {
        return aviso;
      }
      aviso = document.createElement('div');
      aviso.id = 'aviso-disponibilidade';
      aviso.className = 'aviso-disponibilidade';
      if (calendarSection && slotsArea) {
        calendarSection.insertBefore(aviso, slotsArea);
      } else if (calendarSection) {
        calendarSection.appendChild(aviso);
      }
      return aviso;
    })();

    function mostrarAvisoDisponibilidade(mensagem, tipo = 'info') {
      if (!avisoDisponibilidade) {
        return;
      }
      if (!mensagem) {
        avisoDisponibilidade.textContent = '';
        avisoDisponibilidade.style.display = 'none';
        avisoDisponibilidade.className = 'aviso-disponibilidade';
        return;
      }
      const tipoClasse = ['erro', 'alerta', 'info'].includes(tipo) ? tipo : 'info';
      avisoDisponibilidade.textContent = mensagem;
      avisoDisponibilidade.style.display = 'block';
      avisoDisponibilidade.className = `aviso-disponibilidade aviso-${tipoClasse}`;
    }

    function normalizarHorarioSlot(valor) {
      if (valor === null || typeof valor === 'undefined') {
        return null;
      }
      const texto = String(valor).trim();
      if (!texto) {
        return null;
      }
      const match = texto.match(/^(\d{1,2}):(\d{2})/);
      if (!match) {
        return null;
      }
      const horas = match[1].padStart(2, '0');
      const minutos = match[2];
      return `${horas}:${minutos}`;
    }

    function normalizarListaHorarios(lista) {
      if (!Array.isArray(lista)) {
        return [];
      }
      const conjunto = new Set();
      lista.forEach(item => {
        const horario = normalizarHorarioSlot(item);
        if (horario) {
          conjunto.add(horario);
        }
      });
      return Array.from(conjunto).sort();
    }

    function normalizarStatusJS(status) {
      if (!status && status !== 0) {
        return null;
      }
      const texto = String(status)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()
        .toLowerCase();
      switch (texto) {
        case 'pendente':
          return 'pendente';
        case 'confirmado':
          return 'confirmado';
        case 'concluido':
          return 'concluido';
        case 'indisponivel':
          return 'indisponivel';
        default:
          return null;
      }
    }

    function isISODate(valor) {
      if (typeof valor !== 'string') {
        return false;
      }
      if (!/^\d{4}-\d{2}-\d{2}$/.test(valor)) {
        return false;
      }
      const data = new Date(`${valor}T00:00:00`);
      return !Number.isNaN(data.getTime());
    }

    async function carregarDisponibilidade({ mostrarErro = true } = {}) {
      disponibilidadeState.carregando = true;
      try {
        const resposta = await fetch('getDisponibilidade.php', {
          cache: 'no-store',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!resposta.ok) {
          throw new Error('Não foi possível carregar os horários disponíveis no momento.');
        }

        const contentType = resposta.headers.get('Content-Type') || '';
        let payload;
        if (contentType.includes('application/json')) {
          payload = await resposta.json();
        } else {
          const texto = await resposta.text();
          throw new Error(texto || 'Resposta inválida ao carregar disponibilidade.');
        }

        if (!payload || typeof payload !== 'object') {
          throw new Error('Resposta inválida ao carregar disponibilidade.');
        }

        if (payload.success === false) {
          const mensagem = typeof payload.message === 'string' && payload.message.trim()
            ? payload.message.trim()
            : 'Horários indisponíveis no momento.';
          throw new Error(mensagem);
        }

        const dadosDatas = payload.horariosPorData || payload.datas || {};
        const normalizadoDatas = {};

        Object.keys(dadosDatas).forEach(dataISO => {
          const item = dadosDatas[dataISO] || {};
          const mapa = {};
          Object.keys(item).forEach(chave => {
            const status = normalizarStatusJS(chave);
            if (!status) {
              return;
            }
            mapa[status] = normalizarListaHorarios(item[chave]);
          });

          statusChavesDisponibilidade.forEach(status => {
            if (!mapa[status]) {
              mapa[status] = [];
            }
          });

          normalizadoDatas[dataISO] = mapa;
        });

        disponibilidadeState.horariosPadrao = normalizarListaHorarios(payload.horariosPadrao || payload.horarios_padrao || []);
        disponibilidadeState.datas = normalizadoDatas;
        const bloqueios = Array.isArray(payload.datasBloqueadas) ? payload.datasBloqueadas : [];
        disponibilidadeState.datasBloqueadas = new Set(bloqueios.filter(isISODate));
        disponibilidadeState.carregado = true;
        disponibilidadeState.erro = null;

        return disponibilidadeState;
      } catch (erro) {
        disponibilidadeState.erro = erro instanceof Error ? erro : new Error('Não foi possível carregar os horários disponíveis no momento.');
        if (mostrarErro) {
          mostrarAvisoDisponibilidade(disponibilidadeState.erro.message, 'erro');
        }
        if (!disponibilidadeState.carregado) {
          disponibilidadeState.horariosPadrao = [];
          disponibilidadeState.datas = {};
          disponibilidadeState.datasBloqueadas = new Set();
        }
        throw disponibilidadeState.erro;
      } finally {
        disponibilidadeState.carregando = false;
      }
    }

    function obterSlotsDisponiveis(dataISO) {
      if (!dataISO || !Array.isArray(disponibilidadeState.horariosPadrao)) {
        return [];
      }
      if (disponibilidadeState.datasBloqueadas.has(dataISO)) {
        return [];
      }
      const base = disponibilidadeState.horariosPadrao.slice();
      if (!base.length) {
        return [];
      }
      const dadosDia = disponibilidadeState.datas[dataISO];
      if (!dadosDia) {
        return base;
      }
      const ocupados = new Set();
      statusChavesDisponibilidade.forEach(status => {
        const lista = Array.isArray(dadosDia[status]) ? dadosDia[status] : [];
        lista.forEach(hora => ocupados.add(hora));
      });
      return base.filter(hora => !ocupados.has(hora));
    }

    function atualizarSelecaoSlots() {
      document.querySelectorAll('.slot').forEach(btn => {
        const hora = btn.getAttribute('data-time') || btn.textContent.trim();
        if (calendarioState.horarioSelecionado === hora) {
          btn.classList.add('selected');
        } else {
          btn.classList.remove('selected');
        }
      });
    }

    function renderSlots(dataISO, { manterHorarioSelecionado = false } = {}) {
      if (!slotsArea) {
        return;
      }

      const dataNormalizada = typeof dataISO === 'string' ? dataISO.trim() : '';

      if (!dataNormalizada) {
        slotsArea.innerHTML = '<p class="slots-empty">Selecione uma data no calendário para ver os horários disponíveis.</p>';
        return;
      }

      const slotAnterior = manterHorarioSelecionado ? calendarioState.horarioSelecionado : null;
      if (!manterHorarioSelecionado) {
        calendarioState.horarioSelecionado = null;
      }

      const slotsDisponiveis = obterSlotsDisponiveis(dataNormalizada);
      let mensagem = null;

      if (!Array.isArray(slotsDisponiveis) || slotsDisponiveis.length === 0) {
        calendarioState.horarioSelecionado = null;
        slotsArea.innerHTML = '<p class="slots-empty">Não há horários disponíveis para a data selecionada. Escolha outra data.</p>';
        mensagem = 'Não há horários disponíveis para a data selecionada. Escolha outra data.';
      } else {
        slotsArea.innerHTML = '';
        if (slotAnterior && slotsDisponiveis.includes(slotAnterior)) {
          calendarioState.horarioSelecionado = slotAnterior;
        } else if (slotAnterior) {
          calendarioState.horarioSelecionado = null;
          mensagem = 'O horário selecionado não está mais disponível. Escolha outra opção.';
        }

        slotsDisponiveis.forEach(horario => {
          const botao = document.createElement('button');
          botao.type = 'button';
          botao.className = 'slot';
          botao.textContent = horario;
          botao.setAttribute('data-time', horario);
          botao.addEventListener('click', () => {
            calendarioState.horarioSelecionado = horario;
            atualizarSelecaoSlots();
            if (!disponibilidadeState.erro) {
              mostrarAvisoDisponibilidade('');
            }
          });
          slotsArea.appendChild(botao);
        });

        atualizarSelecaoSlots();
      }

      if (mensagem && !disponibilidadeState.erro) {
        mostrarAvisoDisponibilidade(mensagem, 'alerta');
      } else if (!mensagem && !disponibilidadeState.erro) {
        mostrarAvisoDisponibilidade('');
      }
    }

    function renderCalendar(year, month, { manterSelecao = false } = {}) {
      if (!calendarElement) {
        return;
      }

      calendarioState.ano = year;
      calendarioState.mes = month;

      const dataSelecionadaAnterior = manterSelecao ? calendarioState.dataSelecionada : null;
      const horarioSelecionadoAnterior = manterSelecao ? calendarioState.horarioSelecionado : null;

      if (!manterSelecao) {
        calendarioState.dataSelecionada = null;
        calendarioState.horarioSelecionado = null;
      }

      const dt = new Date(year, month, 1);
      const mesLabel = dt.toLocaleString('pt-BR', { month: 'long' });
      const tituloMes = mesLabel.charAt(0).toUpperCase() + mesLabel.slice(1);
      const days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

      let html = `<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;'>
        <button id='prev-month' style='background:none;border:none;font-size:1.3em;cursor:pointer;'>&lt;</button>
        <span style='font-weight:700;color:#7b5097;'>${tituloMes} ${year}</span>
        <button id='next-month' style='background:none;border:none;font-size:1.3em;cursor:pointer;'>&gt;</button>
      </div>`;

      html += `<div style='display:grid;grid-template-columns:repeat(7,1fr);gap:2px 0;font-weight:bold;'>${days.map(d => `<span style='color:#bfa464'>${d}</span>`).join('')}</div>`;
      html += "<div style='display:grid;grid-template-columns:repeat(7,1fr);gap:4px 0;'>";

      const firstDay = dt.getDay();
      const totalDays = new Date(year, month + 1, 0).getDate();
      for (let i = 0; i < firstDay; i++) {
        html += '<span></span>';
      }

      const hoje = new Date();
      hoje.setHours(0, 0, 0, 0);

      for (let dia = 1; dia <= totalDays; dia++) {
        const dataISO = `${year}-${String(month + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
        const dataBtn = new Date(year, month, dia);
        dataBtn.setHours(0, 0, 0, 0);
        const isToday = dataBtn.getTime() === hoje.getTime();
        const isPast = dataBtn.getTime() < hoje.getTime();
        const isSunday = dataBtn.getDay() === 0;
        const slotsDisponiveis = obterSlotsDisponiveis(dataISO);
        const diaBloqueado = disponibilidadeState.datasBloqueadas.has(dataISO);
        const semSlots = disponibilidadeState.carregado && slotsDisponiveis.length === 0;
        const semInformacao = !disponibilidadeState.carregado;
        const disabled = semInformacao || isPast || isToday || isSunday || diaBloqueado || semSlots;

        let dayClass = 'cal-day';
        if (isToday) {
          dayClass += ' today';
        }
        if (disabled) {
          dayClass += ' cal-disabled';
        }
        if (calendarioState.dataSelecionada === dataISO) {
          dayClass += ' cal-selected';
        }

        const background = disabled ? '#eaeaea' : '#fff9e2';
        const color = isSunday ? '#bdbdbd' : '#756430';
        const cursor = disabled ? 'not-allowed' : 'pointer';

        html += `<button class='${dayClass}' data-date='${dataISO}' data-available-slots='${slotsDisponiveis.length}' style='margin:2px;padding:9px 0;border:none;background:${background};border-radius:8px;color:${color};font-size:1.02em;cursor:${cursor};' ${disabled ? 'disabled' : ''}>${dia}</button>`;
      }

      html += '</div>';
      calendarElement.innerHTML = html;

      const prevBtn = calendarElement.querySelector('#prev-month');
      const nextBtn = calendarElement.querySelector('#next-month');

      if (prevBtn) {
        prevBtn.addEventListener('click', () => navegarMes(-1));
      }
      if (nextBtn) {
        nextBtn.addEventListener('click', () => navegarMes(1));
      }

      calendarElement.querySelectorAll('.cal-day').forEach(btn => {
        btn.addEventListener('click', () => {
          if (btn.disabled) {
            return;
          }
          calendarioState.dataSelecionada = btn.getAttribute('data-date');
          calendarioState.horarioSelecionado = null;
          calendarElement.querySelectorAll('.cal-day').forEach(b => b.classList.remove('cal-selected'));
          btn.classList.add('cal-selected');
          renderSlots(calendarioState.dataSelecionada);
        });
      });

      if (manterSelecao && dataSelecionadaAnterior) {
        const btnSelecionado = calendarElement.querySelector(`.cal-day[data-date='${dataSelecionadaAnterior}']`);
        if (btnSelecionado && !btnSelecionado.disabled) {
          calendarioState.dataSelecionada = dataSelecionadaAnterior;
          calendarioState.horarioSelecionado = horarioSelecionadoAnterior;
          btnSelecionado.classList.add('cal-selected');
          renderSlots(calendarioState.dataSelecionada, { manterHorarioSelecionado: true });
        } else {
          if (!disponibilidadeState.erro) {
            mostrarAvisoDisponibilidade('A data selecionada não está mais disponível. Escolha uma nova data.', 'alerta');
          }
          calendarioState.dataSelecionada = null;
          calendarioState.horarioSelecionado = null;
          if (slotsArea) {
            slotsArea.innerHTML = '<p class="slots-empty">Selecione uma data no calendário para ver os horários disponíveis.</p>';
          }
        }
      } else if (!manterSelecao && slotsArea) {
        slotsArea.innerHTML = '<p class="slots-empty">Selecione uma data no calendário para ver os horários disponíveis.</p>';
      }
    }

    function navegarMes(delta) {
      let ano = calendarioState.ano;
      let mes = calendarioState.mes + delta;

      if (mes < 0) {
        mes = 11;
        ano -= 1;
      } else if (mes > 11) {
        mes = 0;
        ano += 1;
      }

      calendarioState.ano = ano;
      calendarioState.mes = mes;
      calendarioState.dataSelecionada = null;
      calendarioState.horarioSelecionado = null;

      mostrarAvisoDisponibilidade('Atualizando horários disponíveis...', 'info');

      carregarDisponibilidade()
        .catch(erro => {
          console.error('Erro ao carregar disponibilidade:', erro);
        })
        .finally(() => {
          if (!disponibilidadeState.erro) {
            mostrarAvisoDisponibilidade('');
          }
          renderCalendar(ano, mes);
        });
    }

    function passoHorarioVisivel() {
      const passo = document.getElementById('step-horario');
      if (!passo) {
        return false;
      }
      if (passo.offsetParent === null) {
        return false;
      }
      const estilo = window.getComputedStyle(passo);
      return estilo.display !== 'none' && estilo.visibility !== 'hidden' && Number(estilo.opacity || '1') > 0;
    }

    function iniciarAtualizacaoPeriodica() {
      if (disponibilidadeRefreshTimer) {
        clearInterval(disponibilidadeRefreshTimer);
      }
      disponibilidadeRefreshTimer = setInterval(() => {
        if (!passoHorarioVisivel()) {
          return;
        }
        carregarDisponibilidade()
          .catch(erro => {
            console.error('Erro ao atualizar disponibilidade:', erro);
          })
          .finally(() => {
            if (!disponibilidadeState.erro) {
              mostrarAvisoDisponibilidade('');
            }
            renderCalendar(calendarioState.ano, calendarioState.mes, { manterSelecao: true });
            if (calendarioState.dataSelecionada) {
              renderSlots(calendarioState.dataSelecionada, { manterHorarioSelecionado: true });
            }
          });
      }, 60000);
    }

    async function inicializarCalendario() {
      if (!calendarElement) {
        return;
      }
      mostrarAvisoDisponibilidade('Carregando horários disponíveis...', 'info');
      try {
        await carregarDisponibilidade();
        mostrarAvisoDisponibilidade('');
      } catch (erro) {
        console.error('Erro ao carregar disponibilidade:', erro);
      } finally {
        renderCalendar(calendarioState.ano, calendarioState.mes);
        if (!disponibilidadeState.erro) {
          mostrarAvisoDisponibilidade('');
        }
      }
      iniciarAtualizacaoPeriodica();
    }

    inicializarCalendario();
  // O estado de login será determinado via AJAX abaixo, então inicialize como false
  let usuarioLogado = false;
  // O código correto para exibir os blocos será executado após a resposta do fetch('getPerfil.php')
      
    // Função para obter até dois serviços selecionados
    function getServicosSelecionados() {
      return Array.from(document.querySelectorAll('.treatment.selected'))
        .map(card => card.dataset.id)
        .filter(Boolean)
        .slice(0, 2);
    }

    function obterNomeServicoPorId(id) {
      if (!id) {
        return '';
      }

      const card = document.querySelector(`.treatment[data-id="${id}"]`);
      if (!card) {
        return '';
      }

      const rotulo = card.querySelector('span');
      return rotulo ? rotulo.textContent.trim().toLowerCase() : '';
    }

    function determinarServicoPrincipal(ids, duracaoSelecionada) {
      if (!Array.isArray(ids) || ids.length === 0) {
        return '';
      }

      if (ids.length === 1) {
        return ids[0];
      }

      const duracao = String(duracaoSelecionada || '').trim();
      const duracoesQuick = new Set(['15', '30']);
      const duracoesNaoQuick = new Set(['50', '90', 'pacote5', 'pacote10']);

      const quickId = ids.find(id => obterNomeServicoPorId(id) === 'quick massage');
      const naoQuickId = ids.find(id => obterNomeServicoPorId(id) !== 'quick massage');

      if (duracoesQuick.has(duracao) && quickId) {
        return quickId;
      }

      if (duracoesNaoQuick.has(duracao) && naoQuickId) {
        return naoQuickId;
      }

      if (!duracao && naoQuickId) {
        return naoQuickId;
      }

      return quickId || naoQuickId || ids[0];
    }
    const avisoTratamentoToast = (() => {
      let toast = document.getElementById('aviso-tratamento-toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'aviso-tratamento-toast';
        document.body.appendChild(toast);
      }
      return toast;
    })();
    let avisoTratamentoToastTimeout;

    function mostrarAvisoLimiteTratamentos() {
      if (!avisoTratamentoToast) {
        alert('Você pode selecionar no máximo dois tratamentos.');
        return;
      }

      avisoTratamentoToast.textContent = 'Você pode selecionar no máximo dois tratamentos.';
      avisoTratamentoToast.style.top = '24px';
      avisoTratamentoToast.style.transform = 'translateX(-50%)';
      avisoTratamentoToast.classList.add('show');

      clearTimeout(avisoTratamentoToastTimeout);
      avisoTratamentoToastTimeout = setTimeout(() => {
        avisoTratamentoToast.classList.remove('show');
      }, 2500);
    }

    function atualizarEstadoBlockHover() {
      const totalSelecionados = getServicosSelecionados().length;
      document.querySelectorAll('.treatment').forEach(card => {
        if (totalSelecionados >= 2 && !card.classList.contains('selected')) {
          card.classList.add('block-hover');
        } else {
          card.classList.remove('block-hover');
        }
      });
    }

    document.querySelectorAll('.treatment').forEach(card => {
      card.addEventListener('click', () => {
        const jaSelecionado = card.classList.contains('selected');

        if (jaSelecionado) {
          card.classList.remove('selected');
        } else {
          const selecionados = getServicosSelecionados();
          if (selecionados.length >= 2) {
            mostrarAvisoLimiteTratamentos();
            return;
          }
          card.classList.add('selected');
        }

        atualizarEstadoBlockHover();
        atualizarDuracoes();
      });
    });

    atualizarEstadoBlockHover();

    // Função para pegar a data escolhida no calendário
    function obterDataSelecionada() {
      return calendarioState.dataSelecionada;
    }

    // Função para pegar o horário selecionado
    function obterHoraSelecionada() {
      return calendarioState.horarioSelecionado;
    }
    document.getElementById('btn-agendar').onclick = function(e) {
      e.preventDefault();
              const duracaoSelecionada = document.querySelector('input[name="duracao"]:checked');
      const horaSelecionada = obterHoraSelecionada();
      const dataSelecionada = obterDataSelecionada();
      const servicosSelecionados = getServicosSelecionados();
      let guestValid = true;

      // Checa se bloco de guest está visível
      const guestVisible = document.getElementById('dados-guest').style.display !== 'none';
      if (guestVisible) {
        guestValid = [...document.querySelectorAll('#dados-guest input[required]')].every(f => f.value.trim() !== "");
      }

      if (
        !servicosSelecionados.length ||
        !duracaoSelecionada ||
        !horaSelecionada ||
        !dataSelecionada ||
        (guestVisible && !guestValid)
      ) {
        alert('Por favor, selecione serviço, data, horário e duração, preenchendo todos os campos obrigatórios.');
        return;
      }

      // Prepara os dados para envio
      const dados = {
        data: dataSelecionada,
        hora: horaSelecionada
      };

      const servicoPrincipal = determinarServicoPrincipal(servicosSelecionados, duracaoSelecionada.value);
      dados.servico_id = String(servicoPrincipal);
      dados.duracao = duracaoSelecionada.value;

      if (servicosSelecionados.length === 2) {
        dados.servicos = servicosSelecionados.join(',');
      }

      if (duracaoSelecionada.value === 'pacote5' || duracaoSelecionada.value === 'pacote10') {
        if (!pacoteId) {
          alert('Não foi possível identificar um pacote ativo para este agendamento.');
          return;
        }
        dados.usou_pacote = 1;
        dados.pacote_id = String(pacoteId);
      }
      // Extra opcional: Escalda Pés  (mantemos a chave add_reflexo para não quebrar o PHP)
      const escaldaEl = document.getElementById('escalda-pes');
      if (escaldaEl && escaldaEl.checked) {
        dados.add_reflexo = 1;
      }

      // Termo (se existir no formulário)
      const termoEl = document.getElementById('termo');
      if (termoEl && termoEl.checked) {
        dados.termo = 1;
      }

      // Se o bloco de guest estiver visível (usuário não logado), pega os campos obrigatórios de visitante
      if (guestVisible) {
        dados.guest_name = document.getElementById('guest-name').value;
        dados.guest_email = document.getElementById('guest-email').value;
        dados.guest_phone = document.getElementById('guest-phone').value;
        dados.guest_nascimento = document.getElementById('guest-nascimento').value;
        dados.guest_sexo = document.getElementById('guest-sexo').value;
        const criarContaMarcado = document.getElementById('criar-conta').checked;
        dados.criar_conta = criarContaMarcado ? 1 : 0;
        if (criarContaMarcado) {
          const senha = document.getElementById('guest-senha').value;
          const senha2 = document.getElementById('guest-senha2').value;
          if (!senha || !senha2) {
            alert('Por favor, preencha ambos os campos de senha.');
            return;
          }
          if (senha !== senha2) {
            alert('As senhas informadas não coincidem.');
            return;
          }
          dados.guest_senha = senha;
          dados.guest_senha2 = senha2;
        }
      }

      // Neste ponto os dados estão montados e prontos para envio ao servidor.
              const btnAgendar = document.getElementById('btn-agendar');
      const textoOriginalBotao = btnAgendar ? btnAgendar.textContent : '';
      if (btnAgendar) {
        btnAgendar.disabled = true;
        btnAgendar.textContent = 'Agendando...';
      }

      const detectarCodigoResposta = (texto) => {
        if (!texto) {
          return null;
        }
        const normalizado = String(texto)
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toUpperCase();
        if (normalizado.includes('HORARIO_INDISPONIVEL')) {
          return 'HORARIO_INDISPONIVEL';
        }
        return null;
      };

      const interpretarResposta = (payload) => {
        if (typeof payload === 'string') {
          const mensagem = payload.trim();
          const sucesso = mensagem.startsWith('SUCESSO');
          const partes = mensagem.split('|');
          return {
            mensagem,
            sucesso,
            id: partes.length > 1 ? partes[1] : null,
            codigo: detectarCodigoResposta(mensagem)
          };
        }

        if (payload && typeof payload === 'object') {
          const status = payload.status || payload.result || payload.sucesso || '';
          const rawMensagem = payload.message || payload.mensagem || payload.error || '';
          const idResposta = payload.id_agendamento ?? payload.id ?? payload.agendamento_id ?? null;
          const sucesso = typeof status === 'string' && status.toUpperCase().startsWith('SUCESSO');
          let mensagem = '';

          if (typeof status === 'string' && status.length) {
            mensagem = status;
          }

          if (rawMensagem) {
            mensagem = mensagem ? `${mensagem}|${rawMensagem}` : rawMensagem;
          }

          if (!mensagem) {
            mensagem = sucesso ? 'SUCESSO' : 'ERRO_AGENDAR: resposta inesperada do servidor.';
          }

          if (sucesso && idResposta && !mensagem.includes('|')) {
            mensagem = `${mensagem}|${idResposta}`;
          }

          return {
            mensagem,
            sucesso,
            id: idResposta,
            codigo: detectarCodigoResposta(`${status}|${rawMensagem}`)
          };
        }

        return {
          mensagem: 'ERRO_AGENDAR: resposta inesperada do servidor.',
          sucesso: false,
          id: null,
          codigo: detectarCodigoResposta('ERRO_AGENDAR: resposta inesperada do servidor.')
        };
      };

      fetch('agendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(dados),
        credentials: 'include'
      })
        .then(response => {
          const contentType = response.headers.get('Content-Type') || '';
          const parsePromise = contentType.includes('application/json') ? response.json() : response.text();
          return parsePromise.then(payload => ({ payload, response }));
        })
        .then(({ payload }) => {
          const { mensagem, sucesso, id, codigo } = interpretarResposta(payload);

          if (codigo === 'HORARIO_INDISPONIVEL') {
            const avisoHorario = 'O horário escolhido acabou de ficar indisponível. Atualizando horários disponíveis...';
            alert(avisoHorario);
            mostrarAvisoDisponibilidade(avisoHorario, 'alerta');
            calendarioState.horarioSelecionado = null;
            return carregarDisponibilidade({ mostrarErro: false })
              .catch((erro) => {
                console.error('Erro ao atualizar disponibilidade após conflito de horário:', erro);
              })
              .finally(() => {
                if (!disponibilidadeState.erro) {
                  mostrarAvisoDisponibilidade('Selecione um novo horário disponível.', 'alerta');
                }
                renderCalendar(calendarioState.ano, calendarioState.mes, { manterSelecao: true });
                if (calendarioState.dataSelecionada) {
                  renderSlots(calendarioState.dataSelecionada, { manterHorarioSelecionado: false });
                }
              });
          }

          if (mensagem) {
            alert(mensagem);
          }

          if (sucesso) {
            if (id) {
              window.location.href = `sessaoMarcada.html?id=${encodeURIComponent(id)}`;
            } else {
              window.location.reload();
            }
          }
        })
        .catch((erro) => {
          console.error('Erro ao agendar sessão:', erro);
          alert('ERRO_AGENDAR: Ocorreu um problema ao finalizar seu agendamento. Tente novamente em instantes.');
        })
        .finally(() => {
          if (btnAgendar) {
            btnAgendar.disabled = false;
            btnAgendar.textContent = textoOriginalBotao;
          }
        });
    };

    // Atualiza sempre que mudar as opções
    document.querySelectorAll('input[name="duracao"]').forEach(function(radio) {
      radio.addEventListener('change', calcularValorFinal);
    });
    const escaldaCheckbox = document.getElementById('escalda-pes');
    if (escaldaCheckbox) {
      escaldaCheckbox.addEventListener('change', calcularValorFinal);
    }

    // Chama na inicialização
    calcularValorFinal();
    
    // Função reutilizável
    function atualizarPacoteAgendamento() {
      fetch('getPacoteUsuario.php')
        .then(res => res.json())
        .then((data) => {
          const sessoesRestantesBrutas =
            typeof data.sessoes_restantes !== 'undefined'
              ? Number(data.sessoes_restantes)
              : Number(data.disponiveis ?? 0);
          sessoesDisponiveis = Number.isFinite(sessoesRestantesBrutas) ? sessoesRestantesBrutas : 0;
          const pacoteIdBruto = typeof data.pacote_id !== 'undefined' ? Number(data.pacote_id) : null;
          pacoteId = Number.isFinite(pacoteIdBruto) && pacoteIdBruto > 0 ? pacoteIdBruto : null;

          const inputPacote = document.getElementById('duracao-pacote');
          if (!inputPacote) return; // Não existe opção de pacote, não faz nada
          const labelPacote = inputPacote.parentElement;
          // Remove info duplicada se recarregar
          const info = document.getElementById('pacote-info-agendamento');
          if (info) info.remove();

          // Exibe ou bloqueia
          if (sessoesDisponiveis > 0 && pacoteId) {
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
              const telefoneValido = data.usuario.telefone && String(data.usuario.telefone).trim() !== '';
              const nascimentoValido = data.usuario.nascimento && data.usuario.nascimento !== '0000-00-00';
              const sexoValido = data.usuario.sexo && String(data.usuario.sexo).trim() !== '';
              const precisaCompletar = !telefoneValido || !nascimentoValido || !sexoValido;
              if (precisaCompletar) {
                  window.location.href = 'perfil.html?completar=1';
                  return;
              }
          }
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
