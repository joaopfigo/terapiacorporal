<?php
header('Content-Type: application/json');
require_once 'conexao.php';

// Carrega o PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
if (!$email) {
    echo json_encode(["success" => false, "message" => "Informe seu email."]);
    exit;
}
// Verifica se email existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Email não cadastrado.", "cadastro" => false]);
    exit;
}
$stmt->bind_result($id);
$stmt->fetch();
$stmt->close();
// Gera token aleatório
$token = bin2hex(random_bytes(30));
$expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
// Atualiza usuário com token e expiração
$stmt = $conn->prepare("UPDATE usuarios SET token_recuperacao=?, token_expira=? WHERE id=?");
$stmt->bind_param("ssi", $token, $expira, $id);
$stmt->execute();
$stmt->close();
// Monta link
$link = "http://localhost:8080/TIAPN/senha.html?token=$token";

// Envia email com PHPMailer (SMTP)
$mail = new PHPMailer(true); // Apenas uma vez!
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'virleneterapiacorporal@gmail.com'; // Seu email Google
    $mail->Password = 'knvy lhjs uvlq fjax'; // Senha de app do Google
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('virleneterapiacorporal@gmail.com', 'Recuperação de Senha');
    $mail->addAddress($email);

    $mail->isHTML(false);
    $mail->Subject = "Redefinição de senha";
    $mail->Body = "Olá,\n\nPara redefinir sua senha clique aqui: $link\n\nSe não foi você, ignore este email.";

    $mail->send();
    echo json_encode(["success" => true, "message" => "Email enviado com instruções para redefinir a senha."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Erro ao enviar email: {$mail->ErrorInfo}"]);
}
?>

