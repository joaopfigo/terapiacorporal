<?php
// admin/paciente.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';
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
$ultimo_agendamento_id = $ids_agendamentos ? max($ids_agendamentos) : 0;
$anamneses = [];
if ($ids_agendamentos) {
    $in = implode(',', array_map('intval', $ids_agendamentos));
    $sql_anamnese = "SELECT * FROM anamneses WHERE agendamento_id IN ($in) ORDER BY data_escrita DESC";
    $res_ana = $conn->query($sql_anamnese);
    while ($a = $res_ana->fetch_assoc()) $anamneses[] = $a;
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
@@ -102,79 +103,79 @@ if ($row = $res->fetch_assoc()) {
  </div>

  <div class='subtitulo'>Histórico de Consultas</div>
  <table class='table-consultas'>
    <tr>
      <th>Data</th>
      <th>Especialidade</th>
      <th>Duração</th>
      <th>Status</th>
      <th>Queixa</th>
    </tr>
    <?php while ($cons = $historico->fetch_assoc()) { 
  $agendamento_id = $cons['id'];
  $tem_formulario = $conn->query("SELECT 1 FROM formularios_queixa WHERE agendamento_id = $agendamento_id LIMIT 1")->num_rows > 0;
?>
<tr>
  <td><?= date('d/m/Y H:i', strtotime($cons['data_horario'])) ?></td>
  <td><?= htmlspecialchars($cons['especialidade']) ?></td>
  <td><?= $cons['duracao'] ?> min</td>
  <td><?= htmlspecialchars($cons['status']) ?></td>
  <td style="text-align:center;">
    <?php if ($tem_formulario): ?>
      <button onclick="toggleFormulario(<?= $agendamento_id ?>)" title="Ver formulário de queixa" style="background:none;border:none;cursor:pointer;font-size:1.2rem;">
        📋
      </button>
    <?php else: ?>
      <span title="Sem formulário">&mdash;</span>
    <?php endif; ?>
  </td>
</tr>
<?php if ($tem_formulario): ?>
<tr id="formulario-<?= $agendamento_id ?>" style="display:none;">
  <td colspan="5" style="background:#f4f4f4;padding:12px 16px;border-top:1px solid #ddd;">
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
      <button onclick="toggleFormulario(<?= $agendamento_id ?>)" style="background:#ccc;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;">Fechar</button>
    </div>
  </td>
</tr>
<?php endif; ?>
<?php } ?>
</table>
  <div class='subtitulo' style='margin-top:40px;'>Anamnese</div>
  <form class='anamnese-form' method='post' action='salvarAnamnese.php'>
    <input type='hidden' name='agendamento_id' value='<?= $ultimo_agendamento_id ?>'>
    <label for='anamnese'>Preencher/Atualizar Anamnese:</label>
    <textarea name='anamnese' required></textarea>
    <button type='submit'>Salvar Anamnese</button>
  </form>

  <?php if ($anamneses) { echo "<div class='subtitulo' style='margin-top:18px;'>Últimas Anamneses</div>"; }
    foreach ($anamneses as $ana) { ?>
      <div class='bloco-anamnese'>
        <div><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($ana['criado_em'])) ?></div>
        <div><?= nl2br(htmlspecialchars($ana['anamnese'])) ?></div>
      </div>
  <?php } ?>

</div>

<div id="modal-pacote" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
  <div style="background:#fff;padding:20px;border-radius:8px;text-align:center;">
    <p>Selecionar pacote:</p>
    <button onclick="comprarPacote(5)" style="margin:5px 10px 5px 0;">5 sessões - R$ <?= htmlspecialchars($precos['pacote5']) ?></button>
    <button onclick="comprarPacote(10)" style="margin:5px 0;">10 sessões - R$ <?= htmlspecialchars($precos['pacote10']) ?></button>
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
