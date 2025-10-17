<?php
session_start();
if(!isset($_SESSION['id_usuario'])) {
    header("Location: logout.php");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>RH</title>
    <link rel="stylesheet" href="../assets/estilo.css">
</head>
<body>
    <header>
        <?php include("../include/navbar.php");?>
        <h1>Sistema de RH</h1>        
    </header>


<footer>
    <h3>Sistema de RH</h3>
    <p>&copy; 2025 Todos os direitos reservados.</p>
    <ul>
        <li><a href="#">Política de Privacidade</a></li>
        <li><a href="#">Termos de Uso</a></li>
        <li><a href="#">Contato</a></li>
    </ul>
    <p><strong>Suporte:</strong> suporte@rhempresa.com</p>
    <p><strong>Telefone:</strong> (11) 99999-9999</p>


</footer>
