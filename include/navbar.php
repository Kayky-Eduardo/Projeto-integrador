<?php
/*
 * =============================================================
 * ARQUIVO: navbar.php
 * MÓDULO: Navegação Dinâmica do Sistema
 * =============================================================
 *
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Arquivo responsável pela renderização dinâmica do menu de
 * navegação principal do sistema, com base no nível de acesso
 * do usuário autenticado.
 *
 * O menu é exibido de forma condicional conforme o cargo
 * (nível) do usuário armazenado na sessão.
 *
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Verifica se a sessão PHP já foi iniciada
 *    - Caso não, inicia a sessão
 * 2. Obtém o nível de acesso do usuário via $_SESSION['nivel']
 * 3. Renderiza o link padrão de "Início"
 * 4. Avalia o nível de acesso do usuário:
 *    - Nível >= 3 (Administrador)
 *    - Nível == 2 (Gestor)
 *    - Nível < 2 (Usuário comum)
 * 5. Exibe os menus e submenus correspondentes ao perfil
 * 6. Renderiza o menu do usuário autenticado com opções pessoais
 *
 *
 * CONTROLE DE PERMISSÕES
 * -------------------------------------------------------------
 * ° Nível >= 3:
 *   - Acesso completo a usuários, indicadores, relatórios,
 *     gerenciamento de pontos e ajustes pendentes
 *
 * ° Nível == 2:
 *   - Acesso intermediário a usuários, indicadores,
 *     gerenciamento de pontos e relatórios gerais
 *
 * ° Nível < 2:
 *   - Acesso restrito a ponto, histórico, notificações
 *     e banco de horas individual
 *
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Informações sensíveis obtidas exclusivamente da sessão
 * - Nenhuma permissão é baseada em dados vindos do cliente
 * - O controle de acesso real deve ser validado também nos 
 *   arquivos de destino
 *
 *
 * ACESSIBILIDADE
 * -------------------------------------------------------------
 * - Estrutura baseada em listas (<ul> / <li>)
 * - Links navegáveis por teclado
 * - Dropdowns acessíveis via foco (:focus-within)
 * - Texto visível e legível para leitores de tela
 *
 *
 * DEPENDÊNCIAS
 * -------------------------------------------------------------
 * 1. Sessão PHP ativa
 * 2. Variáveis de sessão:
 *    - $_SESSION['nivel']
 *    - $_SESSION['nome_usuario']
 *
 *
 * COMPONENTES RENDERIZADOS
 * -------------------------------------------------------------
 * - Links de navegação principais
 * - Dropdown de relatórios (quando permitido)
 * - Dropdown do usuário autenticado
 * - Link de logout
 *
 *
 * BOAS PRÁTICAS APLICADAS
 * -------------------------------------------------------------
 * - Separação de responsabilidade (menu isolado em include)
 * - Renderização condicional por nível de acesso
 * - Uso de caminhos absolutos para evitar erros de include
 * - Código organizado por blocos de permissão
 *
 *
 * OBSERVAÇÕES
 * -------------------------------------------------------------
 * - Este arquivo não realiza consultas ao banco de dados
 * - O controle de acesso visual não substitui a validação de
 *   permissões nos endpoints
 * - Pode ser facilmente refatorado futuramente para uso com
 *   arrays de configuração ou ACL
 *
 *
 * -------------------------------------------------------------
 * Data: 23/01/2026
 * Versão: 1.0
 * =============================================================
*/

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
        <a href="#">Indicadores</a>
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
