<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

switch ($_SESSION['nivel']) {
    case $_SESSION['nivel'] >= 4:
        echo '
        <nav>
            <a href="/Projeto-integrador/public/usuario/lista.php">Usuario</a>
            <a href="/Projeto-integrador/public/ponto/relatorio_ponto.php">Ponto</a>
            <a href="/Projeto-integrador/public/relatorio/online.php">Relatórios</a>
            <a href="/Projeto-integrador/public/logout.php">Sair</a>
        </nav>
        ';
        break;
    case 3:
        echo '
        <nav>
            <a href="/Projeto-integrador/public/usuario/lista.php">Usuario</a>
            <a href="/Projeto-integrador/public/logout.php">Sair</a>
        </nav>';
        break;
    default:
        echo '  
        <nav>
            <a href="/Projeto-integrador/public/usuario/lista.php">Usuario</a>
            <a href="/Projeto-integrador/public/ponto/relatorio_ponto.php">Ponto</a>
            <a href="/Projeto-integrador/public/logout.php">Sair</a>
        </nav>
        ';
        break;
}
?>