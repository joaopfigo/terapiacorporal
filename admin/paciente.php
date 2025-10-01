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
<title>Paciente: <?= htmlspecialchars($pac['nome']) ?></title>
<style>
  body { font-family:Roboto,Arial,sans-serif; background:#f6f6fa; margin:0; }
  .container { max-width:900px; margin:33px auto 0 auto; background:#fff; border-radius:18px; box-shadow:0 2px 32px #0001; paddi
ng:35px 3vw 33px 3vw; }
  .h2-paciente { color:#30795b; font-size:2rem; font-family:Playfair Display,serif; }
  .subtitulo { color:#444; font-size:1.17rem; margin:12px 0 3px 0; }
  .dados { background:#f3f6f1; border-radius:8px; padding:19px 16px; margin-bottom:21px; font-size:1.03rem; }
  .table-consultas { width:100%; border-collapse:collapse; margin-top:28px; }
  .table-consultas th,.table-consultas td { border-bottom:1px solid #ece3d3; padding:11px 7px; text-align:left; }
  .table-consultas th { background:#e7f0e6; color:#2c6246; font-size:1.05rem; }
  .table-consultas tr:hover { background:#f4f8f7; }
    .bloco-anamnese { background:#f4f4f7; border-radius:8px; padding:14px 14px 11px 14px; margin:15px 0; }
</style>
</head>
<body>
<div class='container'>
  <h2 class='h2-paciente'>Paciente: <?= htmlspecialchars($pac['nome']) ?></h2>
  <div class='dados'>
    <strong>Email:</strong> <?= htmlspecialchars($pac['email']) ?> <br>
    <strong>Telefone:</strong> <?= htmlspecialchars($pac['telefone']) ?> <br>
    <strong>Nascimento:</strong> <?= htmlspecialchars($pac['nascimento']) ?> <br>
    <strong>Sexo:</strong> <?= htmlspecialchars($pac['sexo']) ?> <br>
    <div style="margin-top:10px;">
      <strong>Pacote atual:</strong>
      <span id="pacote-atual-text">
        <?php if ($pacote_atual) {
            $restantes = $pacote_atual['total_sessoes'] - $pacote_atual['sessoes_usadas'];
            if ($restantes < 0) $restantes = 0;
            echo $restantes . ' de ' . $pacote_atual['total_sessoes'] . ' sessões restantes';
        } else {
            echo 'Nenhum pacote ativo';
        } ?>
      </span>
      <button id="btn-add-pacote" style="margin-left:10px;padding:4px 12px;background:#30795b;color:#fff;border:none;border-radi
us:6px;cursor:pointer;font-size:0.9rem;">Adicionar pacote</button>
    </div>
  </div>

  <div class='subtitulo'>Histórico de Consultas</div>
  <table class='table-consultas'>
    <tr>
      <th>Data</th>
      <th>Especialidade</th>
      <th>Duração</th>
      <th>Status</th>
      <th>Queixa</th>
      <th>Anamnese</th>
    </tr>
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
    <tr>
      <td><?= date('d/m/Y H:i', strtotime($cons['data_horario'])) ?></td>
      <td><?= htmlspecialchars($servicoTitulo) ?></td>
      <td><?= $cons['duracao'] ?> min</td>
      <td><?= htmlspecialchars($cons['status']) ?></td>
      <td style="text-align:center;">
        <?php if ($tem_formulario): ?>
          <button onclick="toggleFormulario(<?= $agendamento_id ?>)" title="Ver formulário de queixa" style="background:none;bor
der:none;cursor:pointer;font-size:1.2rem;">📋</button>
        <?php else: ?>
          <span title="Sem formulário">&mdash;</span>
        <?php endif; ?>
      </td>
      <td style="text-align:center;">
        <button onclick="toggleAnamnese(<?= $agendamento_id ?>)" style="background:none;border:none;cursor:pointer;">Anamnese</b
utton>
      </td>
    </tr>
    <?php if ($tem_formulario): ?>
    <tr id="formulario-<?= $agendamento_id ?>" style="display:none;">
      <td colspan="6" style="background:#f4f4f4;padding:12px 16px;border-top:1px solid #ddd;">
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
        <div style="margin-top:8px;">
          <button onclick="toggleFormulario(<?= $agendamento_id ?>)" style="background:#ccc;border:none;padding:6px 12px;border-
radius:6px;cursor:pointer;">Fechar</button>
        </div>
      </td>
    </tr>
    <?php endif; ?>
    <tr id="anamnese-<?= $agendamento_id ?>" style="display:none;">
      <td colspan="6" style="background:#f4f4f4;padding:12px 16px;border-top:1px solid #ddd;">
        <textarea id="anamnese-text-<?= $agendamento_id ?>" style="width:100%;min-height:80px;">
<?= isset($anamneses[$agendamento_id][0]) ? htmlspecialchars($anamneses[$agendamento_id][0]['anamnese']) : '' ?></textarea>
        <div style="margin-top:8px;">
          <button onclick="salvarAnamnese(<?= $agendamento_id ?>)" style="background:#30795b;color:#fff;padding:6px 12px;border:
none;border-radius:6px;cursor:pointer;">Salvar</button>
          <button onclick="toggleAnamnese(<?= $agendamento_id ?>)" style="margin-left:8px;background:#ccc;border:none;padding:6p
x 12px;border-radius:6px;cursor:pointer;">Fechar</button>
        </div>
        <?php if (!empty($anamneses[$agendamento_id])): ?>
          <div style="margin-top:12px;">
            <?php foreach ($anamneses[$agendamento_id] as $ana): ?>
              <div class="bloco-anamnese">
                <div><strong>Atualizado:</strong> <?= date('d/m/Y H:i', strtotime($ana['updated_at'])) ?></div>
                <div><?= nl2br(htmlspecialchars($ana['anamnese'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </td>
    </tr>
    <?php } ?>
  </table>
</div>
<div id="modal-pacote" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-i
tems:center;justify-content:center;">
  <div style="background:#fff;padding:20px;border-radius:8px;text-align:center;">
    <p>Selecionar pacote:</p>
    <button onclick="comprarPacote(5)" style="margin:5px 10px 5px 0;">5 sessões - R$ <?= htmlspecialchars($precos['pacote5']) ?>
</button>
    <button onclick="comprarPacote(10)" style="margin:5px 0;">10 sessões - R$ <?= htmlspecialchars($precos['pacote10']) ?></butt
on>
    <div style="margin-top:15px;"><button onclick="fecharModal()" style="padding:4px 12px;">Cancelar</button></div>
  </div>
</div>

<script>
function toggleFormulario(id){
  const row = document.getElementById('formulario-' + id);
  if(row.style.display==='none'){
    row.style.display='';
  } else {
    row.style.display='none';
  }
}
function toggleAnamnese(id){
  const row = document.getElementById('anamnese-' + id);
  if(row.style.display==='none'){
    row.style.display='';
  } else {
    row.style.display='none';
  }
}
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
btnAdd.addEventListener('click', ()=>{ modalPacote.style.display='flex'; });
function fecharModal(){ modalPacote.style.display='none'; }
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
