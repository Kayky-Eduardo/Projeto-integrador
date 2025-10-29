<?php
// Configurações da conexão
$host = "localhost";    // Servidor do banco
$user = "root";         // Usuário (padrão do xampp)
$pass = "";             // Senha (vazia no xampp por padrão)
$db = "pi_0392";   // nome do banco

// cria a conexão
$conn = new mysqli ($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// $redis = new Redis();
// $redis->connect('127.0.0.1', 6379);
?>