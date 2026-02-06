<!--
    MÓDULO: BANCO DE HORAS DO USUÁRIO

    OBJETIVO
        Permitir que o usuário autenticado visualize:
        - Seu saldo atual de banco de horas
        - O saldo anterior
        - A data da última atualização
        - O histórico de movimentações por período

    CONTEXTO DAS MODIFICAÇÕES
        Este arquivo foi refatorado para seguir o mesmo padrão estrutural, 
        semântico e organizacional utilizado no restante do sistema.

        As modificações NÃO alteram:
        - As regras de negócio
        - As funções existentes
        - A estrutura do banco de dados
        - A forma como os cálculos são realizados

    PRINCIPAIS ALTERAÇÕES REALIZADAS
        1. Remoção de JavaScript para carregamento de dados
           - O histórico agora é processado diretamente via PHP
           - Elimina dependência de fetch e API intermediária

        2. Substituição de estruturas genéricas por semânticas
           - Remoção de <div> para layout
           - Uso de header, main, section e article

        3. Padronização do fluxo de dados
           - Filtro por período via formulário POST
           - Processamento direto com get_banco_data()

    ESTRUTURA SEMÂNTICA
        header  - Cabeçalho da página e navegação
        main    - Conteúdo principal
        section - Agrupamento funcional de informações
        article - Blocos individuais de dados
        table   - Exibição do histórico de registros

    FUNCIONALIDADES
        1. Exibição do resumo do banco de horas
        2. Filtro de histórico por intervalo de datas
        3. Listagem detalhada de movimentações
        4. Validação de acesso por sessão ativa

    ACESSIBILIDADE
        role="navigation"   - Identificação do navegador
        role="main"         - Conteúdo principal da página
        aria-label          - Descrição semântica das seções

    OBSERVAÇÕES TÉCNICAS
        - Navbar via include
        - Funções reutilizadas de funcoes_banco_horas.php
-->

<?php
session_start();
include("../../BD/conexao.php");
include("../../include/funcoes/funcoes_banco_horas.php");
include("../../include/verificacao.php");

verificar_login($conn);

// Verifica nível de acesso
$nivel = $_SESSION['nivel'] ?? 1;
$modoRelatorio = isset($_GET['relatorio']) && $_GET['relatorio'] == 1;
$ehRH = ($nivel >= 2);

if ($modoRelatorio && !$ehRH) {
    die("Acesso não autorizado.");
}

// Obtém resumo do banco de horas do usuário
$resumo = get_banco_horas($conn, $_SESSION['id_usuario']);
$historico = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inicio = $_POST['inicio'] ?? null;
    $fim = $_POST['fim'] ?? null;

    if ($inicio && $fim) {
        $historico = get_banco_data($conn, $_SESSION['id_usuario'], $inicio, $fim);
    }
}

// Obtém lista de todos os usuários para relatório geral
$listaUsuarios = [];

if ($modoRelatorio && $ehRH) {
    $listaUsuarios = get_banco_horas_todos($conn);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Banco de Horas</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao" aria-label="Banco de horas do usuário">

            <!-- RELATÓRIO GERAL -->
            <?php if ($modoRelatorio && $ehRH): ?>
                <section class="container tabela-padrao">
                    <h2>Banco de Horas - Relatório Geral</h2>
                    <input class="input filtro-texto" type="text" id="filtroUsuario" placeholder="Digite o nome do usuário..." autocomplete="off">
                </section>

                <section class="container tabela-padrao">
                    <table id="tabelaBancoHoras">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Saldo Atual</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($listaUsuarios as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['nome']) ?></td>
                                    <td><?= $u['saldo'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>

            <!-- BANCO DE HORAS DO USUÁRIO -->
            <?php if (!$modoRelatorio): ?>

                <!-- RESUMO -->
                <section class="container bh-resumo" aria-label="Resumo do banco de horas">
                    <article>
                        <h2><?= $resumo['saldo_antigo'] ?? '00:00' ?></h2>
                        <p>Saldo anterior</p>
                    </article>

                    <article>
                        <h2><?= $resumo['saldo_formatado'] ?? '00:00' ?></h2>
                        <p>Saldo atual</p>
                    </article>

                    <article>
                        <h2><?= isset($resumo['ultima_atualizacao']) ? date('d-m-Y', strtotime($resumo['ultima_atualizacao'])) : 'Sem registros' ?></h2>
                        <p>Última atualização</p>
                    </article>
                </section>

                <!-- FILTRO -->
                <section class="container bh-filtro" aria-label="Filtro por período">
                    <form class="form-linha" method="POST">
                        <article>
                            <label class="label" for="inicio">Início:</label>
                            <input class="input" type="date" name="inicio" id="inicio" required>
                        </article>

                        <article>
                            <label class="label" for="fim">Fim:</label>
                            <input class="input" type="date" name="fim" id="fim" required>
                        </article>

                        <button type="submit" class="btn btn-padrao">Aplicar</button>
                    </form>
                </section>

                <!-- HISTÓRICO -->
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <section class="container tabela-padrao" aria-label="Histórico de banco de horas">
                        <?php if ($historico && is_array($historico)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Saldo anterior</th>
                                        <th>Saldo atual</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($historico as $item): ?>
                                        <?php $dataBR = date('d-m-Y', strtotime($item['data'])); ?>

                                        <tr>
                                            <td><?= $dataBR ?></td>
                                            <td><?= formatar_minutos($item['saldo_anterior']) ?></td>
                                            <td><?= formatar_minutos($item['saldo_novo']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <p>Nenhum registro encontrado para o período informado.</p>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>