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
        <a href="#">Indicadores</a>
        <a href="/projeto-integrador/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/projeto-integrador/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>


        <ul class="nav-dropdown">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    Relatórios ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/relatorio/meu_banco_horas.php?relatorio=1">
                            Banco de Horas
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/relatorio/online.php">
                            Controle de Usuários
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/ponto/historico.php">
                            Histórico de Pontos
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

        <ul class="nav-dropdown usuario">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    ' . $_SESSION["nome_usuario"] . ' ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/config/config.php">
                            Configurações
                        </a>
                    </li>

                    <li class="separador"></li>

                    <li>
                        <a href="/projeto-integrador/public/logout.php" class="sair">
                            Sair
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    ';
} elseif ($nivel == 2) {
    echo '
        <a href="/projeto-integrador/public/usuario/lista.php">Usuários</a>
        <a href="/projeto-integrador/public/indicadores.php">Indicadores</a>
        <a href="/projeto-integrador/public/ponto/status.php">Bater Ponto / Status</a>
        <a href="/projeto-integrador/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/projeto-integrador/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>


        <ul class="nav-dropdown">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    Relatórios ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/relatorio/meu_banco_horas.php?relatorio=1">
                            Banco de Horas Gerais
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/relatorio/online.php">
                            Controle de Usuários
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/ponto/historico.php">
                            Histórico de Pontos
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

        <ul class="nav-dropdown usuario">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    ' . $_SESSION["nome_usuario"] . ' ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/relatorio/meu_banco_horas.php">
                            Meu Banco de Horas
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/ponto/notificacoes.php">
                            Notificações
                        </a>
                    </li>

                    <li class="separador"></li>

                    <li>
                        <a href="/projeto-integrador/public/logout.php" class="sair">
                            Sair
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    ';
} else {
    echo '
        <a href="/projeto-integrador/public/ponto/status.php">Bater Ponto / Status</a>

        <ul class="nav-dropdown usuario">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    ' . $_SESSION["nome_usuario"] . ' ▾
                </a>

                <ul class="dropdown-menu right">
                    <li>
                        <a href="/projeto-integrador/public/relatorio/meu_banco_horas.php">
                            Meu Banco de Horas
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/ponto/historico.php">
                            Meu Histórico
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/ponto/notificacoes.php">
                            Notificações
                        </a>
                    </li>

                    <li class="separador"></li>

                    <li>
                        <a href="/projeto-integrador/public/logout.php" class="sair">
                            Sair
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    ';
}

echo '</nav>';
