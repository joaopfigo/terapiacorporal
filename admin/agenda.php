<?php
// agenda.php corrigido para MySQLi e compatível com o dump 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../conexao.php';
require_once __DIR__ . '/../lib/booking_helpers.php';
// Função auxiliar para labels 
function getStatusLabel($status)
{
  switch (strtolower($status)) {
    case 'pendente':
      return 'Pendente';
    case 'confirmado':
      return 'Confirmado';
    case 'concluido':
      return 'Concluído';
    case 'cancelado':
      return 'Cancelado';
    case 'indisponivel':
      return 'Indisponível';
    case 'recusado':
      return 'Recusado';
    default:
      return ucfirst($status);
  }
}
// ----------- AÇÕES DE STATUS ----------- 
if (isset($_GET['confirmar'])) {
  $id = (int) $_GET['confirmar'];
  $stmt = $conn->prepare("UPDATE agendamentos SET status = 'Confirmado' WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  header('Location: agenda.php');
  exit;
}
if (isset($_GET['recusar'])) {
  $id = (int) $_GET['recusar'];
  $stmt = $conn->prepare("UPDATE agendamentos SET status = 'Recusado' WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  header('Location: agenda.php');
  exit;
}
if (isset($_GET['cancelar'])) {
  $id = (int) $_GET['cancelar'];
  $stmt = $conn->prepare("UPDATE agendamentos SET status = 'Cancelado' WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  header('Location: agenda.php');
  exit;
}
if (isset($_GET['concluir'])) {
  $id = (int) $_GET['concluir'];
  $stmt = $conn->prepare("UPDATE agendamentos SET status = 'Concluido' WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  header('Location: agenda.php');
  exit;
}
// ----------- AUTOCOMPLETE AJAX ----------- 
if (isset($_GET['autocomplete_usuario'])) {
  $term = '%' . $_GET['autocomplete_usuario'] . '%';
  $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE nome LIKE ? ORDER BY nome LIMIT 10");
  $stmt->bind_param('s', $term);
  $stmt->execute();
  $result = $stmt->get_result();
  $usuarios = [];
  while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
  }
  header('Content-Type: application/json');
  echo json_encode($usuarios);
  exit;
}

// ----------- NOVO AGENDAMENTO ----------- 
if (isset($_POST['novo_agendamento'])) {
  $usuario_id = (int) $_POST['usuario_id'];
  if (!$usuario_id) {
    echo "<script>alert('Selecione um usuário válido!');window.location='agenda.php';</script>";
    exit;
  }
  $data = $_POST['data'];
  $hora = $_POST['hora'];
  $especialidade_id = $_POST['especialidade_id'] ?? null;
  $duracao = $_POST['duracao'] ?? 0;
  $adicional_reflexo = $_POST['adicional_reflexo'] ?? 0;
  $data_horario = $data . ' ' . $hora;
  $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, adicional_reflexo, status) VALUES (?, ?, ?, ?, ?, 'Pendente')");
  $stmt->bind_param('iisii', $usuario_id, $especialidade_id, $data_horario, $duracao, $adicional_reflexo);
  $stmt->execute();
  $stmt->close();
  header('Location: agenda.php');
  exit;
}
// ----------- BLOQUEAR HORÁRIO ----------- 
if (isset($_POST['bloquear_horario'])) {
  $data = $_POST['data_bloqueio'];
  $hora = $_POST['hora_bloqueio'];
  $data_horario = $data . ' ' . $hora;
  $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, data_horario, status) VALUES (NULL, ?, 'Indisponivel')");
  $stmt->bind_param('s', $data_horario);
  $stmt->execute();
  $stmt->close();
  header('Location: agenda.php');
  exit;
}
// ----------- AGENDAMENTO FIXO ----------- 
if (isset($_POST['agendamento_fixo'])) {
  $usuario_id = (int) $_POST['usuario_id_fixo'];
  if (!$usuario_id) {
    echo "<script>alert('Selecione um usuário válido!');window.location='agenda.php';</script>";
    exit;
  }
  $data = $_POST['data_fixa'];
  $hora = $_POST['hora_fixa'];
  $especialidade_id = $_POST['especialidade_id_fixo'] ?? null;
  $duracao = $_POST['duracao_fixo'] ?? 0;
  $adicional_reflexo = $_POST['adicional_reflexo_fixo'] ?? 0;
  $data_horario = $data . ' ' . $hora;
  $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, adicional_reflexo, status) VALUES (?, ?, ?, ?, ?, 'Confirmado')");
  $stmt->bind_param('iisii', $usuario_id, $especialidade_id, $data_horario, $duracao, $adicional_reflexo);
  $stmt->execute();
  $stmt->close();
  header('Location: agenda.php');
  exit;
}
// ----------- BLOQUEAR DIA INTEIRO ----------- 
if (isset($_POST['bloquear_dia_inteiro'])) {
  $data = $_POST['data_bloqueio'];
  for ($h = 8; $h <= 20; $h++) {
    $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
    $data_horario = $data . ' ' . $hora;
    $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, data_horario, status) VALUES (NULL, ?, 'Indisponivel')");
    $stmt->bind_param('s', $data_horario);
    $stmt->execute();
    $stmt->close();
  }
  header('Location: agenda.php');
  exit;
}

