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
        <a href="/projeto-integrador/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/projeto-integrador/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="/projeto-integrador/public/setor/setores.php">Setores</a>
        <a href="/projeto-integrador/public/cargo/cargos.php">Cargos</a>

        <ul class="nav-dropdown">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    Financeiro ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/folha/gerar_folha.php">
                            Ver Folha
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/folha/historico_folhas.php">Histórico de Folhas</a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/folha/gerar_folhas_todos.php">
                            Gerar Folha de Pagamento
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

        <ul class="nav-dropdown">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    Relatórios ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/indicadores/indicadores.php">
                            Indicadores
                        </a>
                    </li>

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
                </ul>
            </li>
        </ul>

        <ul class="nav-dropdown usuario">
            <li class="dropdown">
                <a href="#" id="nome-usuario-navbar" data-id=' . $_SESSION["id_usuario"] . ' class="dropdown-toggle">
                    ' . $_SESSION["nome_usuario"] . ' ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/config/config.php">
                            Configurações
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/config/pausa_config.php">
                            Cadastrar Pausas
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
        <a href="/projeto-integrador/public/ponto/pausas_e_ponto/registrar_ponto.php">Bater Ponto / Pausa</a>
        <a href="/projeto-integrador/public/ponto/gerenciar.php">Gerenciar Pontos</a>
        <a href="/projeto-integrador/public/rh/ajustes_pendentes.php">Ajustes Pendentes</a>
        <a href="/projeto-integrador/public/setor/setores.php">Setores</a>
        <a href="/projeto-integrador/public/cargo/cargos.php">Cargos</a>

        <ul class="nav-dropdown">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    Financeiro ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/folha/gerar_folha.php">
                            Ver Folha
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/folha/historico_folhas.php">Histórico de Folhas</a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/folha/gerar_folhas_todos.php">
                            Gerar Folha de Pagamento
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

        <ul class="nav-dropdown">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    Relatórios ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/indicadores/indicadores.php">
                            Indicadores
                        </a>
                    </li>

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
                </ul>
            </li>
        </ul>

        <ul class="nav-dropdown usuario">
            <li class="dropdown">
                <a href="#" id="nome-usuario-navbar" data-id=' . $_SESSION["id_usuario"] . ' class="dropdown-toggle">
                    ' . $_SESSION["nome_usuario"] . ' ▾
                </a>

                <ul class="dropdown-menu left">
                    <li>
                        <a href="/projeto-integrador/public/relatorio/meu_banco_horas.php">
                            Meu Banco de Horas
                        </a>
                    </li>

                    <li>
                        <a href="/projeto-integrador/public/ponto/historico.php">
                            Histórico de Pontos
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
        <a href="/projeto-integrador/public/ponto/pausas_e_ponto/registrar_ponto.php">Bater Ponto / Pausa</a>
        <a href="/projeto-integrador/public/folha/gerar_folha.php">Ver Folha</a>

        <ul class="nav-dropdown usuario">
            <li class="dropdown">
                <a href="#" id="nome-usuario-navbar" data-id=' . $_SESSION["id_usuario"] . ' class="dropdown-toggle">
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
                            Histórico de Pontos
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
