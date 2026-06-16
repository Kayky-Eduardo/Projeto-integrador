<?php
/*
 * =============================================================
 * ARQUIVO: notificacoes.php
 * MÓDULO: Central de Notificações de Ponto
 * =============================================================
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Interface responsável pela exibição e gerenciamento das 
 * notificações relacionadas ao sistema de ponto eletrônico.
 *
 * Permite ao usuário visualizar mensagens geradas pelo sistema,
 * acompanhar eventos relacionados aos seus registros de ponto
 * e navegar diretamente para o dia correspondente.
 *
 * Também possibilita o controle de leitura das notificações,
 * incluindo marcação individual (visual) e ação em massa.
 *
 * Funcionalidades:
 * - Listagem de notificações ordenadas por status e data.
 * - Destaque visual para notificações não lidas.
 * - Filtro por período (data inicial e final).
 * - Filtro para exibir apenas notificações não lidas.
 * - Ação para marcar todas as notificações como lidas.
 * - Redirecionamento para visualização do dia do ponto.
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Inicializa sessão e valida autenticação do usuário.
 * 2. Recupera dados do usuário logado (id e nível).
 * 3. Verifica requisição POST:
 *    - Caso acionado, marca todas notificações como lidas.
 *    - Redireciona para evitar reenvio de formulário.
 * 4. Captura filtros via GET:
 *    - Período (data inicial e final).
 *    - Status de leitura (somente não lidas).
 * 5. Monta cláusula WHERE dinâmica:
 *    - Sempre filtra por id do usuário logado.
 *    - Aplica filtros adicionais conforme parâmetros.
 * 6. Executa consulta com JOIN:
 *    - Relaciona notificações com registros de ponto.
 * 7. Ordena resultados:
 *    - Notificações não lidas primeiro.
 *    - Em seguida por data decrescente.
 * 8. Renderiza tabela com dados e ações disponíveis.
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Validação de sessão via verificar_login.
 * - Restrição de acesso: usuário visualiza apenas suas notificações.
 * - Uso de prepared statements para prevenir SQL Injection.
 * - Escapamento de saída com htmlspecialchars (proteção contra XSS).
 * - Redirecionamento após POST (Post/Redirect/Get).
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * 1. notificacoes_ponto: Armazena mensagens e status de leitura.
 * 2. ponto_dia: Referência ao registro de ponto relacionado.
 *
 * OBSERVAÇÕES TÉCNICAS
 * -------------------------------------------------------------
 * - Ordenação prioriza notificações não lidas para melhor UX.
 * - Campo 'lida' funciona como flag booleana (0 = não lida, 1 = lida).
 * - Ação "Marcar todas como lidas" atualiza em lote via SQL.
 * - Filtro de datas utiliza função DATE() para ignorar horário.
 * - Link "Ver dia" reaproveita módulo de histórico com filtro automático.
 * - Classe CSS 'nao-lida' permite destaque visual na interface.
 *
 * -------------------------------------------------------------
 * Data: 17/03/2026
 * Versão: 1.0
 * =============================================================
 */

session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

/* MARCAR TODAS COMO LIDAS  */
if (isset($_POST['marcar_lidas'])) {
    $up = $conn->prepare("UPDATE notificacoes_ponto SET lida = 1 WHERE id_usuario = ?");
    $up->bind_param("i", $id_usuario);
    $up->execute();
    header("Location: notificacoes.php");
    exit;
}

/* FILTROS */
$f_from   = $_GET['from'] ?? '';
$f_to     = $_GET['to'] ?? '';
$somente_nao_lidas = $_GET['nao_lidas'] ?? '';

$where = ["n.id_usuario = ?"];
$params = [$id_usuario];
$types = "i";

if (!empty($f_from)) {
    $where[] = "DATE(n.data_notificacao) >= ?";
    $params[] = $f_from;
    $types .= "s";
}

if (!empty($f_to)) {
    $where[] = "DATE(n.data_notificacao) <= ?";
    $params[] = $f_to;
    $types .= "s";
}

if ($somente_nao_lidas) {
    $where[] = "n.lida = 0";
}

$sql = "
    SELECT 
        n.id_notificacao,     -- ID da notificação
        n.mensagem,           -- Texto da mensagem
        n.data_notificacao,   -- Data e hora
        n.lida,               -- Status (0 = não lida, 1 = lida)
        p.data_ponto,           -- Data do ponto relacionado
        p.data_ponto,           -- Data do ponto relacionado
        p.id_ponto            -- ID do ponto
    FROM notificacoes_ponto n
    INNER JOIN ponto_dia p ON p.id_ponto = n.id_ponto
    WHERE " . implode(" AND ", $where) . "
    ORDER BY n.lida ASC, n.data_notificacao DESC
    -- Mostra primeiro as não lidas, depois por data
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$notificacoes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Notificações</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Notificações</h1>

            <section class="container filtro-padrao linha-acoes">
                <form method="get" class="form-linha">
                    <article>
                        <label class="label">De:</label>
                        <input class="input" type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">
                    </article>

                    <article>
                        <label class="label">Até:</label>
                        <input class="input" type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">
                    </article>

                    <label class="toggle">
                        <input type="checkbox" name="nao_lidas" value="1" <?= $somente_nao_lidas ? 'checked' : '' ?>>
                        <span class="slider"></span>
                        Somente não lidas
                    </label>

                    <button class="btn btn-padrao" type="submit">
                        Filtrar
                    </button>
                </form>

                <form method="post">
                    <button class="btn btn-destaque" type="submit" name="marcar_lidas">
                        Marcar todas como lidas
                    </button>
                </form>
            </section>

            <section class="tabela-padrao">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Mensagem</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $notificacoes->fetch_assoc()): ?>
                            <tr class="<?= $row['lida'] == 0 ? 'nao-lida' : '' ?> ">
                                <td><?= date("d/m/Y H:i", strtotime($row['data_notificacao'])) ?></td>
                                <td><?= htmlspecialchars($row['mensagem']) ?></td>
                                <td>
                                    <?php if ($row['lida']): ?>
                                        <span class="status-badge status-badge-ativo lida">Lida</span>
                                    <?php else: ?>
                                        <span class="status-badge status-badge-inativo">Não lida</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a class="btn-link btn-padrao" href="../ponto/historico.php?from=<?= $row['data_ponto'] ?>&to=<?= $row['data_ponto'] ?>">
                                        Ver dia
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>
</body>

</html>