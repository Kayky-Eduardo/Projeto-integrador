<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>RH</title>
</head>
<body>
    <header>
        <h1>Sistema de RH</h1>
            <nav>
                <a href="#">Indefinido</a>
                <a href="#">Indefinido</a>
                <!--php if (isset($_SESSION['usuario_id'])): ?-->
                    <a href="logout.php">Sair</a>
                <!--php endif; ?--> 
            </nav>
        </div>
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
