<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

switch ($_SESSION['nivel']) {
    case $_SESSION['nivel'] >= 4:
        echo '
        <nav>
            <a href="usuario/lista.php">Usuario</a>
            <a href="ponto/relatorio_ponto.php">Ponto</a>
            <a href="logout.php">Sair</a>
        </nav>
        ';
        break;
    case 3:
        echo '
        <nav>
            <a href="usuario/lista.php">Usuario</a>
            <a href="logout.php">Sair</a>
        </nav>';
        break;
    default:
        echo '  
        <nav>
            <a href="usuario/lista.php">Usuario</a>
            <a href="ponto/relatorio_ponto.php">Ponto</a>
            <a href="logout.php">Sair</a>
        </nav>
        ';
        break;
}
?>