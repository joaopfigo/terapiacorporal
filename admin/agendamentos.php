<?php
session_start();
// Protege acesso: apenas terapeuta/admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php');
    exit;
}

require_once '../conexao.php';
require_once __DIR__ . '/../lib/booking_helpers.php';
header('Content-Type: text/html; charset=utf-8');

// Utilitário para status display
function badge_status($status) {
    $cores = [
        'Pendente' => '#F6C700',
        'Confirmado' => '#44B67B',
        'Cancelado' => '#B6422F',
        'Concluído' => '#6c63ff'
    ];
    $cor = $cores[$status] ?? '#aaa';
    return "<span style='display:inline-block;padding:3px 14px;border-radius:10px;background:$cor;color:#fff;font-size:0.93em;font-weight:600;'>$status</span>";
}

// Pega todos agendamentos
$sql = "SELECT a.*, e.nome as especialidade
        FROM agendamentos a
        LEFT JOIN especialidades e ON a.especialidade_id = e.id
        ORDER BY a.data_horario DESC";
$res = $conn->query($sql);
if (!$res) die('Erro ao buscar agendamentos: ' . $conn->error);

function get_nome_paciente($row) {
    if (!empty($row['usuario_id'])) {
        // Buscar pelo usuário (nome real)
        global $conn;
        $userId = intval($row['usuario_id']);
        $r = $conn->query("SELECT nome FROM usuarios WHERE id = $userId LIMIT 1");
        if ($r && $dados = $r->fetch_assoc()) return htmlspecialchars($dados['nome']);
    }
    // Caso visitante
    return htmlspecialchars($row['nome_visitante'] ?: '-');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Agendamentos</title>
    <link rel="stylesheet" href="../admin.css">
    <style>
        body { font-family: 'Roboto', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 6px 42px #30795b1a; padding: 34px 2vw 23px 2vw; }
        h1 { color: #30795b; font-family: 'Playfair Display', serif; font-size: 2.3rem; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f3f5f3; color: #30795b; font-size: 1.03rem; }
        tr:hover { background: #f9fbe9; }
        .btn-action { padding: 7px 14px; border-radius: 8px; font-size: .97rem; border: none; margin-right: 4px; cursor:pointer; font-weight:600; transition:background .15s; }
        .btn-confirmar { background: #44B67B; color: #fff; }
        .btn-recusar, .btn-cancelar { background: #B6422F; color: #fff; }
        .btn-acessar { background: #6c63ff; color: #fff; }
        .badge-concluido { background: #6c63ff; color: #fff; }
        @media(max-width: 800px){ .container{ padding:13px 2vw; } th, td{ font-size: 0.97em; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Agendamentos</h1>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Paciente</th>
                    <th>Especialidade</th>
                    <th>Data</th>
                    <th>Duração</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()): ?>
                    <?php [$servicoTitulo] = descreverServicos($conn, (int)($row['especialidade_id'] ?? 0), $row['servicos_csv'] ?? null, $row['especialidade'] ?? ''); ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= get_nome_paciente($row) ?></td>
                        <td><?= htmlspecialchars($servicoTitulo) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['data_horario'])) ?></td>
                        <td><?= $row['duracao'] ?> min</td>
                        <td><?= badge_status($row['status']) ?></td>
                        <td>
                            <?php if($row['status'] === 'Pendente'): ?>
                                <form method="post" action="confirmarAgendamento.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-action btn-confirmar">Confirmar</button>
                                </form>
                                <form method="post" action="recusarAgendamento.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-action btn-recusar">Recusar</button>
                                </form>
                            <?php elseif($row['status'] === 'Confirmado'): ?>
                                <form method="post" action="cancelarAgendamento.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-action btn-cancelar">Cancelar</button>
                                </form>
                                <?php if($row['usuario_id']): ?>
                                    <a href="paciente.php?id=<?= $row['usuario_id'] ?>" class="btn-action btn-acessar">Acessar paciente</a>
                                <?php endif; ?>
                            <?php elseif($row['status'] === 'Concluído'): ?>
                                <span class="badge-concluido">Concluído</span>
                                <a href="paciente.php?id=<?= $row['usuario_id'] ?>" class="btn-action btn-acessar">Ver Paciente</a>
                            <?php elseif($row['status'] === 'Cancelado'): ?>
                                <span style="color:#B6422F;font-weight:700;">Cancelado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

