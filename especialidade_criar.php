<?php
echo "Conexão bem-sucedida!<br>";
$servidor = "localhost";
$usuario = "root";
$senha = ""; // sem senha
$banco = "terapia_corporal";
$porta = 3307;

$conn = new mysqli($servidor, $usuario, $senha, $banco, $porta);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

?>