// ----------- LISTAGEM GERAL ----------- 
$query = "SELECT a.*, u.nome as usuario_nome, e.nome as servico_nome 
FROM agendamentos a 
LEFT JOIN usuarios u ON a.usuario_id = u.id 
LEFT JOIN especialidades e ON a.especialidade_id = e.id 
ORDER BY a.data_horario DESC";
$result = $conn->query($query);
$agendamentos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
foreach ($agendamentos as &$ag) {
  [$tituloServicos, $listaServicos] = descreverServicos(
    $conn,
    isset($ag['especialidade_id']) ? (int)$ag['especialidade_id'] : 0,
    $ag['servicos_csv'] ?? null,
    $ag['servico_nome'] ?? ''
  );
  $ag['servico_exibicao'] = $tituloServicos;
  $ag['servicos_lista'] = $listaServicos;
}
unset($ag);
// Separar eventos para o calendário 
$eventos_calendario = [];
foreach ($agendamentos as $a) {
  if (in_array(strtolower($a['status']), ['confirmado', 'concluido'])) {
    $title = $a['usuario_nome'] ? $a['usuario_nome'] : 'Indisponível';
    $eventos_calendario[] = [
      'title' => $title,
      'start' => $a['data_horario'],
      'url' => '#agendamento-' . $a['id'],
    ];
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <link rel="icon" type="image/png" href="/favicon-transparente.png">
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --bg-gradient: linear-gradient(135deg, #f6f8f8 0%, #e6f3f3 70%, #ffecdb 100%);
      --header-bg: #256d54;
      --header-shadow: 0 6px 30px #256d5412, 0 2px 10px #1d9a7718;
      --card-bg: #fff;
      --card-radius: 2.1rem;
      --card-shadow: 0 10px 42px #256d5433, 0 3px 10px #d2e6e47d;
      --accent: #ffd972;
      --success: #44b67b;
      --danger: #e25b5b;
      --pending: #ffeebe;
      --confirmado: #d2f3e2;
      --concluido: #c1eaff;
      --cancelado: #ffe1e1;
      --recusado: #ececec;
      --text-main: #23423b;
      --text-light: #8ea79c;
      --action-hover: #e9faef;
    }

    body {
      background: var(--bg-gradient) fixed;
      min-height: 100vh;
      font-family: 'Roboto', Arial, sans-serif;
      color: var(--text-main);
      margin: 0;
      padding-bottom: 54px;
      /* margem no fim da página */
    }

    .header-admin {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 63px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--header-bg);
      color: #fff;
      font-family: 'Playfair Display', serif;
      font-size: 1.27rem;
      box-shadow: var(--header-shadow);
      z-index: 999;
      padding: 0 4vw;
    }

    .header-admin .menu-horizontal {
      display: flex;
      gap: 14px;
    }

    .header-admin .menu-btn {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.09rem;
      font-weight: 600;
      padding: 8px 17px 7px 17px;
      border-radius: 12px;
      transition: background .17s, color .14s;
      cursor: pointer;
    }

    .header-admin .menu-btn.active,
    .header-admin .menu-btn:hover {
      background: var(--accent);
      color: #256d54;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      padding: 73px 4vw 42px 4vw;
      /* <-- ESSA LINHA GARANTE ESPAÇO PRO HEADER FIXO */
      background: #fff;
      /* ou mantenha seu padrão de fundo */
      border-radius: 22px;
      box-shadow: 0 7px 30px #1d9a7740;
    }

    @media (max-width: 700px) {
      .container {
        max-width: 100vw;
        margin: 69px auto 0 auto;
        padding: 0 1vw 24vw 1vw;
      }

      .header-admin {
        font-size: 1.07rem;
        height: 49px;
        border-radius: 0 0 16px 16px;
        padding: 0 2vw;
      }
    }

    .section-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.45rem;
      color: #2c8c61;
      margin: 0 0 27px 0;
      font-weight: 700;
      letter-spacing: .5px;
      text-shadow: 0 1px 0 #f6f8f8;
    }

    /* --- LISTA DE AGENDAMENTOS NOVO ESTILO --- */
    .agendas-list {
      display: flex;
      flex-direction: column;
      gap: 0;
      /* Tira espaço entre os cards */
      margin-left: 4vw;
      margin-right: 4vw;
      padding: 0;
      border: 1px solid #e1e8e7;
      /* Borda geral da tabela */
      border-radius: 0;
      box-shadow: 0 3px 20px #e1e8e74c;
      background: #fff;
      overflow-x: auto;
    }

    .agenda-card {
      background: none;
      border-radius: 0;
      box-shadow: none;
      border-bottom: 1px solid #e1e8e7;
      border-left: none;
      border-right: none;
      border-top: none;
      padding: 14px 0;
      min-height: 54px;
      display: flex;
      align-items: stretch;
      flex-direction: row;
      gap: 0;
      transition: background .16s;
      font-size: 1.09rem;
      margin: 0;
    }

    .ag-card-body {
      flex: 3 3 270px;
      gap: 32px;
      background: none;
      font-size: 1.09rem;
      display: flex;
      align-items: center;
    }

    .agenda-card:last-child {
      border-bottom: none;
    }

    /* Cada “célula” do card (header, body, actions) parece uma coluna */
    .ag-card-header,
    .ag-card-body,
    .ag-card-actions {
      display: flex;
      align-items: center;
      padding: 8px 10px;
      font-size: 0.97rem;
      border: none;
      margin: 0;
      min-width: 0;
    }

    .ag-card-header {
      flex: 2 2 180px;
      font-family: 'Roboto', Arial, sans-serif;
      font-size: 1rem;
      color: #256d54;
      font-weight: 700;
      gap: 8px;
    }

    .ag-card-title {
      font-size: 1rem;
      font-weight: 600;
      color: #256d54;
      display: block;
    }

    .ag-card-status {
      min-width: 92px;
      font-size: .98rem;
      font-weight: 600;
      padding: 5px 13px;
      border-radius: 5px;
      text-align: center;
      margin-left: 6px;
      background: var(--pending);
      color: #8f6700;
      border: none;
    }

    .agenda-card[data-status="pendente"] .ag-card-status {
      background: var(--pending);
      color: #8f6700;
    }

    .agenda-card[data-status="confirmado"] .ag-card-status {
      background: var(--success);
      color: #1b5c41;
    }

    .agenda-card[data-status="concluido"] .ag-card-status {
      background: var(--concluido);
      color: #155077;
    }

    .agenda-card[data-status="cancelado"] .ag-card-status {
      background: var(--danger);
      color: #a00d1d;
    }

    .agenda-card[data-status="recusado"] .ag-card-status {
      background: #cacaca;
      color: #555;
    }

    .agenda-card[data-status="indisponivel"] .ag-card-status {
      background: #f0f0f0;
      color: #b38b8b;
    }

    /* Corpo da “linha” bem enxuto, imitando célula */
    .ag-card-body {
      flex: 2 2 210px;
      gap: 22px;
      background: none;
      font-size: .97rem;
    }

    .ag-card-label {
      font-weight: 500;
      color: #8da59e;
      margin-right: 2px;
    }

    .ag-card-value {
      font-weight: 600;
      color: #23423b;
      margin-right: 15px;
    }

    /* Ações como célula do fim da linha */
    .ag-card-actions {
      flex: 0 0 178px;
      gap: 9px;
      justify-content: flex-end;
      align-items: center;
      height: 100%;
    }

    .ag-card-actions a,
    .ag-card-actions button {
      border: none;
      background: #256d54;
      color: #fff;
      border-radius: 6px;
      padding: 6px 17px;
      font-weight: 600;
      font-size: 0.99rem;
      cursor: pointer;
      text-decoration: none;
      box-shadow: none;
      transition: background .13s, color .14s;
      outline: none;
    }

    @media (max-width: 600px) {
      .agendas-list {
        margin-left: 2vw;
        margin-right: 2vw;
      }

      .agenda-card {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px;
        padding: 16px 0;
      }

      .ag-card-header,
      .ag-card-body {
        flex: 1 1 50%;
        min-width: 240px;
        align-items: flex-start;
      }

      .ag-card-header {
        gap: 10px;
        flex-wrap: wrap;
      }

      .ag-card-status {
        margin-left: 0;
        text-align: left;
      }

      .ag-card-body {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 12px;
      }

      .ag-card-actions {
        order: 3;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
      }

      .ag-card-actions a,
      .ag-card-actions button {
        width: 100%;
        text-align: center;
      }
    }

    .ag-card-actions a:hover,
    .ag-card-actions button:hover {
      background: var(--accent);
      color: #256d54;
    }

    ::-webkit-scrollbar {
      width: 8px;
      background: #e2f7f2;
    }

    ::-webkit-scrollbar-thumb {
      background: #b0e8d7;
      border-radius: 10px;
    }

    /* Estilo para <h1> e títulos centrais grandes */
    h1,
    .dashboard-title {
      text-align: center;
      font-family: 'Playfair Display', serif;
      color: #256d54;
      font-size: 2.4rem;
      font-weight: 700;
      margin-top: 20px;
      margin-bottom: 10px;
      letter-spacing: .6px;
      text-shadow: 0 2px 0 #fafafc;
    }

    h2.section-title-agenda {
      margin-left: 24px;
      margin-right: 24px;
      margin-top: 34px;
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      color: #256d54;
      font-weight: 700;
      letter-spacing: .2px;
      text-shadow: 0 1px 0 #f6f8f8;
    }

    /* Tira borda feia de calendário FullCalendar */
    .fc-scrollgrid,
    .fc-theme-standard th,
    .fc-theme-standard td,
    .fc-theme-standard .fc-scrollgrid {
      border: none !important;
      background: none !important;
    }

    #calendar-admin {
      border-radius: 22px;
      box-shadow: 0 7px 30px #1d9a7740;
      background: #fff9f4;
      padding: 15px 22px 18px 22px;
      /* padding lateral maior */
      margin: 0 24px 26px 24px;
      /* margem lateral externa */
    }

    /* Calendar buttons melhorados */
    .fc-button,
    .fc-button-primary {
      background: #44b67b !important;
      border: none !important;
      border-radius: 8px !important;
      color: #fff !important;
      font-weight: 600 !important;
      box-shadow: 0 2px 10px #44b67b11 !important;
      transition: background .13s !important;
    }

    .fc-button:hover,
    .fc-button-primary:hover {
      background: #ffd972 !important;
      color: #1d9a77 !important;
    }

    .fc-toolbar-title {
      font-size: 1.21rem !important;
      color: #246045 !important;
      font-family: 'Playfair Display', serif !important;
    }

    /* Para garantir espaço em mobile */
    @media (max-width: 520px) {
      .container {
        padding: 0 2vw 26vw 2vw;
      }

      .agenda-card {
        padding: 16px 3vw;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
      }

      .section-title {
        font-size: 1.05rem;
      }

      .agendas-list {
        margin-left: 1vw;
        margin-right: 1vw;
      }

      .ag-card-header,
      .ag-card-body {
        flex: 1 1 50%;
        min-width: 220px;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
      }

      .ag-card-title {
        font-size: 1.05rem;
        line-height: 1.35;
        margin-bottom: 2px;
      }

      .ag-card-status {
        font-size: 1rem;
        line-height: 1.3;
        margin-left: 0;
      }

      .ag-card-body {
        gap: 14px;
      }

      .ag-card-label {
        font-size: 0.98rem;
        line-height: 1.3;
        margin-bottom: 2px;
      }

      .ag-card-value {
        font-size: 1.05rem;
        line-height: 1.35;
        margin-right: 0;
        margin-bottom: 4px;
      }

      .ag-card-actions {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
      }

      .ag-card-actions a,
      .ag-card-actions button {
        width: 100%;
        text-align: center;
      }
    }

    /* Corrige sobreposição de modal se usar */
    #options-modal {
      z-index: 10010 !important;
    }

    /* Links desabilitados ou "Indisponível" */
    .bloqueado,
    .ag-card-actions .indisponivel {
      color: #c3c3c3 !important;
      font-style: italic;
      background: none !important;
      border: none !important;
      pointer-events: none;
    }
  </style>


  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agenda Unificada</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11e/index.global.min.js"></script>

