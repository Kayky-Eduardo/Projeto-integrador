<?php
/*
 * =============================================================
 * ARQUIVO: gerenciar.php
 * MÓDULO: Gestão Administrativa de Jornada (RH / Admin)
 * =============================================================
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Interface administrativa para supervisão e auditoria dos 
 * registros de ponto dos colaboradores.
 *
 * Executa:
 * - Listagem consolidada de jornadas (Entrada/Saída/Pausas).
 * - Filtros dinâmicos por período, nome e status do registro.
 * - Aprovação de pontos finalizados com cálculo automático de
 * tempo.
 * - Disparo de notificações internas para os colaboradores.
 * - Ordenação inteligente priorizando registros pendentes de 
 * revisão.
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Verifica privilégios de acesso (Nível >= 2).
 * 2. Processa requisições de aprovação (GET 'aprovar'):
 * a. Valida integridade (ponto deve ter início e fim).
 * b. Atualiza status para 'Aprovado'.
 * c. Calcula duração da jornada via TIMESTAMPDIFF no SQL.
 * d. Registra notificação para o usuário proprietário do ponto.
 * 3. Constrói Query SQL dinâmica baseada nos parâmetros de 
 * filtro:
 * - Utiliza LEFT JOIN para vincular múltiplas pausas a um único
 * ponto.
 * 4. Agrupa resultados em um array multidimensional 
 * ($pontos_agrupados) 
 * para evitar duplicidade de linhas na interface.
 * 5. Renderiza tabela operacional com ações contextuais.
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Controle de acesso rigoroso por nível de permissão.
 * - Prepared Statements (bind_param) em filtros e ações de 
 * escrita.
 * - Proteção contra XSS na exibição de nomes e descrições 
 * (htmlspecialchars).
 * - Validação de existência de registro antes de processar 
 * aprovações.
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * 1. ponto_dia: Fonte primária dos registros de jornada.
 * 2. usuario: Consulta de nomes e perfis.
 * 3. pausa / pausa_config: Detalhamento de intervalos 
 * realizados.
 * 4. notificacoes_ponto: Histórico de alertas enviados aos 
 * usuários.
 *
 * OBSERVAÇÕES TÉCNICAS
 * -------------------------------------------------------------
 * - Implementa a lógica de agrupamento de pausas no Backend 
 * para otimizar a renderização do Frontend.
 * - Ordenação via cláusula CASE no SQL para garantir que status 
 * 'Revisar' apareça no topo da lista.
 * - Uso de gatilhos de software para comunicação interna 
 * (Notificações).
 *
 * -------------------------------------------------------------
 * Data: 09/03/2026
 * Versão: 1.0
 * =============================================================
 */

session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
date_default_timezone_set('America/Sao_Paulo');

if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

