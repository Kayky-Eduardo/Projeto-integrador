<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel'];

echo '<nav>';

if ($nivel >= 4) {
    echo '
        <a href="usuario/lista.php">Usuários</a>
        <a href="ponto/gerenciar.php">Gerenciar Solicitações</a>
        <a href="ponto/solicitar.php">Solicitar Ajuste de Ponto</a>
        <a href="ponto/historico.php">Histórico</a>
        <a href="ponto/notificacoes.php">Notificações</a>
    ';
} elseif ($nivel == 3) {
    echo '
        <a href="usuario/lista.php">Usuários</a>
        <a href="ponto/status.php">Bater Ponto / Status</a>
        <a href="ponto/gerenciar.php">Gerenciar Solicitações</a>
        <a href="ponto/solicitar.php">Solicitar Ajuste de Ponto</a>
        <a href="ponto/historico.php">Histórico</a>
        <a href="ponto/notificacoes.php">Notificações</a>
    ';
} else {
    echo '
        <a href="ponto/status.php">Bater Ponto / Status</a>
        <a href="ponto/solicitar.php">Solicitar Ajuste de Ponto</a>
        <a href="ponto/historico.php">Histórico</a>
        <a href="ponto/notificacoes.php">Notificações</a>
    ';
}

echo '<a href="logout.php">Sair</a>';
echo '</nav>';
?>
