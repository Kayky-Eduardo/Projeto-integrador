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
            <a href="ponto/pausa.php">pausas ativas</a>
            <a href="ponto/pausa_config.php">pausas criar</a>
            <a href="logout.php">Sair</a>
            <a href="gerar_folha.php">Ver Folha</a>
            <a href="historico_folhas.php">Historico folha</a>
            <a href="gerar_folhas_todos.php">Ver Folha todos</a>
        </nav>
        ';
        break;
}
?>