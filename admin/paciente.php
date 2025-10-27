<?php
// admin/paciente.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';
require_once __DIR__ . '/../lib/booking_helpers.php';
$id_paciente = intval($_GET['id'] ?? 0);
if ($id_paciente <= 0) { die('Paciente não encontrado.'); }
// Buscar dados do paciente
$stmt = $conn->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $id_paciente);
$stmt->execute();
$pac = $stmt->get_result()->fetch_assoc();
if (!$pac) { die('Paciente não encontrado.'); }
// Buscar histórico de agendamentos do paciente
$sql = "SELECT a.*, e.nome as especialidade
        FROM agendamentos a
        LEFT JOIN especialidades e ON a.especialidade_id = e.id
        WHERE a.usuario_id = ?
        ORDER BY a.data_horario DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_paciente);
$stmt->execute();
$historico = $stmt->get_result();
// Buscar todos os agendamentos do paciente
$ids_agendamentos = [];
$sql_agds = "SELECT id FROM agendamentos WHERE usuario_id = ?";
$stmt_agds = $conn->prepare($sql_agds);
$stmt_agds->bind_param('i', $id_paciente);
$stmt_agds->execute();
$res_agds = $stmt_agds->get_result();
while ($row = $res_agds->fetch_assoc()) {
    $ids_agendamentos[] = $row['id'];
}
$stmt_agds->close();
$anamneses = [];
if ($ids_agendamentos) {
    $in = implode(',', array_map('intval', $ids_agendamentos));
    $sql_anamnese = "SELECT * FROM anamneses WHERE agendamento_id IN ($in) ORDER BY updated_at DESC";
    $res_ana = $conn->query($sql_anamnese);
    while ($a = $res_ana->fetch_assoc()) {
        $anamneses[$a['agendamento_id']][] = $a;
    }
}
// Pacote atual do paciente
$stmt = $conn->prepare('SELECT total_sessoes, sessoes_usadas FROM pacotes WHERE usuario_id = ? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('i', $id_paciente);
$stmt->execute();
$pacote_atual = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Valores dos pacotes
$precos = ['pacote5' => 0, 'pacote10' => 0];
$res = $conn->query("SELECT pacote5, pacote10 FROM especialidades LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $precos['pacote5'] = $row['pacote5'];
    $precos['pacote10'] = $row['pacote10'];
}
?>
<!DOCTYPE html>
<html lang='pt-br'>
<head>
<meta charset='UTF-8'>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Paciente: <?= htmlspecialchars($pac['nome']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/header-admin.css?v=<?= filemtime(__DIR__ . '/css/header-admin.css') ?>">
<link rel="stylesheet" href="css/paciente.css?v=<?= filemtime(__DIR__ . '/css/paciente.css') ?>">
<style>
.btn-link .anamnese-icon {
  margin-right: 0.4rem;
}
.icon-legend {
  margin-top: 1rem;
  font-size: 0.9rem;
}
.icon-legend span + span {
  margin-left: 1.5rem;
}
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
</style>
</head>
<body>
  <div class="header-admin">
    <div class="header-brand">Painel da Terapeuta</div>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="admin-menu">☰</button>
    <div class="menu-container">
      <div class="menu-horizontal" id="admin-menu">
        <a class="menu-btn" href="agenda.php">Agenda</a>
        <a class="menu-btn active" href="pacientes.php">Pacientes</a>
        <a class="menu-btn" href="precos.php">Preços</a>
        <a class="menu-btn" href="blog.php">Blog</a>
        <a class="menu-btn" href="index.php">Home</a>
      </div>
    </div>
  </div>
<div class='paciente-container'>
  <h2 class='h2-paciente'>Paciente: <?= htmlspecialchars($pac['nome']) ?></h2>
  <div class='dados'>
    <strong>Email:</strong> <?= htmlspecialchars($pac['email']) ?> <br>
    <strong>Telefone:</strong> <?= htmlspecialchars($pac['telefone']) ?> <br>
    <strong>Nascimento:</strong> <?= htmlspecialchars($pac['nascimento']) ?> <br>
    <strong>Sexo:</strong> <?= htmlspecialchars($pac['sexo']) ?> <br>
    <div class="pacote-atual">
      <strong>Pacote atual:</strong>
      <span id="pacote-atual-text" class="pacote-atual-text">
        <?php if ($pacote_atual) {
            $restantes = $pacote_atual['total_sessoes'] - $pacote_atual['sessoes_usadas'];
            if ($restantes < 0) $restantes = 0;
            echo $restantes . ' de ' . $pacote_atual['total_sessoes'] . ' sessões restantes';
        } else {
            echo 'Nenhum pacote ativo';
        } ?>
      </span>
      <button id="btn-add-pacote" class="btn btn-primary btn-sm">Adicionar pacote</button>
    </div>
  </div>

  <div class='subtitulo'>Histórico de Consultas</div>
  <div class="table-consultas-wrapper">
  <table class='table-consultas responsive-table'>
    <thead>
      <tr>
        <th>Data</th>
        <th>Especialidade</th>
        <th>Duração</th>
        <th>Status</th>
        <th class="text-center">Queixa</th>
        <th class="text-center">Anamnese</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($cons = $historico->fetch_assoc()) {
    [$servicoTitulo] = descreverServicos(
        $conn,
        (int)($cons['especialidade_id'] ?? 0),
        $cons['servicos_csv'] ?? null,
        $cons['especialidade'] ?? ''
      );
      $agendamento_id = $cons['id'];
      $tem_formulario = $conn->query("SELECT 1 FROM formularios_queixa WHERE agendamento_id = $agendamento_id LIMIT 1")->num_rows > 0;
    ?>
    <tr class="table-row">
      <td data-label="Data"><?= date('d/m/Y H:i', strtotime($cons['data_horario'])) ?></td>
      <td data-label="Especialidade"><?= htmlspecialchars($servicoTitulo) ?></td>
      <td data-label="Duração"><?= $cons['duracao'] ?> min</td>
      <td data-label="Status"><?= htmlspecialchars($cons['status']) ?></td>
      <td class="text-center" data-label="Queixa">
        <?php if ($tem_formulario): ?>
          <button
            onclick="toggleFormulario(<?= $agendamento_id ?>)"
            title="Ver formulário de queixa"
            class="btn-icon"
            aria-controls="formulario-<?= $agendamento_id ?>"
            aria-expanded="false"
          >📋</button>
        <?php else: ?>
          <span title="Sem formulário">&mdash;</span>
        <?php endif; ?>
      </td>
      <?php
        $anamneseAtual = $anamneses[$agendamento_id][0] ?? null;
        $temAnamnese = !empty($anamneses[$agendamento_id]);
        $iconeAnamnese = $temAnamnese ? '✅' : '✏️';
        $tituloAnamnese = $temAnamnese
          ? 'Anamnese preenchida. Última atualização em ' . date('d/m/Y H:i', strtotime($anamneseAtual['updated_at']))
          : 'Nenhuma anamnese registrada. Clique para preencher.';
        $descricaoStatus = $temAnamnese ? 'Anamnese preenchida' : 'Anamnese não preenchida';
      ?>
      <td class="text-center" data-label="Anamnese">
        <button
          onclick="toggleAnamnese(<?= $agendamento_id ?>)"
          class="btn-link"
          title="<?= htmlspecialchars($tituloAnamnese) ?>"
          aria-describedby="anamnese-legenda"
          aria-controls="anamnese-<?= $agendamento_id ?>"
          aria-expanded="false"
        >
          <span class="anamnese-icon" aria-hidden="true"><?= $iconeAnamnese ?></span>
          <span class="sr-only"><?= htmlspecialchars($descricaoStatus) ?> - </span>
          Anamnese
        </button>
      </td>
    </tr>
    <?php if ($tem_formulario): ?>
    <tr id="formulario-<?= $agendamento_id ?>" class="detail-row is-hidden" hidden>
      <td colspan="6" class="detail-cell">
        <div class="detail-cell-content">
          <?php
            $fq = $conn->query("SELECT * FROM formularios_queixa WHERE agendamento_id = $agendamento_id")->fetch_assoc();
            if ($fq) {
              echo "<strong>Desconforto principal:</strong> ".htmlspecialchars($fq['desconforto_principal'] ?? '')."<br>";
              echo "<strong>Queixa secundária:</strong> ".htmlspecialchars($fq['queixa_secundaria'] ?? '')."<br>";
              echo "<strong>Tempo de desconforto:</strong> ".htmlspecialchars($fq['tempo_desconforto'] ?? '')."<br>";
              echo "<strong>Classificação da dor:</strong> ".htmlspecialchars($fq['classificacao_dor'] ?? '')."<br>";
              echo "<strong>Tratamento médico:</strong> ".htmlspecialchars($fq['tratamento_medico'] ?? '')."<br>";
            }
          ?>
          <div class="detail-actions">
            <button
              onclick="toggleFormulario(<?= $agendamento_id ?>)"
              class="btn btn-secondary btn-sm"
              aria-controls="formulario-<?= $agendamento_id ?>"
              aria-expanded="false"
            >Fechar</button>
          </div>
        </div>
      </td>
    </tr>
    <?php endif; ?>
    <tr id="anamnese-<?= $agendamento_id ?>" class="detail-row is-hidden" hidden>
      <td colspan="6" class="detail-cell">
        <div class="detail-cell-content">
          <div class="anamnese-expanded">
            <div class="anamnese-editor">
              <textarea id="anamnese-text-<?= $agendamento_id ?>" class="anamnese-textarea">
<?= isset($anamneses[$agendamento_id][0]) ? htmlspecialchars($anamneses[$agendamento_id][0]['anamnese']) : '' ?></textarea>
              <div class="detail-actions">
                <button onclick="salvarAnamnese(<?= $agendamento_id ?>)" class="btn btn-primary btn-sm">Salvar</button>
                <button
                  onclick="toggleAnamnese(<?= $agendamento_id ?>)"
                  class="btn btn-secondary btn-sm"
                  aria-controls="anamnese-<?= $agendamento_id ?>"
                  aria-expanded="false"
                >Fechar</button>
              </div>
            </div>
            <?php if (!empty($anamneses[$agendamento_id])): ?>
              <div class="anamnese-historico">
                <?php foreach ($anamneses[$agendamento_id] as $ana): ?>
                  <div class="bloco-anamnese">
                    <div><strong>Atualizado:</strong> <?= date('d/m/Y H:i', strtotime($ana['updated_at'])) ?></div>
                    <div><?= nl2br(htmlspecialchars($ana['anamnese'])) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </td>
    </tr>
    <?php } ?>
    </tbody>
  </table>
  </div>
  <div class="icon-legend" id="anamnese-legenda">
    <strong>Legenda:</strong>
    <span><span aria-hidden="true">✅</span> <span class="sr-only">Ícone de anamnese preenchida</span> Anamnese preenchida</span>
    <span><span aria-hidden="true">✏️</span> <span class="sr-only">Ícone de anamnese pendente</span> Anamnese pendente</span>
  </div>
</div>
<div id="modal-pacote" class="modal-overlay">
  <div class="modal-content">
    <p>Selecionar pacote:</p>
    <div class="modal-actions">
      <button onclick="comprarPacote(5)" class="btn btn-outline">5 sessões - R$ <?= htmlspecialchars($precos['pacote5']) ?></button>
      <button onclick="comprarPacote(10)" class="btn btn-outline">10 sessões - R$ <?= htmlspecialchars($precos['pacote10']) ?></button>
    </div>
    <div class="modal-footer">
      <button onclick="fecharModal()" class="btn btn-secondary btn-sm">Cancelar</button>
    </div>
  </div>
</div>

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

<script>
function updateToggleControls(rowId, expanded) {
  const buttons = document.querySelectorAll('[aria-controls="' + rowId + '"]');
  buttons.forEach((btn) => {
    btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  });
}
function toggleFormulario(id){
  const rowId = 'formulario-' + id;
  const row = document.getElementById(rowId);
  if (row) {
    const isHidden = row.classList.toggle('is-hidden');
    row.hidden = isHidden;
    updateToggleControls(rowId, !isHidden);
  }
}
function toggleAnamnese(id){
  const rowId = 'anamnese-' + id;
  const row = document.getElementById(rowId);
  if (row) {
    const isHidden = row.classList.toggle('is-hidden');
    row.hidden = isHidden;
    updateToggleControls(rowId, !isHidden);
  }
}
function resetDetailRows(){
  document.querySelectorAll('.detail-row').forEach((row) => {
    const controls = row.id
      ? document.querySelectorAll('[aria-controls="' + row.id + '"]')
      : [];
    const anyExpanded = Array.from(controls).some(
      (btn) => btn.getAttribute('aria-expanded') === 'true'
    );
    if (!anyExpanded) {
      row.classList.add('is-hidden');
      row.hidden = true;
      controls.forEach((btn) => {
        btn.setAttribute('aria-expanded', 'false');
      });
    }
  });
}
window.addEventListener('pageshow', resetDetailRows);
document.addEventListener('DOMContentLoaded', resetDetailRows);
function salvarAnamnese(id){
  const txt = document.getElementById('anamnese-text-' + id).value;
  fetch('salvarAnamnese.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ agendamento_id: id, anamnese: txt })
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.sucesso){
      location.reload();
    } else {
      alert(d.mensagem || 'Erro ao salvar anamnese.');
    }
  });
}
const btnAdd = document.getElementById('btn-add-pacote');
const modalPacote = document.getElementById('modal-pacote');
if (btnAdd && modalPacote) {
  btnAdd.addEventListener('click', ()=>{ modalPacote.classList.add('is-visible'); });
}
function fecharModal(){
  if (modalPacote) {
    modalPacote.classList.remove('is-visible');
  }
}
if (modalPacote) {
  modalPacote.addEventListener('click', (event)=>{
    if (event.target === modalPacote) {
      fecharModal();
    }
  });
}
document.addEventListener('keydown', (event)=>{
  if (event.key === 'Escape') {
    fecharModal();
  }
});
function comprarPacote(qtd){
  fetch('comprarPacotePaciente.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ paciente_id: <?= $id_paciente ?>, quantidade: qtd })
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.sucesso){
      alert('Pacote adicionado com sucesso!');
      location.reload();
    } else {
      alert(d.mensagem || 'Erro ao adicionar pacote.');
    }
  });
}
</script>

</body>
</html>
