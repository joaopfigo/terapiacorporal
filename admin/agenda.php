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

$horariosPadrao = [];
for ($h = 8; $h <= 20; $h++) {
  $horariosPadrao[] = sprintf('%02d:00', $h);
}

$horariosPorData = [];
foreach ($agendamentosCalendario as $a) {
  $status = strtolower($a['status'] ?? '');
  $dataHorario = $a['data_horario'] ?? null;

  if (!$dataHorario) {
    continue;
  }

  $timestamp = strtotime($dataHorario);
  if ($timestamp === false) {
    continue;
  }

  $data = date('Y-m-d', $timestamp);
  $hora = date('H:i', $timestamp);

  switch ($status) {
    case 'confirmado':
      $statusKey = 'confirmado';
      break;
    case 'concluido':
    case 'concluído':
      $statusKey = 'concluido';
      break;
    case 'indisponivel':
    case 'indisponível':
      $statusKey = 'indisponivel';
      break;
    default:
      $statusKey = null;
  }

  if ($statusKey === null) {
    continue;
  }

  if (!isset($horariosPorData[$data])) {
    $horariosPorData[$data] = [
      'confirmado' => [],
      'concluido' => [],
      'indisponivel' => [],
    ];
  }

  $horariosPorData[$data][$statusKey][$hora] = true;
}

foreach ($horariosPorData as &$statusLista) {
  foreach ($statusLista as $statusKey => &$horas) {
    if (!is_array($horas)) {
      $horas = [];
      continue;
    }

    $horas = array_keys($horas);
    sort($horas);
  }
  unset($horas);
}
unset($statusLista);

$datasBloqueadas = [];
foreach ($horariosPorData as $data => $statusLista) {
  $horariosBloqueados = $statusLista['indisponivel'] ?? [];
  if (!empty($horariosBloqueados) && count(array_intersect($horariosPadrao, $horariosBloqueados)) === count($horariosPadrao)) {
    $datasBloqueadas[] = $data;
  }
}
$datasBloqueadas = array_values(array_unique($datasBloqueadas));
sort($datasBloqueadas);

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
$eventos_bloqueados = [];
foreach ($agendamentosCalendario as $a) {
  $status = strtolower($a['status'] ?? '');
  $dataHorario = $a['data_horario'] ?? null;

  if (!$dataHorario) {
    continue;
  }

  if (in_array($status, ['confirmado', 'concluido'], true)) {
    $title = $a['usuario_nome'] ? $a['usuario_nome'] : 'Indisponível';
    $eventos_calendario[] = [
      'title' => $title,
      'start' => $dataHorario,
      'url' => '#agendamento-' . $a['id'],
    ];
  }

  if ($status === 'indisponivel') {
    $dia = date('Y-m-d', strtotime($dataHorario));
    if (!isset($eventos_bloqueados[$dia])) {
      $eventos_bloqueados[$dia] = [
        'title' => '🚫 Indisponível',
        'start' => $dia,
        'allDay' => true,
        'display' => 'block',
        'color' => '#f8d7da',
        'textColor' => '#b04a4a',
        'className' => ['evento-bloqueado'],
      ];
    }
  }
}

