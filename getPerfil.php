<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';

// 1. Verifica autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autenticado"]);
    exit;
}
$user_id = $_SESSION['usuario_id'];

// 2. Busca dados do usuário
$stmt = $conn->prepare("SELECT nome, email, telefone, idade, sexo, foto_perfil, is_admin FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome, $email, $telefone, $idade, $sexo, $foto, $is_admin);
$stmt->fetch();
$stmt->close();

// Monta array de usuário para o frontend
$usuario = [
    "nome" => $nome,
    "email" => $email,
    "telefone" => $telefone,
    "idade" => $idade,
    "sexo" => $sexo,
    "foto" => $foto ?: "img/avatar-user.jpg",
    "is_admin" => (int)$is_admin
];

// 3. Busca agendamentos do usuário (sessões)
$sql = "SELECT ag.id, e.nome as servico, ag.data_horario, ag.duracao, ag.adicional_reflexo, ag.status,
               fq.desconforto_principal, fq.tempo_desconforto, fq.classificacao_dor, fq.tratamento_medico,
               an.resumo, an.orientacoes
        FROM agendamentos ag
        JOIN especialidades e ON e.id = ag.especialidade_id
        LEFT JOIN formularios_queixa fq ON fq.agendamento_id = ag.id
        LEFT JOIN anamneses an ON an.agendamento_id = ag.id
        WHERE ag.usuario_id = ?
        ORDER BY ag.data_horario DESC";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result = $stmt2->get_result();
$sessoes = [];
while ($row = $result->fetch_assoc()) {
    $sessoes[] = [
        "id"         => $row['id'],
        "tratamento" => $row['servico'],
        "data_horario" => $row['data_horario'],
        "duracao"    => $row['duracao'],
        "status"     => $row['status'],
        "reclamacao" => ($row['desconforto_principal'] ? [
            "sintomas"    => $row['desconforto_principal'],
            "tempo"       => $row['tempo_desconforto'],
            "intensidade" => $row['classificacao_dor'],
            "tratamento"  => $row['tratamento_medico']
        ] : null),
        "anamnese" => ($row['resumo'] ? [
            "geral"       => $row['resumo'],
            "orientacoes" => $row['orientacoes']
        ] : null)
    ];
}
$stmt2->close();

if (empty($user_id)) {
   echo json_encode(["erro" => true, "mensagem" => "Usuário não autenticado."]);
   exit;
} 

// Inicializa variáveis de pacote
// 1. Variáveis default
$total = 0;
$usadas = 0;
$sessoes_disp = 0;

// 2. Executa query só se tem $user_id
if (!empty($user_id)) {
    $stmt = $conn->prepare("SELECT total_sessoes, sessoes_usadas FROM pacotes WHERE usuario_id=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $stmt->bind_result($totalDB, $usadasDB);
        if ($stmt->fetch()) {
            $total = (int)$totalDB;
            $usadas = (int)$usadasDB;
            $sessoes_disp = $total - $usadas;
        }
        $stmt->close();
    }
}
// 4. Retorna JSON final
echo json_encode([
    "usuario" => $usuario,
    "sessoes" => $sessoes
]);
?>
