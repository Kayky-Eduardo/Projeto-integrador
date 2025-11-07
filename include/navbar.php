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
            <a href="relatorio/online.php">Relatórios</a>
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
            <a href="ponto/pausa_config.php">pausa</a>
            <a href="ponto/pausa_iniciar.php">pausa iiniciar</a>
            <a href="ponto/pausa_encerrar.php">pausa encerrar</a>
            <a href="ponto/pausa_auto.php">pausa encerra auto</a>
            <a href="logout.php">Sair</a>
        </nav>
        ';
        break;
}
?>