function criar_notificacao($conn, $id_usuario, $id_ponto, $msg)
{
    $stmt = $conn->prepare("
        INSERT INTO notificacoes_ponto
        (id_usuario, id_ponto, mensagem, data_notificacao)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->bind_param("iis", $id_usuario, $id_ponto, $msg);
    $stmt->execute();
}

if (isset($_GET['aprovar'])) {
    $id = intval($_GET['aprovar']);

    $check = $conn->query("
        SELECT inicio_ponto, fim_ponto
        FROM ponto_dia
        WHERE id_ponto = $id
    ")->fetch_assoc();

    if (!$check) {
        die("Registro não encontrado.");
    }

    if (
        !$check['inicio_ponto'] ||
        !$check['fim_ponto']
    ) {
        die("Não é possível aprovar: ponto incompleto.");
    }

    $stmt = $conn->prepare("UPDATE ponto_dia SET status = 'Aprovado' WHERE id_ponto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $coleta_tempo = $conn->prepare("
        SELECT
        TIMESTAMPDIFF(MINUTE, inicio_ponto, fim_ponto) as duracao
        FROM ponto_dia
        WHERE id_ponto = ?;
    ");

    $coleta_tempo->bind_param("i", $id);
    $coleta_tempo->execute();
    $resultado = $coleta_tempo->get_result();
    $resultado = $resultado->fetch_assoc()['duracao'];

    $user = $conn->query("
        SELECT id_usuario 
        FROM ponto_dia 
        WHERE id_ponto = $id
    ")->fetch_assoc();

    $dados = [
        "resultado" => $resultado,
        "mensagem" => "Ponto aprovado"
    ];

    criar_notificacao($conn, $user['id_usuario'], $id, "Seu ponto foi aprovado.");
    header("Location: gerenciar.php");
    exit;
}

$data_from = $_GET['from'] ?? '';
$data_to   = $_GET['to'] ?? '';
$nome      = $_GET['nome'] ?? '';
$status    = $_GET['status'] ?? '';
$where  = [];
$params = [];
$types  = '';

$sql = "
    SELECT 
        p.*, 
        u.nome_usuario AS nome,
        ps.inicio AS inicio_pausa,
        ps.fim AS fim_pausa,
        pc.descricao_pausa
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
    LEFT JOIN pausa ps ON ps.id_usuario = p.id_usuario AND ps.data = p.data_ponto
    LEFT JOIN pausa_config pc ON pc.id_config = ps.id_config
";

if ($data_from) {
    $where[]  = "p.data_ponto >= ?";
    $params[] = $data_from;
    $types   .= 's';
}

if ($data_to) {
    $where[]  = "p.data_ponto <= ?";
    $params[] = $data_to;
    $types   .= 's';
}

if ($nome) {
    $where[]  = "u.nome_usuario LIKE ?";
    $params[] = "%$nome%";
    $types   .= 's';
}

if ($status) {
    $where[]  = "p.status = ?";
    $params[] = $status;
    $types   .= 's';
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    ORDER BY CASE p.status
        WHEN 'Revisar' THEN 1
        WHEN 'Finalizado' THEN 2
        WHEN 'Em Andamento' THEN 3
        WHEN 'Aprovado' THEN 4
        ELSE 5
    END,
    p.data_ponto DESC,
    p.inicio_ponto ASC
";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$pontos_agrupados = [];

while ($row = $result->fetch_assoc()) {
    $id_ponto = $row['id_ponto'];

    if (!isset($pontos_agrupados[$id_ponto])) {
        $pontos_agrupados[$id_ponto] = [
            'data_ponto'   => $row['data_ponto'],
            'nome'         => $row['nome'],
            'inicio_ponto' => $row['inicio_ponto'],
            'fim_ponto'    => $row['fim_ponto'],
            'status'       => $row['status'],
            'id_ponto'     => $row['id_ponto'],
            'pausas'       => [],
        ];
    }

    if (!empty($row['inicio_pausa'])) {
        $pontos_agrupados[$id_ponto]['pausas'][] = [
            'descricao_pausa' => $row['descricao_pausa'],
            'inicio'          => $row['inicio_pausa'],
            'fim'             => $row['fim_pausa'],
        ];
    }
}

function mostrar($valor, $formato = null)
{
    if (empty($valor)) {
        return "-";
    }

    if ($formato === "data") {
        return date("d/m/Y", strtotime($valor));
    }

    if ($formato === "hora") {
        return date("H:i", strtotime($valor));
    }

    return htmlspecialchars($valor);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Ponto</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Gerenciar Ponto</h1>

            <section class="container filtro-padrao">
                <form method="get" class="form-linha">
                    <article>
                        <label class="label">De:</label>
                        <input type="date" name="from" class="input" value="<?= htmlspecialchars($data_from) ?>">
                    </article>

                    <article>
                        <label class="label">Até:</label>
                        <input type="date" name="to" class="input" value="<?= htmlspecialchars($data_to) ?>">
                    </article>

                    <article>
                        <label class="label">Nome:</label>
                        <input type="text" name="nome" placeholder="Digite o nome do usuário" class="input" value="<?= htmlspecialchars($nome) ?>">
                    </article>

                    <article>
                        <label class="label">Status:</label>
                        <select name="status" class="select-padrao">
                            <option value="">Todos</option>
                            <option value="Em Andamento" <?= $status == 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="Finalizado" <?= $status == 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            <option value="Revisar" <?= $status == 'Revisar' ? 'selected' : '' ?>>Revisar</option>
                            <option value="Aprovado" <?= $status == 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
                        </select>
                    </article>

                    <button type="submit" class="btn btn-padrao">Filtrar</button>
                </form>
            </section>

            <section class="tabela-padrao">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Funcionário</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                            <th>Pausas</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($pontos_agrupados as $r): ?>
                            <tr>
                                <td><?= mostrar($r['data_ponto'], "data") ?></td>
                                <td><?= mostrar($r['nome']) ?></td>
                                <td><?= mostrar($r['inicio_ponto'], "hora") ?></td>
                                <td><?= mostrar($r['fim_ponto'], "hora") ?></td>

                                <td>
                                    <?php
                                    if (empty($r['pausas'])) {
                                        echo "-";
                                    } else {
                                        foreach ($r['pausas'] as $pausa) {
                                            $pausa_inicio = mostrar($pausa['inicio'], "hora");
                                            $pausa_fim    = mostrar($pausa['fim'], "hora");
                                            echo htmlspecialchars(ucfirst($pausa['descricao_pausa'])) . " - $pausa_inicio - $pausa_fim <br>";
                                        }
                                    }
                                    ?>
                                </td>

                                <td><?= mostrar($r['status']) ?></td>

                                <td>
                                    <?php if ($r['status'] !== 'Aprovado' && $r['status'] !== 'Em Andamento' && $r['status'] !== 'Revisar'): ?>
                                        <a class="btn-link btn-padrao" href="gerenciar.php?aprovar=<?= $r['id_ponto'] ?>">Aprovar</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>
</body>

</html>