</head>

<body>
  <div class="header-admin">
    <div>Painel da Terapeuta</div>
    <div class="menu-horizontal">
      <a class="menu-btn active" href="agenda.php">Agenda</a>
      <a class="menu-btn" href="pacientes.php">Pacientes</a>
      <a class="menu-btn" href="precos.php">Preços</a>
      <a class="menu-btn" href="blog.php">Blog</a>
      <a class="menu-btn" href="index.php">Home</a>
    </div>
  </div>
  <!-- Calendário -->
  <div class="container">
    <h1>Agenda da Massoterapeuta</h1>
    <div id="calendar-admin"></div>
    <div id="options-modal"
      style="display:none;position:fixed;top:10%;left:50%;transform:translateX(-50%);background:#fff;padding:32px 24px;z-index:9999;border-radius:16px;box-shadow:0 8px 40px #0001;">
      <div class="modal-content">
        <button class="close-modal" onclick="closeModal()" style="float:right;">X</button>
        <h2 id="modal-title"></h2>
        <div id="modal-body"></div>
      </div>
    </div>
  </div>

  <!-- LISTA DE AGENDAMENTOS -->
  <h2 class="section-title-agenda">Todos os Agendamentos</h2>

  <div class="agendas-list">
    <?php foreach ($agendamentos as $a): ?>
      <div class="agenda-card" data-status="<?= strtolower($a['status']) ?>">
        <div class="ag-card-header">
          <div class="ag-card-title">
            <?php $servicoTitulo = trim((string)($a['servico_exibicao'] ?? $a['servico_nome'] ?? '')); ?>
            <?= $a['usuario_nome'] ? htmlspecialchars($a['usuario_nome']) : '<span class="bloqueado">Indisponível</span>' ?>
            <?php if ($servicoTitulo !== ''): ?>
              <span style="font-weight:400;color:#b3a76c;font-size:.97rem;margin-left:9px;">
                <?= htmlspecialchars($servicoTitulo) ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="ag-card-status" data-status="<?= strtolower($a['status']) ?>">
            <?= getStatusLabel($a['status']) ?>
          </div>
        </div>
        <div class="ag-card-body">
          <div><span class="ag-card-label">Data:</span> <span
              class="ag-card-value"><?= date('d/m/Y', strtotime($a['data_horario'])) ?></span></div>
          <div><span class="ag-card-label">Hora:</span> <span
              class="ag-card-value"><?= date('H:i', strtotime($a['data_horario'])) ?></span></div>
          <div><span class="ag-card-label">Duração:</span> <span class="ag-card-value"><?= $a['duracao'] ?> min</span>
          </div>
        </div>
        <div class="ag-card-actions">
          <?php if (strtolower($a['status']) == 'pendente'): ?>
            <a href="?confirmar=<?= $a['id'] ?>">Confirmar</a>
            <a href="?recusar=<?= $a['id'] ?>">Recusar</a>
          <?php elseif (strtolower($a['status']) == 'confirmado'): ?>
            <a href="?cancelar=<?= $a['id'] ?>">Cancelar</a>
            <a href="paciente.php?id=<?= $a['usuario_id'] ?>">Acessar Usuário</a>
          <?php elseif (strtolower($a['status']) == 'concluido'): ?>
            <span style="color:#30795b">Concluído</span>
          <?php elseif (strtolower($a['status']) == 'cancelado'): ?>
            <span style="color:#e25b5b">Cancelado</span>
          <?php elseif (strtolower($a['status']) == 'indisponivel'): ?>
            <span class="bloqueado">Indisponível</span>
          <?php elseif (strtolower($a['status']) == 'recusado'): ?>
            <span style="color:#aaa;">Recusado</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <script>
      
      function closeModal()
      {
          
          document.getElementById("options-modal").style.display = 'none';
          
      }
      
      
    function setupAutocomplete(inputId, hiddenId, listId) {
      const input = document.getElementById(inputId);
      const hidden = document.getElementById(hiddenId);
      const list = document.getElementById(listId);

      input.addEventListener("input", function () {
        const val = this.value;
        hidden.value = '';
        list.innerHTML = '';
        if (!val) return;
        fetch('?autocomplete_usuario=' + encodeURIComponent(val))
          .then(r => r.json())
          .then(data => {
            list.innerHTML = '';
            data.forEach(u => {
              const div = document.createElement('div');
              div.textContent = u.nome;
              div.onclick = function () {
                input.value = u.nome;
                hidden.value = u.id;
                list.innerHTML = '';
              };
              list.appendChild(div);
            });
          });
      });
      document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !list.contains(e.target)) {
          list.innerHTML = '';
        }
      });
    }
    document.addEventListener('DOMContentLoaded', function () {
      let now = new Date();

      const cal = document.getElementById('calendar-admin');

      renderCalendarAdmin(now.getFullYear(), now.getMonth(), datasBloqueadas);
    });



    let datasBloqueadas = <?php
    // PHP para bloquear domingos e datas confirmadas
    $bloqueadas = [];
    foreach ($agendamentos as $a) {
      if (strtolower($a['status']) == 'confirmado') {
        $bloqueadas[] = date('Y-m-d', strtotime($a['data_horario']));
      }
    }
    echo json_encode($bloqueadas);
    ?>;

    function renderCalendarAdmin(year, month, datasBloqueadas) {


      const cal = document.getElementById('calendar-admin');

      cal.innerHTML = '';
      const dt = new Date(year, month, 1);
      const days = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
      let html = `<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;'>
    <button id='prev-month'>&lt;</button>
    <span style='font-weight:700;'>${dt.toLocaleString('pt-BR', { month: 'long' })} ${year}</span>
    <button id='next-month'>&gt;</button>
  </div>`;
      html += `<div style='display:grid;grid-template-columns:repeat(7,1fr);font-weight:bold;'>${days.map(d => `<span>${d}</span>`).join('')}</div>`;
      let firstDay = dt.getDay();
      let totalDays = new Date(year, month + 1, 0).getDate();
      html += `<div style='display:grid;grid-template-columns:repeat(7,1fr);'>`;
      for (let i = 0; i < firstDay; i++) html += "<span></span>";
      for (let d = 1; d <= totalDays; d++) {
        let data = `${year}-${(month + 1).toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
        let isSunday = new Date(year, month, d).getDay() === 0;
        let bloqueado = datasBloqueadas.includes(data) || isSunday;
        html += `<button class="cal-admin-day${bloqueado ? ' cal-disabled' : ''}" data-date="${data}" ${bloqueado ? 'disabled' : ''}>${d}</button>`;
      }
      html += `</div>`;
      cal.innerHTML = html;
      document.getElementById('prev-month').onclick = () => renderCalendarAdmin(month === 0 ? year - 1 : year, month === 0 ? 11 : month - 1, datasBloqueadas);
      document.getElementById('next-month').onclick = () => renderCalendarAdmin(month === 11 ? year + 1 : year, month === 11 ? 0 : month + 1, datasBloqueadas);

      document.querySelectorAll('.cal-admin-day').forEach(btn => {
        btn.onclick = function () {
          let dataEscolhida = this.getAttribute('data-date');
          mostrarModalOpcoes(dataEscolhida);
        };
      });
    }

    function mostrarModalOpcoes(data) {
      // Gera horários do dia (exemplo: 08:00 às 20:00 de hora em hora)
      let horarios = [];
      for (let h = 8; h <= 20; h++) {
        horarios.push((h < 10 ? '0' : '') + h + ':00');
      }

      // Você pode buscar horários ocupados/bloqueados via PHP e passar para o JS se quiser
      // Aqui exemplo estático:
      let horariosOcupados = []; // Ex: ['09:00', '14:00']
      let horariosBloqueados = []; // Ex: ['10:00']

      let html = `<h3 style="margin-bottom:10px;">Horários do dia ${data.split('-').reverse().join('/')}</h3>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:7px 5px;margin-bottom:18px;">`;

      horarios.forEach(h => {
        let ocupado = horariosOcupados.includes(h);
        let bloqueado = horariosBloqueados.includes(h);
        let btnClass = ocupado ? 'cal-disabled' : bloqueado ? 'cal-disabled' : '';
        let label = ocupado ? 'Ocupado' : bloqueado ? 'Bloqueado' : h;
        html += `<button class="cal-admin-day ${btnClass}" style="padding:7px 0;" data-date="${data}" data-hora="${h}" ${ocupado || bloqueado ? 'disabled' : ''} onclick="abrirOpcoesHorario('${data}','${h}')">${label}</button>`;
      });
      html += `</div>
    <button onclick="abrirBloquearDia('${data}')" style="margin-top:10px;">Bloquear Dia Inteiro</button>
    <button onclick="closeModal();" style="margin-left:10px;">Fechar</button>
  `;

      document.getElementById('options-modal').style.display = 'block';
      document.getElementById('modal-title').innerText = 'Horários do dia';
      document.getElementById('modal-body').innerHTML = html;
    }



    // Opções para um horário específico
    function abrirOpcoesHorario(data, hora) {
      document.getElementById('modal-title').innerText = `Opções para ${data.split('-').reverse().join('/')} ${hora}`;
      document.getElementById('modal-body').innerHTML = `
    <button onclick="abrirNovoAgendamento('${data}','${hora}')">Marcar Novo Agendamento</button>
    <button onclick="abrirBloquearHorario('${data}','${hora}')">Bloquear Horário</button>
    <button onclick="abrirAgendamentoFixo('${data}','${hora}')">Agendamento Fixo</button>
    <button onclick="mostrarModalOpcoes('${data}')" style="margin-top:10px;">Voltar</button>
  `;
    }

    // Bloquear dia inteiro
    function abrirBloquearDia(data) {
      document.getElementById('modal-title').innerText = 'Bloquear Dia Inteiro ' + data.split('-').reverse().join('/');
      document.getElementById('modal-body').innerHTML = `
    <button id="btn-bloquear-dia" class="btn-form">Bloquear Dia Inteiro</button>
    <button type="button" onclick="mostrarModalOpcoes('${data}')" style="margin-left:10px;">Voltar</button>
  `;
      document.getElementById('btn-bloquear-dia').onclick = function () {
        let promises = [];
        for (let h = 8; h <= 20; h++) {
          let hora = (h < 10 ? '0' : '') + h + ':00:00';
          let data_horario = data + ' ' + hora;
          promises.push(
            fetch('bloquearHorario.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'data_horario=' + encodeURIComponent(data_horario)
            })
          );
        }
        Promise.all(promises).then(() => {
          alert('Dia inteiro bloqueado!');
          document.getElementById('options-modal').style.display = 'none';
          location.reload();
        });
      };
    }

