<?php
require_once 'conexao.php';

header('Content-Type: application/json');

// Validação
$campos = ['nome', 'email', 'senha', 'nascimento', 'sexo'];
foreach ($campos as $campo) {
    if (empty($_POST[$campo])) {
        echo json_encode(['success' => false, 'message' => "Campo $campo é obrigatório."]);
        exit;
    }
}

$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'] ?? null;
$senha = $_POST['senha'];
$nascimento = $_POST['nascimento'];
$sexo = $_POST['sexo'];

// Calcula a idade
$dataNascimento = new DateTime($nascimento);
$hoje = new DateTime();
$idade = $hoje->diff($dataNascimento)->y;

// Valida se o e-mail já existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado!']);
    exit;
}
$stmt->close();

// Salva no banco com nascimento armazenado
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, telefone, nascimento, sexo, idade, senha_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssis", $nome, $email, $telefone, $nascimento, $sexo, $idade, $senha_hash);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar usuário.']);
}
$stmt->close();
$conn->close();
?>
