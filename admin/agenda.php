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

function atualizarStatusAgendamento(mysqli $conn, int $id, string $novoStatus): bool
{
  $stmt = $conn->prepare('UPDATE agendamentos SET status = ? WHERE id = ?');
  if (!$stmt) {
    return false;
  }
  $stmt->bind_param('si', $novoStatus, $id);
  $stmt->execute();
  $erro = $stmt->error;
  $stmt->close();

  return $erro === '';
}

function buscarAgendamento(mysqli $conn, int $id): ?array
{
  $sql = "SELECT a.*, u.nome as usuario_nome, e.nome as servico_nome
FROM agendamentos a
LEFT JOIN usuarios u ON a.usuario_id = u.id
LEFT JOIN especialidades e ON a.especialidade_id = e.id
WHERE a.id = ?";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return null;
  }
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $resultado = $stmt->get_result();
  $agendamento = $resultado ? $resultado->fetch_assoc() : null;
  $stmt->close();

  return $agendamento ?: null;
}

function renderStatusActionButton(int $id, string $acao, string $label, string $fallbackHref): string
{
  $acaoEsc = htmlspecialchars($acao, ENT_QUOTES, 'UTF-8');
  $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
  $fallbackEsc = htmlspecialchars($fallbackHref, ENT_QUOTES, 'UTF-8');

  return sprintf(
    '<button type="button" class="ag-card-action-btn" data-id="%d" data-acao="%s" data-fallback-href="%s" data-loading-text="Processando..." onclick="event.preventDefault();">%s</button><noscript><a class="ag-card-action-fallback" href="%s">%s</a></noscript>',
    $id,
    $acaoEsc,
    $fallbackEsc,
    $labelEsc,
    $fallbackEsc,
    $labelEsc
  );
}

function renderActionButtons(array $agendamento): string
{
  $status = strtolower($agendamento['status'] ?? '');
  $id = (int) ($agendamento['id'] ?? 0);
  $usuarioId = isset($agendamento['usuario_id']) ? (int) $agendamento['usuario_id'] : 0;
  $parts = [];

  if ($status === 'pendente') {
    $parts[] = renderStatusActionButton($id, 'confirmar', 'Confirmar', "?confirmar=$id");
    $parts[] = renderStatusActionButton($id, 'recusar', 'Recusar', "?recusar=$id");
  } elseif ($status === 'confirmado') {
    $parts[] = renderStatusActionButton($id, 'cancelar', 'Cancelar', "?cancelar=$id");
    if ($usuarioId) {
      $parts[] = '<a class="ag-card-secondary-link" href="paciente.php?id=' . $usuarioId . '">Acessar Usuário</a>';
    }
  } elseif ($status === 'concluido') {
    $parts[] = '<span style="color:#30795b">Concluído</span>';
  } elseif ($status === 'cancelado') {
    $parts[] = '<span style="color:#e25b5b">Cancelado</span>';
  } elseif ($status === 'indisponivel') {
    $parts[] = '<span class="bloqueado">Indisponível</span>';
  } elseif ($status === 'recusado') {
    $parts[] = '<span style="color:#aaa;">Recusado</span>';
  }

  return implode("\n", $parts);
}

// ----------- AÇÕES DE STATUS -----------
$acoesStatus = [
  'confirmar' => 'Confirmado',
  'recusar' => 'Recusado',
  'cancelar' => 'Cancelado',
  'concluir' => 'Concluido',
];