function abrirBloquearHorario(data, hora) {
  document.getElementById('modal-title').innerText = 'Bloquear Horário em ' + data.split('-').reverse().join('/') + ' ' + hora;
  document.getElementById('modal-body').innerHTML = `
        < button id = "btn-bloquear-horario" class="btn-form" > Bloquear</button >
          <button type="button" onclick="mostrarModalOpcoes('${data}')" style="margin-left:10px;">Voltar</button>`;
  document.getElementById('btn-bloquear-horario').onclick = function() {
    fetch('bloquearHorario.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'data_horario=' + encodeURIComponent(data + ' ' + hora)
    })
    .then(r => r.text())
    .then(res => {
      alert('Horário bloqueado!');
      document.getElementById('options-modal').style.display = 'none';
      location.reload();
    });
  };
}


 document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar-admin');
              var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
              locale: 'pt-br',
              height: 600,
              events: <?php echo json_encode($eventos_calendario); ?>,
              dateClick: function(info) {
                mostrarModalOpcoes(info.dateStr);
    }
  });
              calendar.render();
});

 </script>
 <script>






    // Ajuste as funções abrirNovoAgendamento, abrirBloquearHorario, abrirAgendamentoFixo para receber data e hora:
    function abrirNovoAgendamento(data) {
      document.getElementById('modal-title').innerText = 'Novo Agendamento em ' + data;
      document.getElementById('modal-body').innerHTML = `
    <div id="novo-agendamento-massoterapeuta">
      <div id="escolher-usuario-area">
        <label>Escolha um usuário cadastrado:</label>
        <select id="select-usuario">
          <option value="">Selecione...</option>
        </select>
        <button id="btn-para-visitante" type="button" class="btn-form">Agendar para visitante</button>
      </div>
      <div id="form-visitante-area" style="display:none;">
        <div class="guest-form">
          <label for="guest-name">Nome</label>
          <input type="text" id="guest-name" name="guest-name" placeholder="Digite seu nome" required>
          <label for="guest-email">E-mail</label>
          <input type="email" id="guest-email" name="guest-email" placeholder="Seu e-mail" required>
          <label for="guest-phone">Número de telefone</label>
          <input type="tel" id="guest-phone" name="guest-phone" placeholder="+55" pattern="\\+?\\d{2,15}" required>
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
        </div>
        <button id="btn-voltar-escolha" type="button" class="btn-form">Voltar</button>
      </div>
      <label>Especialidade:</label>
      <select id="especialidade_id">
        <option value="1">Quick Massage</option>
        <option value="2">Massoterapia</option>
        <option value="3">Reflexologia Podal</option>
        <option value="4">Auriculoterapia</option>
        <option value="5">Ventosa</option>
        <option value="6">Acupuntura</option>
        <option value="7">Biomagnetismo</option>
        <option value="8">Reiki</option>
      </select>
      <label>Duração (min):</label>
      <input type="number" id="duracao" required>
      <label><input type="checkbox" id="adicional_reflexo" value="1"> Adicional Reflexo</label>
      <br>
      <button id="btn-agendar-massa" type="button" class="btn-form">Agendar</button>
    </div>
`;
}

  // 1. Carrega usuários no select
  fetch('get_usuarios.php')
    .then(r=>r.json())
    .then(lista => {
      let sel = document.getElementById('select-usuario');
      lista.forEach(u=>{
        let opt = document.createElement('option');
        opt.value = u.id;
        opt.innerText = u.nome + (u.email ? ' ('+u.email+')' : '');
        sel.appendChild(opt);
      });
    });

  // 2. Alterna visitante/usuário
  document.getElementById('btn-para-visitante').onclick = function(){
    document.getElementById('escolher-usuario-area').style.display = 'none';
    document.getElementById('form-visitante-area').style.display = '';
  };
  document.getElementById('btn-voltar-escolha').onclick = function(){
    document.getElementById('form-visitante-area').style.display = 'none';
    document.getElementById('escolher-usuario-area').style.display = '';
  };

  // 3. Agendamento
  document.getElementById('btn-agendar-massa').onclick = function(){
    let especialidade_id = document.getElementById('especialidade_id').value;
    let duracao = document.getElementById('duracao').value;
    let adicional_reflexo = document.getElementById('adicional_reflexo').checked ? 1 : 0;
    let usuario_id = document.getElementById('select-usuario').value;
    let hora = ''; // Se não for usar hora separada, deixe vazio ou adicione um campo de horário no modal

    let dados = {
      data, hora, especialidade_id, duracao, adicional_reflexo
    };

    if(usuario_id){
      dados.usuario_id = usuario_id;
    } else {
      dados.guest_name = document.getElementById('guest-name').value;
      dados.guest_email = document.getElementById('guest-email').value;
      dados.guest_phone = document.getElementById('guest-phone').value;
      dados.guest_nascimento = document.getElementById('guest-nascimento').value;
      dados.guest_sexo = document.getElementById('guest-sexo').value;
    }

    fetch('novo_agendar_massat.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams(dados)
    })
    .then(r=>r.json())
    .then(res=>{
      if(res.ok){
        alert('Agendado com sucesso!');
        document.getElementById('options-modal').style.display = 'none';
      } else {
        alert(res.msg);
      }
    });
  };


