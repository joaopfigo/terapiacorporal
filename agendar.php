<?php
session_start();

// Habilita exceções do MySQLi ANTES de conectar (debug temporário)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/conexao.php';

// Sanidade: confirma que $conn existe e é mysqli
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("ERRO_AGENDAR: conexao.php não definiu \$conn (mysqli).");
}
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

/**
 * Define qual serviço deve ser utilizado para calcular preço conforme a duração.
 */
function selecionarServicoPorDuracao(mysqli $conn, array $servicosLista, string $duracao, int $servicoAtual): int
{
    if (!$servicosLista) {
        return $servicoAtual;
    }

    $colunas = [
        '15'      => 'preco_15',
        '30'      => 'preco_30',
        '50'      => 'preco_50',
        '90'      => 'preco_90',
        'escalda' => 'preco_escalda',
        'pacote5' => 'pacote5',
        'pacote10'=> 'pacote10',
    ];

    if (!isset($colunas[$duracao])) {
        return $servicoAtual ?: (int) $servicosLista[0];
    }

    $idsValidos = array_values(array_unique(array_map('intval', $servicosLista)));
    if (!$idsValidos) {
        return $servicoAtual;
    }

    $coluna = $colunas[$duracao];
    if (count($idsValidos) === 1) {
        $stmt = $conn->prepare("SELECT id, $coluna AS preco FROM especialidades WHERE id = ?");
        $stmt->bind_param('i', $idsValidos[0]);
    } else {
        $stmt = $conn->prepare("SELECT id, $coluna AS preco FROM especialidades WHERE id IN (?, ?)");
        $stmt->bind_param('ii', $idsValidos[0], $idsValidos[1]);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $precos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $precos[(int) $row['id']] = $row['preco'];
        }
    }
    $stmt->close();

    foreach ($idsValidos as $id) {
        if (array_key_exists($id, $precos) && $precos[$id] !== null && $precos[$id] !== '') {
            return $id;
        }
    }

    return $servicoAtual ?: (int) $idsValidos[0];
}

// Coleta todos os campos necessários
$user_id       = $_SESSION['usuario_id'] ?? null;
$rawServicos   = isset($_POST['servicos']) ? (string) $_POST['servicos'] : '';
$servicosLista = [];
if ($rawServicos !== '') {
    $servicosLista = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawServicos)))));
}
if (count($servicosLista) > 2) {
    die('SERVICOS_INVALIDOS');
}

$servico_id = isset($_POST['servico_id']) ? (int) $_POST['servico_id'] : 0;
if (!$servico_id && $servicosLista) {
    $servico_id = (int) $servicosLista[0];
    $_POST['servico_id'] = (string) $servico_id;
}

$data         = trim($_POST['data'] ?? '');
$hora         = trim($_POST['hora'] ?? '');
$duracao      = trim($_POST['duracao'] ?? '');

if ($servicosLista) {
    $servico_id = selecionarServicoPorDuracao($conn, $servicosLista, $duracao, $servico_id);
    $_POST['servico_id'] = (string) $servico_id;
}

$add_reflexo  = isset($_POST['add_reflexo']) ? 1 : 0;
$status       = 'Pendente';

if (!$servico_id) {
    die('SERVICO_OBRIGATORIO');
}
if ($servicosLista && !in_array($servico_id, $servicosLista, true)) {
    die('SERVICOS_INVALIDOS');
}

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
$duracaoPermitePacote = in_array($duracao, ['pacote5', 'pacote10'], true);
if ($usou_pacote) {
    if (!$user_id || !$pacote_id || !$duracaoPermitePacote) {
        die('PACOTE_INVALIDO');
    }

    $stmt = $conn->prepare("SELECT total_sessoes, sessoes_usadas FROM pacotes WHERE id = ? AND usuario_id = ? LIMIT 1");
    $stmt->bind_param('ii', $pacote_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($totalSessoes, $sessoesUsadas);
    if (!$stmt->fetch()) {
        $stmt->close();
        die('PACOTE_INVALIDO');
    }
    $stmt->close();

    if ($sessoesUsadas >= $totalSessoes) {
        die('PACOTE_INVALIDO');
    }
} else {
    $pacote_id = null;
}

$required = ['data' => $data, 'hora' => $hora, 'duracao' => $duracao];
$missing = [];
foreach ($required as $campo => $valor) {
    if ($valor === '' || $valor === null) {
        $missing[] = $campo;
    }
}
if ($missing) {
    die('DADOS_INCOMPLETOS: ' . implode(',', $missing));
}

// (opcional) checagem simples de formato de data/hora
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
    die('DADOS_INCOMPLETOS: data/hora inválidas');
}

// Normalizações finais
$datetime     = $data . ' ' . $hora . ':00';
$servicosCsv  = count($servicosLista) === 2 ? implode(',', $servicosLista) : null;


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

