<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel'];

echo '<nav>';
echo '<a href="/projeto-integrador/public/index.php">Início</a>';

if ($nivel >= 3) {
    echo '
        <a href="/projeto-integrador/public/usuario/lista.php">Usuários</a>
        <a href="/projeto-integrador/public/ponto/pausas_e_ponto/registrar_ponto.php">Bater Ponto / Pausa</a>
        <a href="/projeto-integrador/public/relatorio/online.php">Controle de usuarios</a>
        <a href="/projeto-integrador/public/meu_banco_horas.php">Banco de Horas</a>
        <a href="/projeto-integrador/public/indicadores/indicadores.php">Indicadores</a>
        <a href="/projeto-integrador/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/projeto-integrador/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="/projeto-integrador/public/ponto/historico.php">Histórico</a>
        <a href="/projeto-integrador/public/config/config.php">Configurações</a>
    ';
} elseif ($nivel == 2) {
    echo '
        <a href="/projeto-integrador/public/usuario/lista.php">Usuários</a>
        <a href="/projeto-integrador/public/relatorio/online.php">Controle de usuarios</a>
        <a href="/projeto-integrador/public/meu_banco_horas.php">Banco de Horas</a>
        <a href="/projeto-integrador/public/indicadores/indicadores.php">Indicadores</a>
        <a href="/projeto-integrador/public/ponto/pausas_e_ponto/registrar_ponto.php">Bater Ponto / Pausa</a>
        <a href="/projeto-integrador/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/projeto-integrador/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="/projeto-integrador/public/ponto/historico.php">Histórico</a>
        <a href="/projeto-integrador/public/ponto/notificacoes.php">Notificações</a>
        <a href="/projeto-integrador/public/config/config.php">Configurações</a>
    ';
        
} else {
    echo '
        <a href="/projeto-integrador/public/ponto/pausas_e_ponto/registrar_ponto.php">Bater Ponto / Pausa</a>
        <a href="/projeto-integrador/public/meu_banco_horas.php">Banco de Horas</a>
        <a href="/projeto-integrador/public/ponto/historico.php">Histórico</a>
        <a href="/projeto-integrador/public/ponto/notificacoes.php">Notificações</a>
    ';
}

echo '
    <a href="">'. $_SESSION["nome_usuario"] .' </a>
    <a href="/projeto-integrador/public/logout.php">Sair</a>
';
echo '</nav>';