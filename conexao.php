<?php
$host = "localhost"; // Ou use o host mostrado no painel, se não for localhost
$user = "u565699716_joaopedro"; // Usuário criado no painel
$pass = "Jpfvalle123!";  // Senha que você definiu na criação
$dbname = "u565699716_terapia_corpo"; // Nome completo do banco

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Erro na conexão com o banco de dados: " . $conn->connect_error]));
}
?>