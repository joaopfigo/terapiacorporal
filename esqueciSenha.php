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
// Monta link dinâmico baseado no domínio atual
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
if ($path === '' || $path === '.') {
    $path = '';
}
$resetPath = $path ? $path . '/senha.html' : '/senha.html';
$link = $protocol . $host . $resetPath . '?token=' . urlencode($token);
$linkEscaped = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

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

    $mail->isHTML(true);
    $mail->Subject = 'Alterar senha - Terapia Corporal Sistêmica';
    $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f7f7f7; color: #333333; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 0 auto; padding: 24px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05); }
        h1 { font-size: 20px; color: #1b4332; margin-bottom: 16px; }
        p { font-size: 16px; line-height: 1.5; }
        .button { display: inline-block; margin: 24px 0; padding: 12px 24px; background-color: #2d6a4f; color: #ffffff !important; text-decoration: none; font-weight: bold; border-radius: 8px; }
        .footer { font-size: 12px; color: #6b6b6b; margin-top: 24px; }
        @media (max-width: 480px) {
            .container { padding: 18px; }
            h1 { font-size: 18px; }
            p { font-size: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Redefinição de senha</h1>
        <p>Recebemos uma solicitação para redefinir a senha da sua conta em nosso site. Para continuar, clique no botão abaixo:</p>
        <p style="text-align: center;">
            <a class="button" href="{$linkEscaped}">Redefinir senha</a>
        </p>
        <p>Se o botão não funcionar, copie e cole o link abaixo no seu navegador:</p>
        <p><a href="{$linkEscaped}">{$linkEscaped}</a></p>
        <p class="footer">Se você não solicitou esta alteração, pode ignorar este e-mail com segurança.</p>
    </div>
</body>
</html>
HTML;
    $mail->AltBody = "Olá,\n\nRecebemos uma solicitação para redefinir sua senha. Acesse o link abaixo para continuar:\n$link\n\nSe você não solicitou esta alteração, ignore este e-mail.";

    $mail->send();
    echo json_encode(["success" => true, "message" => "Email enviado com instruções para redefinir a senha."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Erro ao enviar email: {$mail->ErrorInfo}"]);
}
?>

