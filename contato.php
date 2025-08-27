<?php
header('Content-Type: text/plain');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Dados do formulário
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

if (!$nome || !$email || !$mensagem) {
    echo "ERRO";
    exit;
}

// Envio por PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'virleneterapiacorporal@gmail.com'; // email da profissional
    $mail->Password = 'knvy lhjs uvlq fjax'; // senha de app
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('virleneterapiacorporal@gmail.com', 'Site Consultório');
    $mail->addAddress('virleneterapiacorporal@gmail.com', 'Virlene Figueiredo');
    // Se quiser aparecer o email do usuário como reply
    $mail->addReplyTo($email, $nome);

    $mail->isHTML(false);
    $mail->Subject = "Mensagem de contato do site";
    $mail->Body = "Nome: $nome\nEmail: $email\n\nMensagem:\n$mensagem";

    $mail->send();
    echo "SUCESSO";
} catch (Exception $e) {
    echo "ERRO";
}
?>
