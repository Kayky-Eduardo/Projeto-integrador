<?php
/*
 * =============================================================
 * ARQUIVO: banco_horas.php
 * MÓDULO: Gestão de Saldo e Compensação (Financeiro/RH)
 * =============================================================
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Página híbrida que atua tanto como consulta individual de saldo 
 * de horas para o colaborador, quanto como relatório consolidado 
 * para o RH (Modo Relatório).
 * * Funcionalidades:
 * - Exibição de Resumo: Saldo anterior, saldo atual e última atualização.
 * - Filtro Histórico: Consulta de evolução de saldo por período (POST).
 * - Relatório Geral (Admin): Listagem de saldos de todos os usuários.
 * - Busca Dinâmica: Filtro client-side para localização de usuários no relatório.
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Valida autenticação e nível de acesso.
 * 2. Carrega biblioteca de funções específicas: 'funcoes_banco_horas.php'.
 * 3. Se Modo Relatório (GET 'relatorio=1') + RH: 
 * - Recupera lista global de saldos (get_banco_horas_todos).
 * 4. Se Modo Usuário (Padrão):
 * - Busca resumo do saldo atual (get_banco_horas).
 * - Se houver requisição POST, busca detalhamento por data (get_banco_data).
 * 5. Renderiza a interface condicional baseada no modo ativo.
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Verificação de privilégios para acesso ao Relatório Geral.
 * - Tratamento de entradas POST para filtros de data.
 * - Escapamento de dados de saída (htmlspecialchars) na listagem de nomes.
 *
 * TABELAS UTILIZADAS (Via Funções Externas)
 * -------------------------------------------------------------
 * 1. banco_horas: Registro de saldos atuais e anteriores.
 * 2. historico_banco_horas: Log de movimentações e atualizações diárias.
 * 3. usuario: Associação de nomes aos registros de saldo.
 *
 * -------------------------------------------------------------
 * Data: 11/03/2026
 * Versão: 1.1
 * ==============================================================
 */

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
                <section class="tabela-padrao">
                    <h2>Banco de Horas - Relatório Geral</h2>
                    <input class="input filtro-texto" type="text" id="filtroUsuario" placeholder="Digite o nome do usuário..." autocomplete="off">
                </section>

                <section class="tabela-padrao">
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
                <section class="bh-resumo" aria-label="Resumo do banco de horas">
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
                <section class="filtro-padrao" aria-label="Filtro por período">
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