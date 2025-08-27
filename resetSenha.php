<?php
header('Content-Type: application/json');
require_once 'conexao.php';

// Recebe os dados do POST
$token = $_POST['token'] ?? '';
$senha = $_POST['senha'] ?? '';

// Verifica se todos os campos foram enviados
if (!$token || !$senha) {
    echo json_encode(["success" => false, "message" => "Dados incompletos."]);
    exit;
}

// Busca usuário com token válido e ainda não expirado
$stmt = $conn->prepare("SELECT id, token_expira FROM usuarios WHERE token_recuperacao = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($id, $expira);

// Se não encontrar ou expirou, retorna erro
if (!$stmt->fetch() || strtotime($expira) < time()) {
    echo json_encode(["success" => false, "message" => "Token inválido ou expirado."]);
    exit;
}
$stmt->close();

// Gera hash seguro da nova senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Atualiza senha no banco e limpa o token de recuperação
$stmt = $conn->prepare("UPDATE usuarios SET senha_hash=?, token_recuperacao=NULL, token_expira=NULL WHERE id=?");
$stmt->bind_param("si", $senha_hash, $id);
$stmt->execute();
$stmt->close();

// Mensagem de sucesso
echo json_encode(["success" => true, "message" => "Senha alterada com sucesso!"]);
?>