if (isset($_POST['acao_status'])) {
  header('Content-Type: application/json');

  $acao = strtolower(trim((string) $_POST['acao_status']));
  $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

  if (!$id || !isset($acoesStatus[$acao])) {
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'message' => 'Ação inválida.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $novoStatus = $acoesStatus[$acao];
  $atualizado = atualizarStatusAgendamento($conn, $id, $novoStatus);

  if (!$atualizado) {
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'message' => 'Não foi possível atualizar o status.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $agendamento = buscarAgendamento($conn, $id);

  if (!$agendamento) {
    echo json_encode([
      'success' => true,
      'message' => 'Status atualizado.',
      'new_status' => strtolower($novoStatus),
      'new_status_label' => getStatusLabel($novoStatus),
      'actions_html' => '',
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Atualizar descrições de serviços para manter consistência com a listagem.
  [$tituloServicos, $listaServicos] = descreverServicos(
    $conn,
    isset($agendamento['especialidade_id']) ? (int) $agendamento['especialidade_id'] : 0,
    $agendamento['servicos_csv'] ?? null,
    $agendamento['servico_nome'] ?? ''
  );
  $agendamento['servico_exibicao'] = $tituloServicos;
  $agendamento['servicos_lista'] = $listaServicos;

  echo json_encode([
    'success' => true,
    'message' => 'Status atualizado.',
    'new_status' => strtolower($agendamento['status']),
    'new_status_label' => getStatusLabel($agendamento['status']),
    'actions_html' => renderActionButtons($agendamento),
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

foreach ($acoesStatus as $param => $statusAlvo) {
  if (isset($_GET[$param])) {
    $id = (int) $_GET[$param];
    if ($id) {
      atualizarStatusAgendamento($conn, $id, $statusAlvo);
    }
  }
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
$dataFiltro = $_GET['data'] ?? '';
$dataFiltroValido = false;
if ($dataFiltro !== '') {
  $dataObj = DateTime::createFromFormat('Y-m-d', $dataFiltro);
  if ($dataObj && $dataObj->format('Y-m-d') === $dataFiltro) {
    $dataFiltroValido = true;
  } else {
    $dataFiltro = '';
  }
}

$sqlBaseAgendamentos = "SELECT a.*, u.nome as usuario_nome, e.nome as servico_nome
FROM agendamentos a
LEFT JOIN usuarios u ON a.usuario_id = u.id
LEFT JOIN especialidades e ON a.especialidade_id = e.id";
$orderByClause = "\nORDER BY a.data_horario DESC";

// Consulta completa para o calendário e bloqueios
$agendamentosCalendario = [];
$stmtCalendario = $conn->prepare($sqlBaseAgendamentos . $orderByClause);
if ($stmtCalendario) {
  $stmtCalendario->execute();
  $resultCalendario = $stmtCalendario->get_result();
  $agendamentosCalendario = $resultCalendario ? $resultCalendario->fetch_all(MYSQLI_ASSOC) : [];
  $stmtCalendario->close();
}

// Consulta filtrada para a listagem
$sqlLista = $sqlBaseAgendamentos;
if ($dataFiltroValido) {
  $sqlLista .= "\nWHERE DATE(a.data_horario) = ?";
}
$sqlLista .= $orderByClause;
$agendamentosLista = [];
$stmtLista = $conn->prepare($sqlLista);
if ($stmtLista) {
  if ($dataFiltroValido) {
    $stmtLista->bind_param('s', $dataFiltro);
  }
  $stmtLista->execute();
  $resultLista = $stmtLista->get_result();
  $agendamentosLista = $resultLista ? $resultLista->fetch_all(MYSQLI_ASSOC) : [];
  $stmtLista->close();
}

$mensagemAgendamentos = '';
if ($dataFiltroValido && empty($agendamentosLista)) {
  $mensagemAgendamentos = 'Nenhum agendamento encontrado para a data selecionada.';
}
foreach ($agendamentosLista as &$ag) {
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
foreach ($agendamentosCalendario as $a) {
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
      gap: 16px;
    }

    .header-admin .header-brand {
      font-weight: 700;
      letter-spacing: .4px;
    }

    .header-admin .menu-container {
      display: flex;
      align-items: center;
    }

    .header-admin .menu-horizontal {
      display: flex;
      gap: 14px;
    }

    .header-admin .menu-toggle {
      display: none;
      background: none;
      border: none;
      color: #fff;
      font-size: 1.7rem;
      cursor: pointer;
      line-height: 1;
      padding: 6px 10px;
      border-radius: 10px;
      transition: background .17s;
    }

    .header-admin .menu-toggle:hover,
    .header-admin.is-open .menu-toggle {
      background: rgba(255, 255, 255, 0.12);
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
        gap: 10px;
      }

      .header-admin .menu-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }

      .header-admin .menu-container {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        padding: 12px 2vw 16px 2vw;
        background: var(--header-bg);
        box-shadow: var(--header-shadow);
        border-radius: 0 0 16px 16px;
      }

      .header-admin .menu-horizontal {
        display: none;
        flex-direction: column;
        gap: 6px;
      }

      .header-admin .menu-btn {
        text-align: left;
        padding: 10px 12px;
      }

      .header-admin.is-open .menu-container {
        display: block;
      }

      .header-admin.is-open .menu-horizontal {
        display: flex;
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

    .ag-card-action-btn.is-loading,
    .ag-card-action-btn[disabled] {
      opacity: 0.6;
      cursor: progress;
    }

    .agenda-card.is-updating {
      opacity: 0.85;
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

    .agenda-filter-form {
      margin: 18px 24px 10px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      font-family: 'Roboto', sans-serif;
    }

    .agenda-filter-form label {
      font-weight: 600;
      color: #256d54;
    }

    .agenda-filter-form input[type="date"] {
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid #c9dfd6;
      background: #ffffff;
      color: #256d54;
      font-size: 0.95rem;
    }

    .agenda-filter-form button {
      background: var(--header-bg);
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 10px 22px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s ease, transform .2s ease;
    }

    .agenda-filter-form button:hover {
      background: var(--accent);
      color: #1d5f45;
      transform: translateY(-1px);
    }

    .agenda-filter-form .filter-reset {
      color: #256d54;
      text-decoration: underline;
      font-weight: 500;
    }

    .no-results-alert {
      margin: 0 24px 12px;
      padding: 14px 18px;
      border-radius: 16px;
      background: #ffeebe;
      color: #8a6d3b;
      font-weight: 600;
      font-family: 'Roboto', sans-serif;
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
      background: #fff;
      border-radius: 22px;
      box-shadow: 0 7px 30px #1d9a7740;
      padding: 20px 24px 24px 24px;
      margin: 0 24px 26px 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .calendar-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      font-weight: 600;
      font-size: 0.95rem;
      border-radius: 12px;
      padding: 0.55rem 1.2rem;
      border: 1px solid transparent;
      background: var(--header-bg);
      color: #fff;
      cursor: pointer;
      text-decoration: none;
      transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .calendar-btn:hover,
    .calendar-btn:focus-visible {
      background: var(--accent);
      color: #1d5f45;
      outline: none;
      box-shadow: 0 6px 18px #ffd97233;
    }

    .calendar-btn--primary {
      background: var(--success);
      box-shadow: 0 4px 16px #1d9a7722;
    }

    .calendar-btn--primary:hover,
    .calendar-btn--primary:focus-visible {
      background: #32a169;
      color: #fff;
      box-shadow: 0 6px 22px #1d9a7733;
    }

    .calendar-btn--secondary {
      background: #fff;
      color: var(--header-bg);
      border-color: #c8dbd4;
      box-shadow: 0 3px 12px #1d9a7714;
    }

    .calendar-btn--secondary:hover,
    .calendar-btn--secondary:focus-visible {
      background: var(--action-hover);
      color: #1d5f45;
      border-color: #9ecab8;
    }

    .calendar-btn--ghost {
      background: transparent;
      color: var(--text-light);
      border-color: transparent;
      box-shadow: none;
    }

    .calendar-btn--ghost:hover,
    .calendar-btn--ghost:focus-visible {
      background: #f1f5f4;
      color: var(--header-bg);
    }

    .calendar-btn--icon {
      padding: 0.35rem;
      border-radius: 999px;
      font-size: 0.85rem;
      line-height: 1;
      min-width: 34px;
      min-height: 34px;
    }

    .calendar-btn--small {
      padding: 0.45rem 0.85rem;
      font-size: 0.85rem;
      border-radius: 10px;
    }

    .calendar-btn + .calendar-btn {
      margin-left: 0.75rem;
    }

    .calendar-modal-actions .calendar-btn + .calendar-btn {
      margin-left: 0;
    }

    .calendar-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }

    .calendar-nav .calendar-btn {
      font-size: 1rem;
      padding: 8px 14px;
      box-shadow: 0 4px 14px #1d9a7722;
    }

    .calendar-nav .calendar-btn:hover,
    .calendar-nav .calendar-btn:focus-visible {
      transform: translateY(-1px);
    }

    .calendar-nav-label {
      font-family: 'Playfair Display', serif;
      font-size: 1.25rem;
      color: #246045;
      text-transform: capitalize;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, minmax(0, 1fr));
      gap: 8px;
    }

    .calendar-header {
      font-size: 0.9rem;
      font-weight: 700;
      letter-spacing: .4px;
      text-transform: uppercase;
      color: var(--text-light);
    }

    .calendar-header span {
      text-align: center;
      padding: 6px 0;
    }

    .calendar-placeholder {
      min-height: 54px;
    }

    .cal-admin-day {
      background: #fdfdfd;
      border: 1px solid #dfe9e6;
      border-radius: 14px;
      color: var(--text-main);
      cursor: pointer;
      font-weight: 600;
      min-height: 54px;
      padding: 10px 0;
      transition: background .2s, color .2s, box-shadow .2s, transform .2s, border-color .2s;
    }

    .cal-admin-day:hover,
    .cal-admin-day:focus-visible {
      background: var(--action-hover);
      border-color: var(--success);
      box-shadow: 0 6px 18px #1d9a7718;
      color: #246045;
      transform: translateY(-1px);
      outline: none;
    }

    .cal-admin-day.is-today {
      border-color: var(--accent);
      background: #fff7dc;
      color: #6f5300;
    }

    .cal-admin-day.cal-disabled {
      background: #f1f4f3;
      border-color: #d8e3df;
      box-shadow: none;
      color: #a1b2ab;
      cursor: not-allowed;
    }

    .cal-admin-day.cal-disabled:hover,
    .cal-admin-day.cal-disabled:focus-visible {
      background: #f1f4f3;
      color: #a1b2ab;
      transform: none;
      box-shadow: none;
      border-color: #d8e3df;
    }

    .calendar-legend {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
      font-size: 0.85rem;
      color: var(--text-light);
    }

    .calendar-legend span {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #f7faf9;
      border-radius: 999px;
      padding: 6px 10px;
    }

    .calendar-legend .legend-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      display: inline-block;
    }

    .calendar-legend .legend-available {
      background: var(--success);
    }

    .calendar-legend .legend-disabled {
      background: #bccbc5;
    }

    .calendar-legend .legend-today {
      background: var(--accent);
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

      #calendar-admin {
        padding: 18px;
        margin: 0 12px 24px 12px;
        gap: 12px;
      }

      .calendar-nav {
        justify-content: center;
        gap: 6px;
        flex-wrap: nowrap;
      }

      .calendar-nav button {
        flex: 0 0 auto;
        min-width: 34px;
        padding: 0.35rem;
      }

      .calendar-nav-label {
        flex: 0 0 auto;
        margin: 0 8px;
        text-align: center;
        font-size: 1.1rem;
      }

      .calendar-grid {
        gap: 6px;
      }

      .calendar-placeholder {
        min-height: 46px;
      }

      .cal-admin-day {
        min-height: 46px;
        padding: 8px 0;
        font-size: 0.95rem;
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

    #options-modal {
      display: none;
      position: fixed;
      top: 10%;
      left: 50%;
      transform: translateX(-50%);
      background: #fff;
      padding: 32px 24px;
      z-index: 10010 !important;
      border-radius: 16px;
      box-shadow: 0 8px 40px #0001;
      width: min(92vw, 520px);
    }

    #options-modal .modal-content {
      position: relative;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    #modal-title {
      font-family: 'Playfair Display', serif;
      color: #256d54;
      margin: 0;
      font-size: 1.3rem;
    }

    #modal-body {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .modal-subtitle {
      margin: 0;
      font-size: 1.05rem;
      color: var(--text-main);
      font-weight: 600;
    }

    .modal-text {
      margin: 0;
      color: var(--text-light);
      font-size: 0.95rem;
    }

    .calendar-modal-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
      gap: 7px 8px;
    }

    .calendar-modal-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .calendar-modal-actions .calendar-btn {
      flex: 1 1 160px;
    }

    .close-modal {
      position: absolute;
      top: 8px;
      right: 8px;
    }

    @media (max-width: 520px) {
      #options-modal {
        padding: 24px 16px;
      }

      .calendar-modal-actions .calendar-btn {
        flex: 1 1 100%;
      }
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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

</head>

<body>
  <div class="header-admin">
    <div class="header-brand">Painel da Terapeuta</div>
    <button class="menu-toggle" aria-expanded="false" aria-controls="admin-menu">☰</button>
    <div class="menu-container">
      <div class="menu-horizontal" id="admin-menu">
        <a class="menu-btn active" href="agenda.php">Agenda</a>
        <a class="menu-btn" href="pacientes.php">Pacientes</a>
        <a class="menu-btn" href="precos.php">Preços</a>
        <a class="menu-btn" href="blog.php">Blog</a>
        <a class="menu-btn" href="index.php">Home</a>
      </div>
    </div>
  </div>
  <!-- Calendário -->
  <div class="container">
    <h1>Agenda da Massoterapeuta</h1>
    <div id="calendar-admin"></div>
    <div id="options-modal">
      <div class="modal-content">
        <button type="button" class="calendar-btn calendar-btn--ghost calendar-btn--icon close-modal" onclick="closeModal()" aria-label="Fechar modal">&times;</button>
        <h2 id="modal-title"></h2>
        <div id="modal-body"></div>
      </div>
    </div>
  </div>

  <!-- LISTA DE AGENDAMENTOS -->
  <h2 class="section-title-agenda">Todos os Agendamentos</h2>

  <form class="agenda-filter-form" method="get" action="agenda.php">
    <label for="filtro-data">Filtrar por data:</label>
    <input type="date" id="filtro-data" name="data" value="<?= htmlspecialchars($dataFiltro) ?>">
    <button type="submit">Buscar</button>
    <a class="filter-reset" href="agenda.php">Limpar filtro</a>
  </form>

  <?php if ($mensagemAgendamentos !== ''): ?>
    <div class="no-results-alert"><?= htmlspecialchars($mensagemAgendamentos) ?></div>
  <?php endif; ?>

  <div class="agendas-list">
    <?php foreach ($agendamentosLista as $a): ?>
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
          <?= renderActionButtons($a); ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <script>

    function closeModal()
    {

        document.getElementById("options-modal").style.display = 'none';

    }


    function setupStatusActionHandlers() {
      const agendasList = document.querySelector('.agendas-list');
      if (!agendasList || agendasList.dataset.statusListeners === 'true') {
        return;
      }

      agendasList.dataset.statusListeners = 'true';

      agendasList.addEventListener('click', function (event) {
        const button = event.target.closest('.ag-card-action-btn');
        if (!button) {
          return;
        }
        event.preventDefault();
        if (button.disabled) {
          return;
        }
        processStatusAction(button);
      });

      agendasList.addEventListener('keydown', function (event) {
        const button = event.target.closest('.ag-card-action-btn');
        if (!button) {
          return;
        }
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          if (!button.disabled) {
            processStatusAction(button);
          }
        }
      });
    }

    function processStatusAction(button) {
      const acao = button.dataset.acao;
      const id = button.dataset.id;
      if (!acao || !id) {
        return;
      }

      const card = button.closest('.agenda-card');
      const fallbackHref = button.dataset.fallbackHref || '';
      const originalHtml = button.innerHTML;
      const loadingText = button.dataset.loadingText || 'Processando...';

      button.disabled = true;
      button.classList.add('is-loading');
      if (button.innerHTML.trim() !== loadingText) {
        button.innerHTML = loadingText;
      }

      if (card) {
        card.classList.add('is-updating');
        card.setAttribute('aria-busy', 'true');
      }

      fetch('agenda.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
          acao_status: acao,
          id: id
        })
      })
        .then(response => {
          if (!response.ok) {
            throw new Error('Falha na comunicação com o servidor.');
          }
          return response.json();
        })
        .then(data => {
          if (!data.success) {
            throw new Error(data.message || 'Não foi possível atualizar o status.');
          }

          if (card) {
            if (data.new_status) {
              card.dataset.status = data.new_status;
            }

            const statusBadge = card.querySelector('.ag-card-status');
            if (statusBadge && data.new_status_label) {
              statusBadge.textContent = data.new_status_label;
              statusBadge.setAttribute('data-status', data.new_status);
            }

            if (typeof data.actions_html === 'string') {
              const actionsContainer = card.querySelector('.ag-card-actions');
              if (actionsContainer) {
                actionsContainer.innerHTML = data.actions_html;
              }
            }

            if (data.remove_card) {
              card.classList.add('is-removing');
              setTimeout(() => card.remove(), 200);
            }
          }
        })
        .catch(error => {
          console.error(error);
          const message = error && error.message ? error.message : 'Ocorreu um erro ao atualizar o status.';
          alert(message + (fallbackHref ? '\nVocê pode tentar novamente pelo link tradicional.' : ''));
          if (fallbackHref) {
            window.location.href = fallbackHref;
          }
        })
        .finally(() => {
          if (button.isConnected) {
            button.classList.remove('is-loading');
            button.disabled = false;
            button.innerHTML = originalHtml;
          }

          if (card && card.isConnected) {
            card.classList.remove('is-updating');
            card.removeAttribute('aria-busy');
          }
        });
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
    let datasBloqueadas = <?php
    // PHP para bloquear domingos e datas confirmadas usando o conjunto completo
    $bloqueadas = [];
    foreach ($agendamentosCalendario as $a) {
      if (strtolower($a['status']) == 'confirmado') {
        $bloqueadas[] = date('Y-m-d', strtotime($a['data_horario']));
      }
    }
    echo json_encode($bloqueadas);
    ?>;

    document.addEventListener('DOMContentLoaded', function () {
      const calendarEl = document.getElementById('calendar-admin');
      if (!calendarEl) {
        return;
      }

      setupStatusActionHandlers();

      const calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [FullCalendarDayGrid, FullCalendarInteraction],
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        height: 600,
        headerToolbar: {
          start: 'prev,next today',
          center: 'title',
          end: ''
        },
        footerToolbar: false,
        events: <?php echo json_encode($eventos_calendario); ?>,
        dateClick: function (info) {
          if (datasBloqueadas.includes(info.dateStr) || info.date.getDay() === 0) {
            return;
          }

          mostrarModalOpcoes(info.dateStr);
        }
      });

      calendar.render();
    });

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

      let html = `<h3 class="modal-subtitle">Horários do dia ${data.split('-').reverse().join('/')}</h3>
    <div class="calendar-modal-grid">`;

      horarios.forEach(h => {
        let ocupado = horariosOcupados.includes(h);
        let bloqueado = horariosBloqueados.includes(h);
        let btnClass = ocupado ? 'cal-disabled' : bloqueado ? 'cal-disabled' : '';
        let label = ocupado ? 'Ocupado' : bloqueado ? 'Bloqueado' : h;
        const buttonClasses = ['calendar-btn', 'calendar-btn--secondary', 'cal-admin-day'];
        if (btnClass) {
          buttonClasses.push(btnClass);
        }
        html += `<button type="button" class="${buttonClasses.join(' ')}" data-date="${data}" data-hora="${h}" ${ocupado || bloqueado ? 'disabled' : ''} onclick="abrirOpcoesHorario('${data}','${h}')">${label}</button>`;
      });
      html += `</div>
    <div class="calendar-modal-actions">
      <button type="button" class="calendar-btn calendar-btn--primary" onclick="abrirBloquearDia('${data}')">Bloquear Dia Inteiro</button>
      <button type="button" class="calendar-btn calendar-btn--secondary" onclick="closeModal()">Fechar</button>
    </div>
  `;

      document.getElementById('options-modal').style.display = 'block';
      document.getElementById('modal-title').innerText = 'Horários do dia';
      document.getElementById('modal-body').innerHTML = html;
    }



    // Opções para um horário específico
    function abrirOpcoesHorario(data, hora) {
      document.getElementById('modal-title').innerText = `Opções para ${data.split('-').reverse().join('/')} ${hora}`;
      document.getElementById('modal-body').innerHTML = `
    <div class="calendar-modal-actions">
      <button type="button" class="calendar-btn calendar-btn--primary" onclick="abrirNovoAgendamento('${data}','${hora}')">Marcar Novo Agendamento</button>
      <button type="button" class="calendar-btn calendar-btn--secondary" onclick="abrirBloquearHorario('${data}','${hora}')">Bloquear Horário</button>
      <button type="button" class="calendar-btn calendar-btn--secondary" onclick="abrirAgendamentoFixo('${data}','${hora}')">Agendamento Fixo</button>
    </div>
    <div class="calendar-modal-actions">
      <button type="button" class="calendar-btn calendar-btn--ghost" onclick="mostrarModalOpcoes('${data}')">Voltar</button>
    </div>
  `;
    }

    // Bloquear dia inteiro
    function abrirBloquearDia(data) {
      document.getElementById('modal-title').innerText = 'Bloquear Dia Inteiro ' + data.split('-').reverse().join('/');
      document.getElementById('modal-body').innerHTML = `
    <p class="modal-text">Esta ação criará bloqueios para todos os horários disponíveis deste dia.</p>
    <div class="calendar-modal-actions">
      <button type="button" id="btn-bloquear-dia" class="calendar-btn calendar-btn--primary">Bloquear Dia Inteiro</button>
      <button type="button" class="calendar-btn calendar-btn--secondary" onclick="mostrarModalOpcoes('${data}')">Voltar</button>
    </div>
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
    <p class="modal-text">Confirme para impedir novos agendamentos neste horário específico.</p>
    <div class="calendar-modal-actions">
      <button type="button" id="btn-bloquear-horario" class="calendar-btn calendar-btn--primary">Bloquear</button>
      <button type="button" class="calendar-btn calendar-btn--secondary" onclick="mostrarModalOpcoes('${data}')">Voltar</button>
    </div>`;
  document.getElementById('btn-bloquear-horario').onclick = function () {
    fetch('bloquearHorario.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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


 </script>
 <script>






    // Ajuste as funções abrirNovoAgendamento, abrirBloquearHorario, abrirAgendamentoFixo para receber data e hora:
    function abrirNovoAgendamento(data, hora = '') {
      const modalTitle = document.getElementById('modal-title');
      const modalBody = document.getElementById('modal-body');

      if (!modalTitle || !modalBody) {
        return;
      }

      modalTitle.innerText = 'Novo Agendamento em ' + data;
      modalBody.innerHTML = `
    <div id="novo-agendamento-massoterapeuta">
      <div id="escolher-usuario-area">
        <label>Escolha um usuário cadastrado:</label>
        <select id="select-usuario">
          <option value="">Selecione...</option>
        </select>
        <button id="btn-para-visitante" type="button" class="calendar-btn calendar-btn--secondary">Agendar para visitante</button>
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
        <button id="btn-voltar-escolha" type="button" class="calendar-btn calendar-btn--ghost">Voltar</button>
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
      <div class="calendar-modal-actions">
        <button id="btn-agendar-massa" type="button" class="calendar-btn calendar-btn--primary">Agendar</button>
        <button type="button" class="calendar-btn calendar-btn--secondary" onclick="mostrarModalOpcoes('${data}')">Voltar</button>
      </div>
    </div>
`;

      const sel = modalBody.querySelector('#select-usuario');
      const btnParaVisitante = modalBody.querySelector('#btn-para-visitante');
      const btnVoltarEscolha = modalBody.querySelector('#btn-voltar-escolha');
      const btnAgendar = modalBody.querySelector('#btn-agendar-massa');
      const escolherUsuarioArea = modalBody.querySelector('#escolher-usuario-area');
      const formVisitanteArea = modalBody.querySelector('#form-visitante-area');

      if (!sel || !btnParaVisitante || !btnVoltarEscolha || !btnAgendar) {
        return;
      }

      fetch('get_usuarios.php')
        .then(r => r.json())
        .then(lista => {
          if (!sel.isConnected || !Array.isArray(lista)) {
            return;
          }

          lista.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.innerText = u.nome + (u.email ? ' (' + u.email + ')' : '');
            sel.appendChild(opt);
          });
        })
        .catch(() => {
          /* Evita quebra silenciosa caso o fetch falhe. */
        });

      btnParaVisitante.onclick = function () {
        if (escolherUsuarioArea) {
          escolherUsuarioArea.style.display = 'none';
        }
        if (formVisitanteArea) {
          formVisitanteArea.style.display = '';
        }
      };

      btnVoltarEscolha.onclick = function () {
        if (formVisitanteArea) {
          formVisitanteArea.style.display = 'none';
        }
        if (escolherUsuarioArea) {
          escolherUsuarioArea.style.display = '';
        }
      };

      btnAgendar.onclick = function () {
        const especialidadeSelect = modalBody.querySelector('#especialidade_id');
        const duracaoInput = modalBody.querySelector('#duracao');
        const adicionalReflexoCheckbox = modalBody.querySelector('#adicional_reflexo');
        const horaSelecionada = hora || '';

        const dados = {
          data,
          hora: horaSelecionada,
          especialidade_id: especialidadeSelect ? especialidadeSelect.value : '',
          duracao: duracaoInput ? duracaoInput.value : '',
          adicional_reflexo: adicionalReflexoCheckbox && adicionalReflexoCheckbox.checked ? 1 : 0
        };

        const usuarioId = sel.value;
        if (usuarioId) {
          dados.usuario_id = usuarioId;
        } else {
          const guestName = modalBody.querySelector('#guest-name');
          const guestEmail = modalBody.querySelector('#guest-email');
          const guestPhone = modalBody.querySelector('#guest-phone');
          const guestNascimento = modalBody.querySelector('#guest-nascimento');
          const guestSexo = modalBody.querySelector('#guest-sexo');

          dados.guest_name = guestName ? guestName.value : '';
          dados.guest_email = guestEmail ? guestEmail.value : '';
          dados.guest_phone = guestPhone ? guestPhone.value : '';
          dados.guest_nascimento = guestNascimento ? guestNascimento.value : '';
          dados.guest_sexo = guestSexo ? guestSexo.value : '';
        }

        fetch('novo_agendar_massat.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams(dados)
        })
          .then(r => r.json())
          .then(res => {
            if (res.ok) {
              alert('Agendado com sucesso!');
              const modal = document.getElementById('options-modal');
              if (modal) {
                modal.style.display = 'none';
              }
            } else {
              alert(res.msg);
            }
          });
      };
    }