if (!empty($eventos_bloqueados)) {
  $eventos_calendario = array_merge($eventos_calendario, array_values($eventos_bloqueados));
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

    .calendar-btn--outline {
      background: #fff;
      color: var(--header-bg);
      border-color: #bcd9cd;
      box-shadow: inset 0 0 0 1px rgba(37, 109, 84, 0.1);
    }

    .calendar-btn--outline:hover,
    .calendar-btn--outline:focus-visible {
      background: var(--action-hover);
      border-color: #8fbfa9;
      color: #1d5f45;
      box-shadow: 0 5px 16px #1d9a7722;
    }

    .calendar-btn.is-active,
    .calendar-btn--outline.is-active {
      background: var(--success);
      color: #fff;
      border-color: #2a8a60;
      box-shadow: 0 6px 20px #1d9a7726;
    }

    .calendar-btn[disabled] {
      opacity: 0.55;
      cursor: not-allowed;
      box-shadow: none;
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

    .cal-admin-day.is-blocked {
      background: #fdecea;
      border-color: #f5c2c7;
      color: #b04a4a;
    }

    .cal-admin-day.is-occupied {
      background: #e7f1ff;
      border-color: #b6d4fe;
      color: #3a6ea5;
    }

    .cal-admin-day.is-blocked:hover,
    .cal-admin-day.is-blocked:focus-visible,
    .cal-admin-day.is-occupied:hover,
    .cal-admin-day.is-occupied:focus-visible {
      background: inherit;
      border-color: inherit;
      box-shadow: none;
      color: inherit;
      transform: none;
    }

    .cal-admin-day .cal-status-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 6px;
    }

    .cal-admin-day .cal-status-text {
      vertical-align: middle;
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

    .fc-daygrid-day.fc-day--domingo {
      background-color: #fff7f0;
    }

    .fc-daygrid-day.fc-day--bloqueado {
      background-image: linear-gradient(135deg, rgba(248, 215, 218, 0.45) 0%, rgba(248, 215, 218, 0.1) 100%);
    }

    .fc-daygrid-day.fc-day--bloqueado .fc-daygrid-day-number {
      color: #b04a4a;
    }

    .fc-day-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      color: #a94442;
      background: rgba(248, 215, 218, 0.6);
      border-radius: 999px;
      padding: 2px 10px;
      margin-bottom: 4px;
    }

    .fc-day-status::before {
      margin-right: 4px;
    }

    .fc-day-status--domingo {
      background: rgba(255, 231, 204, 0.7);
      color: #c77f1a;
    }

    .fc-day-status--bloqueado::before {
      content: '🚫';
    }

    .fc-day-status--domingo::before {
      content: '☀️';
    }

    @media (max-width: 520px) {
      #options-modal {
        padding: 24px 16px;
      }

      .calendar-modal-actions .calendar-btn {
        flex: 1 1 100%;
      }
    }

    .agendamento-novo-layout {
      display: grid;
      gap: 18px;
    }

    @media (min-width: 640px) {
      .agendamento-novo-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    .agendamento-novo-card {
      background: #f7fbfa;
      border-radius: 20px;
      border: 1px solid #d7ebe3;
      padding: 18px 20px 22px;
      box-shadow: 0 8px 28px rgba(29, 154, 119, 0.12);
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .agendamento-novo-card--wide {
      grid-column: 1 / -1;
    }

    .agendamento-novo-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.2rem;
      color: #1f5e45;
      margin: 0;
    }

    .agendamento-novo-section {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .agendamento-novo-section label,
    .agendamento-novo-field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      font-weight: 600;
      color: #305e4f;
      font-size: 0.95rem;
      margin: 0;
    }

    .agendamento-novo-section--visitante {
      background: #ffffff;
      border-radius: 16px;
      border: 1px dashed #cbdad4;
      padding: 14px 16px;
    }

    #form-agendamento-fixo input[type="time"],
    #form-agendamento-fixo input[type="date"],
    #form-agendamento-fixo input[type="number"],
    #form-agendamento-fixo input[type="text"],
    #form-agendamento-fixo input[type="email"],
    #form-agendamento-fixo input[type="tel"],
    #form-agendamento-fixo select {
      border: 1px solid #cfe3db;
      border-radius: 11px;
      padding: 10px 12px;
      font-size: 0.95rem;
      font-weight: 500;
      color: #1f3d35;
      background: #fff;
      box-shadow: inset 0 1px 2px rgba(8, 42, 26, 0.06);
    }

    #form-agendamento-fixo input:focus,
    #form-agendamento-fixo select:focus {
      outline: none;
      border-color: #7fbca3;
      box-shadow: 0 0 0 3px rgba(47, 143, 110, 0.18);
    }

    .agendamento-novo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 14px 16px;
    }

    .agendamento-novo-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .agendamento-novo-helper {
      margin: 0;
      font-size: 0.9rem;
      color: #6f877d;
    }

    .agendamento-novo-extras {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .agendamento-novo-footer {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: flex-end;
    }

    .checkbox-inline {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      color: #305e4f;
    }

    .checkbox-inline input[type="checkbox"] {
      width: auto;
      height: auto;
      margin: 0;
    }

    .pacote-info {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background: #fff9ed;
      border-radius: 16px;
      border: 1px dashed #e5c690;
      padding: 14px 16px;
      transition: background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .pacote-info[data-state="idle"] {
      background: #fdfcf8;
      border-color: #d9e7e1;
    }

    .pacote-info[data-state="available"] {
      background: #f2fbf7;
      border-color: #9ecab8;
    }

    .pacote-info[data-state="active"] {
      background: #e6f6ef;
      border-color: #4fa57c;
      box-shadow: 0 6px 22px rgba(31, 111, 83, 0.18);
    }

    .pacote-info__status {
      margin: 0;
      font-weight: 700;
      color: #2f5447;
    }

    .pacote-info__helper {
      margin: 0;
      font-size: 0.88rem;
      color: #6c8379;
    }

    .pacote-info__texts {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .pacote-info__helper strong {
      color: #214d3b;
    }

    .autocomplete-wrapper {
      position: relative;
    }

    .autocomplete-items {
      position: absolute;
      top: calc(100% + 6px);
      left: 0;
      right: 0;
      z-index: 10100;
      background: #fff;
      border-radius: 12px;
      border: 1px solid #cfe3db;
      box-shadow: 0 12px 26px rgba(18, 65, 47, 0.15);
      overflow: hidden;
    }

    .autocomplete-items div {
      padding: 10px 14px;
      cursor: pointer;
      font-size: 0.95rem;
      color: #274b3e;
    }

    .autocomplete-items div:hover {
      background: #edf7f3;
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


    function setupAutocomplete(inputId, hiddenId, listId, onSelect) {
      const input = document.getElementById(inputId);
      const hidden = document.getElementById(hiddenId);
      const list = document.getElementById(listId);
      const callback = typeof onSelect === 'function' ? onSelect : null;

      if (!input || !hidden || !list) {
        return;
      }

      input.addEventListener('input', function () {
        const val = this.value;
        hidden.value = '';
        list.innerHTML = '';
        if (callback) {
          callback(null);
        }
        if (!val) {
          return;
        }
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
                if (callback) {
                  callback(u);
                }
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
    const datasBloqueadas = <?php echo json_encode($datasBloqueadas, JSON_UNESCAPED_UNICODE); ?>;
    const horariosPorData = <?php echo json_encode($horariosPorData, JSON_UNESCAPED_UNICODE); ?>;
    const horariosPadraoDia = <?php echo json_encode($horariosPadrao, JSON_UNESCAPED_UNICODE); ?>;
    const datasBloqueadasSet = new Set(datasBloqueadas);

    function adicionarIndicadorDia(dayEl, texto, extraClass) {
      const frame = dayEl.querySelector('.fc-daygrid-day-frame');
      if (!frame) {
        return;
      }
      const selector = '.fc-day-status' + (extraClass ? '.' + extraClass : '');
      if (frame.querySelector(selector)) {
        return;
      }
      const badge = document.createElement('span');
      badge.className = 'fc-day-status' + (extraClass ? ' ' + extraClass : '');
      badge.textContent = texto;
      frame.prepend(badge);
    }

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
        dayCellClassNames: function (arg) {
          const classes = [];
          const dateStr = arg.date.toISOString().split('T')[0];
          if (arg.date.getDay() === 0) {
            classes.push('fc-day--domingo');
          }
          if (datasBloqueadasSet.has(dateStr)) {
            classes.push('fc-day--bloqueado');
          }
          return classes;
        },
        dayCellDidMount: function (arg) {
          const dateStr = arg.date.toISOString().split('T')[0];
          if (arg.date.getDay() === 0) {
            adicionarIndicadorDia(arg.el, 'Domingo', 'fc-day-status--domingo');
          }
          if (datasBloqueadasSet.has(dateStr)) {
            adicionarIndicadorDia(arg.el, 'Indisponível', 'fc-day-status--bloqueado');
          }
        },
        dateClick: function (info) {
          if (info.date.getDay() === 0) {
            return;
          }

          if (datasBloqueadasSet.has(info.dateStr)) {
            return;
          }

          const dadosDia = horariosPorData[info.dateStr];
          if (dadosDia) {
            const bloqueiosDia = new Set(dadosDia.indisponivel || []);
            const todosBloqueados = horariosPadraoDia.every(horario => bloqueiosDia.has(horario));
            if (todosBloqueados) {
              datasBloqueadasSet.add(info.dateStr);
              return;
            }
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

      const infoDia = horariosPorData[data] || {};
      const horariosOcupados = new Set([...(infoDia.confirmado || []), ...(infoDia.concluido || [])]);
      const horariosBloqueados = new Set(infoDia.indisponivel || []);

      let html = `<h3 class="modal-subtitle">Horários do dia ${data.split('-').reverse().join('/')}</h3>
    <div class="calendar-modal-grid">`;

      horarios.forEach(h => {
        const ocupado = horariosOcupados.has(h);
        const bloqueado = horariosBloqueados.has(h);
        const isDisabled = ocupado || bloqueado;
        const buttonClasses = ['calendar-btn', 'calendar-btn--secondary', 'cal-admin-day'];
        let label = h;

        if (bloqueado) {
          buttonClasses.push('cal-disabled', 'is-blocked');
          label = '<span class="cal-status-icon" aria-hidden="true">🚫</span><span class="cal-status-text">Bloqueado</span>';
        } else if (ocupado) {
          buttonClasses.push('cal-disabled', 'is-occupied');
          label = '<span class="cal-status-icon" aria-hidden="true">📌</span><span class="cal-status-text">Reservado</span>';
        }

        html += `<button type="button" class="${buttonClasses.join(' ')}" data-date="${data}" data-hora="${h}" ${isDisabled ? 'disabled' : ''} onclick="abrirOpcoesHorario('${data}','${h}')">${label}</button>`;
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
      <button type="button" class="calendar-btn calendar-btn--primary" onclick="abrirAgendamentoNovo('${data}','${hora}')">Agendamento Novo</button>
      <button type="button" class="calendar-btn calendar-btn--secondary" onclick="abrirBloquearHorario('${data}','${hora}')">Bloquear Horário</button>
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






    // Ajuste as funções abrirBloquearHorario e abrirAgendamentoNovo para receber data e hora:


function abrirAgendamentoNovo(data, hora) {
  const modalTitle = document.getElementById('modal-title');
  const modalBody = document.getElementById('modal-body');

  if (!modalTitle || !modalBody) {
    return;
  }

  modalTitle.innerText = 'Agendamento Novo';
  modalBody.innerHTML = `
        <form id="form-agendamento-fixo" method="post" action="agendarFixo.php" autocomplete="off">
          <input type="hidden" name="visitante" id="agendamento-fixo-visitante-flag" value="0">
          <input type="hidden" name="usar_pacote" id="usar-pacote-flag" value="0">
          <input type="hidden" name="pacote_id" id="pacote-id-fixo" value="">
          <div class="agendamento-novo-layout">
            <section class="agendamento-novo-card">
              <h3 class="agendamento-novo-title">Paciente</h3>
              <div class="agendamento-novo-section" id="agendamento-fixo-usuario">
                <label class="agendamento-novo-field" for="usuario_nome_fixo">
                  <span>Paciente cadastrado</span>
                  <div class="autocomplete-wrapper">
                    <input type="text" id="usuario_nome_fixo" placeholder="Digite o nome..." autocomplete="off">
                    <div id="autocomplete-list-fixo" class="autocomplete-items"></div>
                  </div>
                </label>
                <input type="hidden" name="usuario_id" id="usuario_id_fixo">
                <p class="agendamento-novo-helper">Selecione um paciente para vincular este agendamento.</p>
                <div class="agendamento-novo-actions">
                  <button type="button" id="btn-fixo-para-visitante" class="calendar-btn calendar-btn--outline">Agendar para visitante</button>
                </div>
              </div>
              <div id="agendamento-fixo-visitante" class="agendamento-novo-section agendamento-novo-section--visitante" style="display:none;">
                <p class="agendamento-novo-helper"><strong>Visitante:</strong> preencha os dados para um paciente sem cadastro.</p>
                <label class="agendamento-novo-field" for="guest-name-fixo"><span>Nome</span>
                  <input type="text" id="guest-name-fixo" name="guest_name"></label>
                <label class="agendamento-novo-field" for="guest-email-fixo"><span>E-mail</span>
                  <input type="email" id="guest-email-fixo" name="guest_email"></label>
                <label class="agendamento-novo-field" for="guest-phone-fixo"><span>Número de telefone</span>
                  <input type="tel" id="guest-phone-fixo" name="guest_phone" placeholder="+55" pattern="\\+?\\d{2,15}"></label>
                <label class="agendamento-novo-field" for="guest-nascimento-fixo"><span>Data de nascimento</span>
                  <input type="date" id="guest-nascimento-fixo" name="guest_nascimento"></label>
                <label class="agendamento-novo-field" for="guest-sexo-fixo"><span>Sexo</span>
                  <select id="guest-sexo-fixo" name="guest_sexo">
                    <option value="">Selecione...</option>
                    <option value="feminino">Feminino</option>
                    <option value="masculino">Masculino</option>
                    <option value="outro">Outro</option>
                    <option value="prefiro_nao_dizer">Prefiro não dizer</option>
                  </select>
                </label>
                <div class="agendamento-novo-actions">
                  <button type="button" id="btn-fixo-voltar-usuario" class="calendar-btn calendar-btn--ghost">Selecionar usuário cadastrado</button>
                </div>
              </div>
              <div class="pacote-info" id="pacote-info-fixo" data-state="idle">
                <div class="pacote-info__texts">
                  <p class="pacote-info__status" id="pacote-info-status">Selecione um paciente para verificar pacotes.</p>
                  <p class="pacote-info__helper" id="pacote-info-helper">Os pacotes ativos aparecerão aqui.</p>
                </div>
                <div class="agendamento-novo-actions">
                  <button type="button" class="calendar-btn calendar-btn--outline" id="btn-toggle-pacote" disabled>Usar pacote</button>
                </div>
              </div>
            </section>
            <section class="agendamento-novo-card">
              <h3 class="agendamento-novo-title">Detalhes da sessão</h3>
              <div class="agendamento-novo-grid">
                <label class="agendamento-novo-field"><span>Especialidade</span>
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
                <label class="agendamento-novo-field"><span>Dia da semana</span>
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
                <label class="agendamento-novo-field"><span>Horário</span>
                  <input type="time" name="horario" required></label>
                <label class="agendamento-novo-field"><span>Duração (min)</span>
                  <input type="number" name="duracao" value="60" min="1" required></label>
                <label class="agendamento-novo-field"><span>Data de início</span>
                  <input type="date" name="data_inicio" value="${data}" required></label>
                <label class="agendamento-novo-field"><span>Repetições</span>
                  <input type="number" name="repeticoes" value="1" min="1" required></label>
              </div>
              <div class="agendamento-novo-extras">
                <label class="checkbox-inline">
                  <input type="checkbox" name="adicional_reflexo_fixo" value="1"> Adicional Escalda
                </label>
              </div>
            </section>
          </div>
          <div class="agendamento-novo-footer">
            <button type="button" class="calendar-btn calendar-btn--secondary" onclick="mostrarModalOpcoes('${data}')">Voltar</button>
            <button type="submit" class="calendar-btn calendar-btn--primary">Agendar</button>
          </div>
        </form>
      `;

  const usuarioNomeInput = modalBody.querySelector('#usuario_nome_fixo');
  const usuarioIdInput = modalBody.querySelector('#usuario_id_fixo');
  const visitanteFlag = modalBody.querySelector('#agendamento-fixo-visitante-flag');
  const usuarioSection = modalBody.querySelector('#agendamento-fixo-usuario');
  const visitanteSection = modalBody.querySelector('#agendamento-fixo-visitante');
  const visitanteCampos = visitanteSection ? visitanteSection.querySelectorAll('input, select') : [];
  const btnParaVisitante = modalBody.querySelector('#btn-fixo-para-visitante');
  const btnVoltarUsuario = modalBody.querySelector('#btn-fixo-voltar-usuario');
  const pacoteInfoBox = modalBody.querySelector('#pacote-info-fixo');
  const pacoteStatusText = modalBody.querySelector('#pacote-info-status');
  const pacoteHelperText = modalBody.querySelector('#pacote-info-helper');
  const pacoteToggleBtn = modalBody.querySelector('#btn-toggle-pacote');
  const usarPacoteInput = modalBody.querySelector('#usar-pacote-flag');
  const pacoteIdInput = modalBody.querySelector('#pacote-id-fixo');
  const repeticoesInput = modalBody.querySelector('input[name="repeticoes"]');

  let pacoteAtual = null;

  function atualizarPacoteBox(state, statusMsg, helperMsg) {
    if (pacoteInfoBox) {
      pacoteInfoBox.dataset.state = state;
    }
    if (pacoteStatusText && typeof statusMsg === 'string') {
      pacoteStatusText.textContent = statusMsg;
    }
    if (pacoteHelperText) {
      pacoteHelperText.textContent = helperMsg || '';
    }
  }

  function definirUsoPacote(ativar) {
    if (!usarPacoteInput || !pacoteToggleBtn) {
      return;
    }
    usarPacoteInput.value = ativar ? '1' : '0';
    pacoteToggleBtn.classList.toggle('is-active', ativar);
    pacoteToggleBtn.textContent = ativar ? 'Não usar pacote' : 'Usar pacote';
    if (pacoteInfoBox) {
      pacoteInfoBox.dataset.state = ativar ? 'active' : (pacoteAtual ? 'available' : pacoteInfoBox.dataset.state);
    }
  }

  function resetPacoteInfo(baseState) {
    pacoteAtual = null;
    if (pacoteIdInput) {
      pacoteIdInput.value = '';
    }
    if (pacoteToggleBtn) {
      pacoteToggleBtn.disabled = true;
      pacoteToggleBtn.classList.remove('is-active');
      pacoteToggleBtn.textContent = 'Usar pacote';
    }
    definirUsoPacote(false);
    atualizarPacoteBox(baseState || 'idle',
      baseState === 'visitor'
        ? 'Pacotes disponíveis apenas para pacientes cadastrados.'
        : 'Selecione um paciente para verificar pacotes.',
      baseState === 'visitor' ? '' : 'Os pacotes ativos aparecerão aqui.');
  }

  function verificarRepeticoesPacote() {
    if (!pacoteAtual || !repeticoesInput) {
      return;
    }
    const repeticoes = parseInt(repeticoesInput.value, 10) || 0;
    const excedeu = repeticoes > pacoteAtual.restantes;
    if (excedeu) {
      definirUsoPacote(false);
      if (pacoteToggleBtn) {
        pacoteToggleBtn.disabled = true;
      }
      atualizarPacoteBox('available',
        `Pacote disponível: ${pacoteAtual.restantes} de ${pacoteAtual.total} sessões.`,
        'Ajuste as repetições ou agende sem usar o pacote.');
    } else {
      if (pacoteToggleBtn) {
        pacoteToggleBtn.disabled = false;
      }
      atualizarPacoteBox(usarPacoteInput && usarPacoteInput.value === '1' ? 'active' : 'available',
        `Pacote disponível: ${pacoteAtual.restantes} de ${pacoteAtual.total} sessões.`,
        'Ative para descontar uma sessão do pacote a cada encontro.');
    }
  }

  function aplicarDadosPacote(info) {
    pacoteAtual = info;
    if (pacoteIdInput) {
      pacoteIdInput.value = info && info.id ? String(info.id) : '';
    }
    definirUsoPacote(false);
    if (pacoteToggleBtn) {
      pacoteToggleBtn.disabled = !info;
    }
    if (info) {
      atualizarPacoteBox('available',
        `Pacote disponível: ${info.restantes} de ${info.total} sessões.`,
        'Ative para descontar uma sessão do pacote a cada encontro.');
      verificarRepeticoesPacote();
    } else {
      atualizarPacoteBox('empty', 'Este paciente não possui pacote ativo.', 'É possível agendar normalmente sem desconto de pacote.');
    }
  }

  function carregarPacote(usuarioId) {
    if (!pacoteInfoBox) {
      return;
    }
    if (!usuarioId) {
      resetPacoteInfo('idle');
      return;
    }
    atualizarPacoteBox('idle', 'Verificando pacotes disponíveis...', '');
    if (pacoteToggleBtn) {
      pacoteToggleBtn.disabled = true;
    }
    fetch('getPacoteUsuario.php?usuario_id=' + encodeURIComponent(usuarioId))
      .then((response) => {
        if (!response.ok) {
          throw new Error('Erro ao buscar pacote.');
        }
        return response.json();
      })
      .then((data) => {
        if (!data || data.success === false) {
          throw new Error(data && data.message ? data.message : 'Não foi possível carregar pacotes.');
        }
        const pacoteInfo = data.pacote && typeof data.pacote === 'object'
          ? {
            id: Number(data.pacote.id) || null,
            total: Number(data.pacote.total) || 0,
            usadas: Number(data.pacote.usadas) || 0,
            restantes: Number(data.pacote.restantes) || 0,
          }
          : null;
        if (pacoteInfo && (!pacoteInfo.id || pacoteInfo.restantes <= 0)) {
          aplicarDadosPacote(null);
        } else {
          aplicarDadosPacote(pacoteInfo);
        }
      })
      .catch((error) => {
        console.error(error);
        aplicarDadosPacote(null);
        atualizarPacoteBox('empty', 'Não foi possível carregar os dados de pacote.', 'Tente novamente ou prossiga sem utilizar pacote.');
      });
  }

  function atualizarModoVisitante(ativar) {
    if (!usuarioSection || !visitanteSection || !visitanteFlag) {
      return;
    }

    visitanteFlag.value = ativar ? '1' : '0';
    usuarioSection.style.display = ativar ? 'none' : '';
    visitanteSection.style.display = ativar ? '' : 'none';

    if (usuarioNomeInput) {
      usuarioNomeInput.required = !ativar;
      if (ativar) {
        usuarioNomeInput.value = '';
      }
    }

    if (usuarioIdInput) {
      usuarioIdInput.required = !ativar;
      if (ativar) {
        usuarioIdInput.value = '';
      }
    }

    const camposObrigatorios = ['guest_name', 'guest_email', 'guest_phone'];
    visitanteCampos.forEach((campo) => {
      if (!(campo instanceof HTMLElement)) {
        return;
      }

      if (ativar) {
        campo.required = camposObrigatorios.includes(campo.name);
      } else {
        campo.required = false;
        if ('value' in campo) {
          campo.value = '';
        }
      }
    });

    if (ativar) {
      resetPacoteInfo('visitor');
    } else if (usuarioIdInput && usuarioIdInput.value) {
      carregarPacote(usuarioIdInput.value);
    } else {
      resetPacoteInfo('idle');
    }
  }

  function handleSelecaoUsuario(usuarioSelecionado) {
    if (!usuarioIdInput) {
      return;
    }
    if (!usuarioSelecionado) {
      usuarioIdInput.value = '';
      if (!visitanteFlag || visitanteFlag.value !== '1') {
        resetPacoteInfo('idle');
      }
      return;
    }
    usuarioIdInput.value = usuarioSelecionado.id;
    if (!visitanteFlag || visitanteFlag.value !== '1') {
      carregarPacote(usuarioSelecionado.id);
    }
  }

  setupAutocomplete('usuario_nome_fixo', 'usuario_id_fixo', 'autocomplete-list-fixo', handleSelecaoUsuario);

  if (btnParaVisitante) {
    btnParaVisitante.addEventListener('click', function () {
      atualizarModoVisitante(true);
    });
  }

  if (btnVoltarUsuario) {
    btnVoltarUsuario.addEventListener('click', function () {
      atualizarModoVisitante(false);
    });
  }

  if (pacoteToggleBtn) {
    pacoteToggleBtn.addEventListener('click', function () {
      if (!pacoteAtual || pacoteToggleBtn.disabled) {
        return;
      }
      const ativar = usarPacoteInput && usarPacoteInput.value !== '1';
      definirUsoPacote(ativar);
      if (ativar) {
        atualizarPacoteBox('active',
          `Pacote disponível: ${pacoteAtual.restantes} de ${pacoteAtual.total} sessões.`,
          'Este agendamento irá consumir sessões do pacote.');
      } else {
        atualizarPacoteBox('available',
          `Pacote disponível: ${pacoteAtual.restantes} de ${pacoteAtual.total} sessões.`,
          'Ative para descontar uma sessão do pacote a cada encontro.');
      }
    });
  }

  if (repeticoesInput) {
    repeticoesInput.addEventListener('input', function () {
      verificarRepeticoesPacote();
    });
  }

  atualizarModoVisitante(false);

  const diaSemanaSelect = modalBody.querySelector('select[name="dia_semana"]');
  if (diaSemanaSelect) {
    const baseDate = new Date(`${data}T00:00:00`);
    if (!Number.isNaN(baseDate.getTime())) {
      const diaSemanaJs = baseDate.getDay();
      const diaSemana = ((diaSemanaJs + 6) % 7) + 1;
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
