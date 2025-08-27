<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $nascimento = $_POST['nascimento'] ?? '';
    $sexo = $_POST['sexo'] ?? '';

    if ($nome && $email) {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, telefone, nascimento, sexo, senha_hash, criado_em) VALUES (?, ?, ?, ?, ?, '', NOW())");
        $stmt->bind_param("sssss", $nome, $email, $telefone, $nascimento, $sexo);
        $stmt->execute();
        header('Location: pacientes.php?msg=novo');
        exit;
    } else {
        $msg = 'Preencha nome e email.';
    }
}
?>
<!DOCTYPE html>
<html lang='pt-br'>
<head>
<meta charset='UTF-8'>
<title>Criar novo paciente</title>
<link rel='stylesheet' href='admin.css'>
<style>
  body { font-family:Roboto,Arial,sans-serif; background:#f6f6fa; margin:0; }
  .container { max-width:460px; margin:44px auto 0 auto; background:#fff; border-radius:15px; box-shadow:0 2px 28px #0001; padding:35px 32px 29px 32px; }
  h2 { color:#30795b; font-size:1.5rem; }
  label { display:block; margin-top:15px; font-weight:700; color:#455; }
  input, select { width:97%; padding:7px; margin-top:3px; border-radius:5px; border:1px solid #ccc; font-size:1.02rem;}
  button { background:#38b26d; color:#fff; font-weight:700; padding:9px 26px; border-radius:7px; border:none; font-size:1rem; margin-top:19px; cursor:pointer;}
</style>
</head>
<body>
<div class='container'>
  <h2>Criar novo paciente</h2>
  <?php if ($msg) echo \"<div style='color:red;'>$msg</div>\"; ?>
  <form method='post'>
    <label for='nome'>Nome:</label>
    <input type='text' name='nome' required>
    <label for='email'>E-mail:</label>
    <input type='email' name='email' required>
    <label for='telefone'>Telefone:</label>
    <input type='text' name='telefone'>
    <label for='nascimento'>Nascimento:</label>
    <input type='date' name='nascimento'>
    <label for='sexo'>Sexo:</label>
    <select name='sexo'>
      <option value=''>Selecione</option>
      <option value='feminino'>Feminino</option>
      <option value='masculino'>Masculino</option>
      <option value='outro'>Outro</option>
    </select>
    <button type='submit'>Salvar</button>
  </form>
</div>
</body>
</html>