function abrirAgendamentoFixo(data, hora) {
  document.getElementById('modal-title').innerText = 'Agendamento Fixo';
  const modalBody = document.getElementById('modal-body');
  modalBody.innerHTML = `
        <form method="post" action="agendarFixo.php" autocomplete="off">
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
          <label>Repetições:
            <input type="number" name="repeticoes" value="1" min="1" required>
          </label>
          <div class="calendar-modal-actions">
            <button type="submit" class="calendar-btn calendar-btn--primary">Agendar Fixo</button>
            <button type="button" class="calendar-btn calendar-btn--secondary" onclick="mostrarModalOpcoes('${data}')">Voltar</button>
          </div>
        </form>
      `;
  setupAutocomplete('usuario_nome_fixo', 'usuario_id_fixo', 'autocomplete-list-fixo');
  const diaSemanaSelect = modalBody.querySelector('select[name="dia_semana"]');
  if (diaSemanaSelect) {
    const baseDate = new Date(`${data}T00:00:00`);
    if (!Number.isNaN(baseDate.getTime())) {
      const diaSemanaJs = baseDate.getDay(); // 0 (domingo) - 6 (sábado)
      const diaSemana = ((diaSemanaJs + 6) % 7) + 1; // converte para 1=segunda ... 7=domingo
      diaSemanaSelect.value = String(diaSemana);
    }
  }
  const horarioInput = modalBody.querySelector('input[name="horario"]');
  if (horarioInput && hora) {
    horarioInput.value = hora.substring(0, 5);
  }
}


    </script>

    <script>
      (function () {
        const header = document.querySelector('.header-admin');
        if (!header) return;

      const toggle = header.querySelector('.menu-toggle');
      const menuLinks = header.querySelectorAll('.menu-horizontal a');
      if (!toggle) return;

      const closeMenu = () => {
        header.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      };

      toggle.addEventListener('click', () => {
        const isOpen = header.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      menuLinks.forEach((link) => {
        link.addEventListener('click', () => {
          if (header.classList.contains('is-open')) {
            closeMenu();
          }
        });
      });
    })();
  </script>



</body>

</html>
