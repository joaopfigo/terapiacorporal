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
$duracao      = $_POST['duracao'] ?? null; // pode vir 15/30/50/90 ou rótulos (escalda/pacote5/pacote10)
$add_reflexo  = isset($_POST['add_reflexo']) ? 1 : 0;
$status       = 'Pendente';

// Campos do formulário de visitante/conta
$criarConta   = isset($_POST['criar_conta']) ? intval($_POST['criar_conta']) : 0;
$nome         = $_POST['guest_name'] ?? '';
$email        = $_POST['guest_email'] ?? '';
$telefone     = $_POST['guest_phone'] ?? '';
$nascimento   = $_POST['guest_nascimento'] ?? '';
$sexo         = $_POST['guest_sexo'] ?? '';
$senha        = $_POST['guest_senha'] ?? '';
$senha2       = $_POST['guest_senha2'] ?? '';

// Campos relacionados a pacotes
$usou_pacote = isset($_POST['usou_pacote']) ? intval($_POST['usou_pacote']) : 0;
$pacote_id   = isset($_POST['pacote_id']) ? intval($_POST['pacote_id']) : null;

// Validação obrigatória dos campos de agendamento
if (!$servico_id || !$data || !$hora || !$duracao) {
    die("DADOS_INCOMPLETOS");
}
$datetime = "$data $hora:00";

// Confere o preço oficial do serviço para evitar manipulação do cliente
$stmt = $conn->prepare("SELECT preco_15, preco_30, preco_50, preco_90, preco_escalda, pacote5, pacote10 FROM especialidades WHERE id = ?");
$stmt->bind_param('i', $servico_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    die("SERVICO_INEXISTENTE");
}
$servico = $result->fetch_assoc();
$stmt->close();

switch ((string)$duracao) {
    case '15':       $preco_oficial = $servico['preco_15'];      break;
    case '30':       $preco_oficial = $servico['preco_30'];      break;
    case '50':       $preco_oficial = $servico['preco_50'];      break;
    case '90':       $preco_oficial = $servico['preco_90'];      break;
    case 'escalda':  $preco_oficial = $servico['preco_escalda']; break;
    case 'pacote5':  $preco_oficial = $servico['pacote5'];       break;
    case 'pacote10': $preco_oficial = $servico['pacote10'];      break;
    default:         die("PRECO_INVALIDO");
}
if ($preco_oficial === null) {
    die("SERVICO_SEM_PRECO");
}

// Adicional de Reflexologia (opcional)
$preco_reflexo = 0.0;
if ($add_reflexo) {
    $stmt = $conn->prepare("SELECT preco_15, preco_30, preco_50, preco_90, preco_escalda, pacote5, pacote10 FROM especialidades WHERE nome = 'Reflexologia Podal' LIMIT 1");
    $stmt->execute();
    $resRef = $stmt->get_result();
    if ($resRef && $resRef->num_rows > 0) {
        $reflexo = $resRef->fetch_assoc();
        switch ((string)$duracao) {
            case '15':       $preco_reflexo = $reflexo['preco_15'];      break;
            case '30':       $preco_reflexo = $reflexo['preco_30'];      break;
            case '50':       $preco_reflexo = $reflexo['preco_50'];      break;
            case '90':       $preco_reflexo = $reflexo['preco_90'];      break;
            case 'escalda':  $preco_reflexo = $reflexo['preco_escalda']; break;
            case 'pacote5':  $preco_reflexo = $reflexo['pacote5'];       break;
            case 'pacote10': $preco_reflexo = $reflexo['pacote10'];      break;
        }
    }
    $stmt->close();
}

// Preço final (zera se usou pacote)
$preco_final = (float)$preco_oficial + (float)$preco_reflexo;
if ($usou_pacote) {
    $preco_final = 0.0;
}

// Normaliza duração para o banco (schema = INT)
$duracao_db = is_numeric($duracao) ? (int)$duracao : 0; // 0 como sentinela caso venha rótulo

// Se for visitante, validar dados e criar conta se necessário
if (!$user_id) {
    if (!$nome || !$email || !$telefone || !$nascimento || !$sexo) {
        die("DADOS_INCOMPLETOS");
    }
    if ($criarConta) {
        if (!$senha || !$senha2 || $senha !== $senha2) {
            die("SENHA_INVALIDA");
        }
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            die("EMAIL_EXISTENTE");
        }
        $stmt->close();
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, telefone, nascimento, sexo, senha_hash) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("ERRO_CRIAR_USUARIO");
        }
        $stmt->bind_param("ssssss", $nome, $email, $telefone, $nascimento, $sexo, $senha_hash);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
        } else {
            die("ERRO_CRIAR_USUARIO");
        }
        $stmt->close();
    }

    $idade = null;
    if ($nascimento) {
        $dt = DateTime::createFromFormat('Y-m-d', $nascimento);
        if ($dt) {
            $idade = $dt->diff(new DateTime('now'))->y;
        }
    }
}

try {
    $conn->begin_transaction();

    if ($user_id) {
        $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, preco_final, adicional_reflexo, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisidis", $user_id, $servico_id, $datetime, $duracao_db, $preco_final, $add_reflexo, $status);
    } else {
        $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, nome_visitante, email_visitante, telefone_visitante, idade_visitante, especialidade_id, data_horario, duracao, preco_final, adicional_reflexo, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssiisidis", $user_id, $nome, $email, $telefone, $idade, $servico_id, $datetime, $duracao_db, $preco_final, $add_reflexo, $status);
    }

    if (!$stmt->execute()) {
        throw new Exception('ERRO_AGENDAR');
    }
    $id_agendamento = $stmt->insert_id;
    $stmt->close();

    if ($usou_pacote && $pacote_id) {
        $stmt = $conn->prepare("UPDATE pacotes SET sessoes_usadas = sessoes_usadas + 1 WHERE id = ?");
        $stmt->bind_param("i", $pacote_id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new Exception('PACOTE_INVALIDO');
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO uso_pacote (pacote_id, agendamento_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $pacote_id, $id_agendamento);
        $stmt->execute();
        $stmt->close();
    }

    copiarAnamneseAnterior($conn, $user_id, $id_agendamento);

    $conn->commit();

    require_once __DIR__ . '/lib/wa_hooks.php';
    notifyTherapistNewBooking($id_agendamento);

    echo "SUCESSO|$id_agendamento";
} catch (Exception $e) {
    $conn->rollback();
    die("ERRO_AGENDAR: " . $e->getMessage());
}

?>
