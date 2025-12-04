<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel'];

echo '<nav>';

if ($nivel >= 3) {
    echo '
        <a href="usuario/lista.php">Usuários</a>
        <a href="ponto/gerenciar.php">Gerenciar Solicitações</a>
        <a href="ponto/historico.php">Histórico</a>
    ';
} elseif ($nivel == 2) {
    echo '
        <a href="usuario/lista.php">Usuários</a>
        <a href="ponto/status.php">Bater Ponto / Status</a>
        <a href="ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="ponto/historico.php">Histórico</a>
        <a href="ponto/notificacoes.php">Notificações</a>
    ';
} else {
    echo '
        <a href="ponto/status.php">Bater Ponto / Status</a>
        <a href="ponto/historico.php">Histórico</a>
        <a href="ponto/notificacoes.php">Notificações</a>
    ';
}

echo '<a href="logout.php">Sair</a>';
echo '</nav>';