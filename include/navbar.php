<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel'];

echo '<nav>';

// Nível 3 - ADM
if ($nivel >= 3) {
    echo '
        <a href="usuario/lista.php">Usuários</a>
        <a href="ponto/gerenciar.php">Gerenciar Solicitações</a>
        <a href="ponto/historico.php">Histórico</a>
    ';
}
// Nível 2 - RH
elseif ($nivel == 2) {
    echo '
        <a href="usuario/lista.php">Usuários</a>
        <a href="ponto/pausas_e_ponto/registrar_ponto.php">Bater Ponto</a>
        <a href="ponto/pausas_e_ponto/pausa_config.php">Pausas</a>
        <a href="ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="ponto/historico.php">Histórico</a>
        <a href="ponto/notificacoes.php">Notificações</a>
    ';
}
// Nível 1 - Funcionário
else {
    echo '
        <a href="ponto/pausas_e_ponto/registrar_ponto.php">Bater Ponto</a>
        <a href="ponto/pausas_e_ponto/pausa_config.php">Pausas</a>
        <a href="ponto/historico.php">Histórico</a>
        <a href="ponto/notificacoes.php">Notificações</a>
    ';
}

echo '<a href="logout.php">Sair</a>';
echo '<a style="pointer-events: none;">'. $_SESSION["nome_usuario"] .' </a>';
echo '</nav>';
?>