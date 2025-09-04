<?php
include 'conexao.php';
session_start();

/**
 * Duplica a última anamnese do usuário (se existir) para o novo agendamento
 * ou cria registro vazio para permitir edição posterior.
 */
function copiarAnamneseAnterior(mysqli $conn, ?int $usuario_id, int $agendamento_id): void {
    $anamnese = '';

    if ($usuario_id) {
        $stmt = $conn->prepare(
            "SELECT a.anamnese
             FROM anamneses a
             INNER JOIN agendamentos g ON g.id = a.agendamento_id
             WHERE g.usuario_id = ?
             ORDER BY g.data_horario DESC
             LIMIT 1"
        );
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $stmt->bind_result($ultimoTexto);
        if ($stmt->fetch() && $ultimoTexto !== null) {
            $anamnese = $ultimoTexto;
        }
        $stmt->close();
    }

    $stmt = $conn->prepare(
        "INSERT INTO anamneses (agendamento_id, anamnese, data_escrita) VALUES (?, ?, NOW())"
    );
    $stmt->bind_param('is', $agendamento_id, $anamnese);
    $stmt->execute();
    $stmt->close();
}

// Coleta todos os campos necessários
$user_id      = $_SESSION['usuario_id'] ?? null;
$servico_id   = $_POST['servico_id'] ?? null;
$data         = $_POST['data'] ?? null;
$hora         = $_POST['hora'] ?? null;
$duracao      = $_POST['duracao'] ?? null;
$add_reflexo  = isset($_POST['add_reflexo']) ? 1 : 0;
$status       = 'Pendente';
$preco_final  = isset($_POST['preco_final']) ? floatval($_POST['preco_final']) : null;

// Campos do formulário de visitante/conta
$criarConta   = isset($_POST['criar_conta']) ? intval($_POST['criar_conta']) : 0;
$nome         = $_POST['guest_name'] ?? '';
$email        = $_POST['guest_email'] ?? '';
$telefone     = $_POST['guest_phone'] ?? '';
$nascimento   = $_POST['guest_nascimento'] ?? '';
$sexo         = $_POST['guest_sexo'] ?? '';
$senha        = $_POST['guest_senha'] ?? '';
$senha2       = $_POST['guest_senha2'] ?? '';

// Validação obrigatória dos campos de agendamento
if (!$servico_id || !$data || !$hora || !$duracao) {
    die("DADOS_INCOMPLETOS");
}
$datetime = "$data $hora:00";

if ($user_id) {
    // Usuário logado: crie o agendamento vinculado ao usuário
    $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, preco_final, adicional_reflexo, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisdiss", $user_id, $servico_id, $datetime, $duracao, $preco_final, $add_reflexo, $status);
    $ok = $stmt->execute();
    if ($ok) {
        $id_agendamento = $stmt->insert_id;
        copiarAnamneseAnterior($conn, $user_id, $id_agendamento);
        require_once __DIR__ . '/lib/wa_hooks.php';
        notifyTherapistNewBooking($id_agendamento);
        die("SUCESSO|$id_agendamento");
    } else {
        die("ERRO_AGENDAR");
    }
} else {
    // Usuário visitante: obrigue o preenchimento dos campos
    if (!$nome || !$email || !$telefone || !$nascimento || !$sexo) {
        die("DADOS_INCOMPLETOS");
    }
    // Se criar conta, processe o cadastro
    if ($criarConta) {
        // Validação básica de senha
        if (!$senha || !$senha2 || $senha !== $senha2) {
            die("SENHA_INVALIDA");
        }
        // Checar se e-mail já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            die("EMAIL_EXISTENTE");
        }
        $stmt->close();
        // Criar usuário
@@ -70,50 +104,51 @@ if ($user_id) {
            die("ERRO_CRIAR_USUARIO");
        }
        $stmt->close();
    }

    // Calcula idade a partir da data de nascimento
    $idade = null;
    if ($nascimento) {
        $dt = DateTime::createFromFormat('Y-m-d', $nascimento);
        if ($dt) {
            $idade = $dt->diff(new DateTime('now'))->y;
        }
    }

    // Crie o agendamento (como visitante OU recém cadastrado)
    $stmt = $conn->prepare("INSERT INTO agendamentos 
    (usuario_id, nome_visitante, email_visitante, telefone_visitante, idade_visitante, especialidade_id, data_horario, duracao, preco_final, adicional_reflexo, status, criado_em) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param(
    "isssiisdiss",
    $user_id, $nome, $email, $telefone, $idade, $servico_id, $datetime, $duracao, $preco_final, $add_reflexo, $status
);
    $ok = $stmt->execute();
    if ($ok) {
        $id_agendamento = $stmt->insert_id;
        copiarAnamneseAnterior($conn, $user_id, $id_agendamento);
        require_once __DIR__ . '/lib/wa_hooks.php';
        notifyTherapistNewBooking($id_agendamento);
        die("SUCESSO|$id_agendamento");
    } else {
        die("ERRO_AGENDAR");
    }
    $stmt->close();
}


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
@@ -175,33 +210,34 @@ try {
$stmt->bind_param(
    "isssiisdiss",
    $user_id, $nome, $email, $telefone, $idade, $servico_id, $datetime, $duracao, $preco_final, $add_reflexo, $status
);
    } else {
        $null = null;
        $stmt = $conn->prepare("INSERT INTO agendamentos 
    (usuario_id, nome_visitante, email_visitante, telefone_visitante, idade_visitante, especialidade_id, data_horario, duracao, preco_final, adicional_reflexo, status, criado_em) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param(
    "isssiisdiss",
    $user_id, $nome, $email, $telefone, $idade, $servico_id, $datetime, $duracao, $preco_final, $add_reflexo, $status
);
    }
    $stmt->execute();
    $agendamentoId = $stmt->insert_id;
    $stmt->close();

    // 4. Se usou pacote, registra uso
    if ($usou_pacote && $pacote_id && $agendamentoId) {
        $stmt = $conn->prepare("INSERT INTO uso_pacote (pacote_id, agendamento_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $pacote_id, $agendamentoId);
        $stmt->execute();
        $stmt->close();
    }
    copiarAnamneseAnterior($conn, $user_id, $agendamentoId);

    $conn->commit();
    echo "SUCESSO|$agendamentoId";
} catch (Exception $e) {
    $conn->rollback();
    die("ERRO_AGENDAR: " . $e->getMessage());
}
?>