if ($servicosLista) {
    if (count($servicosLista) === 2) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM especialidades WHERE id IN (?, ?)");
        $stmt->bind_param('ii', $servicosLista[0], $servicosLista[1]);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM especialidades WHERE id = ?");
        $stmt->bind_param('i', $servicosLista[0]);
    }
    $stmt->execute();
    $stmt->bind_result($qtdValidas);
    $stmt->fetch();
    $stmt->close();
    if ((int)$qtdValidas !== count($servicosLista)) {
        die('SERVICOS_INVALIDOS');
    }
}

// Adicional de Escalda Pés (opcional)
$preco_reflexo = 0.0;
if ($add_reflexo) {
    $stmt = $conn->prepare("SELECT preco_escalda FROM especialidades WHERE id = 9 LIMIT 1");
    $stmt->execute();
    $resRef = $stmt->get_result();
    if ($resRef && $resRef->num_rows > 0) {
        $ref = $resRef->fetch_assoc();
        $preco_reflexo = (float)$ref['preco_escalda']; // não depende de 15/30/50/90
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

$idade = null;

// Se for visitante, validar dados e criar conta se necessário
if (!$user_id) {
    $guestRequired = [
        'guest_name'  => trim($nome),
        'guest_email' => trim($email),
        'guest_phone' => trim($telefone),
    ];

    $missingGuest = [];
    foreach ($guestRequired as $campo => $valor) {
        if ($valor === '') {
            $missingGuest[] = $campo;
        }
    }

    if ($missingGuest) {
        die('DADOS_INCOMPLETOS: ' . implode(',', $missingGuest));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('EMAIL_INVALIDO');
    }

    $dtNascimento = null;
    if ($nascimento !== '') {
        $dtNascimento = DateTime::createFromFormat('Y-m-d', $nascimento);
        if (!$dtNascimento || $dtNascimento->format('Y-m-d') !== $nascimento) {
            die('NASCIMENTO_INVALIDO');
        }
        $idade = $dtNascimento->diff(new DateTime('now'))->y;
    }

    if ($criarConta) {
        if ($senha === '' || $senha2 === '') {
            die('SENHA_OBRIGATORIA');
        }
        if ($senha !== $senha2) {
            die('SENHAS_DIFERENTES');
        }
        if (!$dtNascimento) {
            die('NASCIMENTO_OBRIGATORIO');
        }

        $stmt = null;
        try {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO usuarios (nome, email, telefone, nascimento, sexo, senha_hash, criado_em) VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('ssssss', $nome, $email, $telefone, $nascimento, $sexo, $senha_hash);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($stmt instanceof mysqli_stmt) {
                $stmt->close();
            }

            if ((int) $e->getCode() === 1062) {
                $stmtBusca = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
                $stmtBusca->bind_param('s', $email);
                $stmtBusca->execute();
                $stmtBusca->bind_result($usuarioExistente);
                if ($stmtBusca->fetch()) {
                    $user_id = (int) $usuarioExistente;
                    $stmtBusca->close();
                } else {
                    $stmtBusca->close();
                    throw $e;
                }
            } else {
                throw $e;
            }
        }
    }
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "SELECT 1 FROM agendamentos WHERE data_horario = ? AND status IN ('Pendente','Confirmado','Concluido','Indisponivel','Indisponível') LIMIT 1 FOR UPDATE"
    );
    $stmt->bind_param('s', $datetime);
    $stmt->execute();
    $stmt->store_result();
    $ocupado = $stmt->num_rows > 0;
    $stmt->close();

    if ($ocupado) {
        $conn->rollback();
        die('HORARIO_INDISPONIVEL');
    }

    if ($user_id) {
        $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, especialidade_id, data_horario, duracao, preco_final, adicional_reflexo, status, servicos_csv, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisidiss", $user_id, $servico_id, $datetime, $duracao_db, $preco_final, $add_reflexo, $status, $servicosCsv);
    } else {
        $stmt = $conn->prepare("INSERT INTO agendamentos (usuario_id, nome_visitante, email_visitante, telefone_visitante, idade_visitante, especialidade_id, data_horario, duracao, preco_final, adicional_reflexo, status, servicos_csv, criado_em) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssiisidiss", $nome, $email, $telefone, $idade, $servico_id, $datetime, $duracao_db, $preco_final, $add_reflexo, $status, $servicosCsv);
    }

    $stmt->execute();
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
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    if ((int) $e->getCode() === 1062) {
        die('HORARIO_INDISPONIVEL');
    }
    die("ERRO_AGENDAR: " . $e->getMessage());
} catch (Exception $e) {
    $conn->rollback();
    die("ERRO_AGENDAR: " . $e->getMessage());
}

?>