function abrirAgendamentoFixo(data, hora) {
  document.getElementById('modal-title').innerText = 'Agendamento Fixo';
  document.getElementById('modal-body').innerHTML = `
        < form method = "post" action = "agendarFixo.php" autocomplete = "off" >
          <label>Usuário:
            <input type="text" id="usuario_nome_fixo" placeholder="Digite o nome..." autocomplete="off" required>
              <input type="hidden" name="usuario_id" id="usuario_id_fixo" required>
                <div id="autocomplete-list-fixo" class="autocomplete-items"></div>
              </label>
              <label>Especialidade:
                <select name="especialidade_id" required>
                  <option value="1">Quick Massage</option>
                  <option value="2">Massoterapia</option>
                  <option value="3">Reflexologia Podal</option>
                  <option value="4">Auriculoterapia</option>
                  <option value="5">Ventosa</option>
                  <option value="6">Acupuntura</option>
                  <option value="7">Biomagnetismo</option>
                  <option value="8">Reiki</option>
                </select>
              </label>
              <label>Dia da semana:
                <select name="dia_semana" required>
                  <option value="1">Segunda-feira</option>
                  <option value="2">Terça-feira</option>
                  <option value="3">Quarta-feira</option>
                  <option value="4">Quinta-feira</option>
                  <option value="5">Sexta-feira</option>
                  <option value="6">Sábado</option>
                  <option value="7">Domingo</option>
                </select>
              </label>
              <label>Horário:
                <input type="time" name="horario" required>
              </label>
              <label>Duração (min):
                <input type="number" name="duracao" value="60" required>
              </label>
              <label>Data de início:
                <input type="date" name="data_inicio" value="${data}" required>
              </label>
              <label>Data de fim:
                <input type="date" name="data_fim" value="${data}" required>
              </label>
              <button type="submit" class="btn-form">Agendar Fixo</button>
              <button type="button" onclick="mostrarModalOpcoes('${data}')" style="margin-left:10px;">Voltar</button>
        </form>
            <script>
              setupAutocomplete('usuario_nome_fixo', 'usuario_id_fixo', 'autocomplete-list-fixo');
              
              `;
}
  </script>



</body>

</html>
