<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel'];

echo '<nav>';
echo '<a href="/PROJETO-INTEGRADOR/public/index.php">Início</a>';

if ($nivel >= 3) {
    echo '
        <a href="/PROJETO-INTEGRADOR/public/usuario/lista.php">Usuários</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/PROJETO-INTEGRADOR/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/historico.php">Histórico</a>
        <a href="/PROJETO-INTEGRADOR/public/config/config.php">Configurações</a>
    ';
} elseif ($nivel == 2) {
    echo '
        <a href="/PROJETO-INTEGRADOR/public/usuario/lista.php">Usuários</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/status.php">Bater Ponto / Status</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/PROJETO-INTEGRADOR/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/historico.php">Histórico</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/notificacoes.php">Notificações</a>
        <a href="/PROJETO-INTEGRADOR/public/config/config.php">Configurações</a>
    ';
} else {
    echo '
        <a href="/PROJETO-INTEGRADOR/public/ponto/status.php">Bater Ponto / Status</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/historico.php">Histórico</a>
        <a href="/PROJETO-INTEGRADOR/public/ponto/notificacoes.php">Notificações</a>
';
}

echo '<a href="/PROJETO-INTEGRADOR/public/logout.php">Sair</a>';
echo '</nav>';