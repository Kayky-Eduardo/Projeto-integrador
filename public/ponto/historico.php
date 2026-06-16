<?php
/*
 * =============================================================
 * ARQUIVO: historico.php
 * MÓDULO: Consulta de Histórico de Jornada (Colaborador / RH)
 * =============================================================
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Interface responsável pela visualização do histórico de registros 
 * de ponto dos colaboradores no sistema.
 *
 * Permite ao usuário consultar jornadas anteriores, incluindo 
 * horários de entrada, saída, pausas realizadas e status do ponto.
 * Também disponibiliza a funcionalidade de solicitação de ajustes 
 * de ponto, respeitando regras de validação de negócio.
 *
 * Funcionalidades:
 * - Listagem de histórico de pontos (Entrada/Saída/Pausas).
 * - Filtros dinâmicos por período e status.
 * - Agrupamento de múltiplas pausas por registro.
 * - Exibição detalhada de intervalos realizados.
 * - Solicitação de ajuste de ponto com validação.
 * - Formatação amigável de datas e horários.
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Inicializa a sessão e valida autenticação do usuário.
 * 2. Recupera dados do usuário logado (id e nível de acesso).
 * 3. Define filtros recebidos via GET (data, status).
 * 4. Aplica controle de permissões:
 *    - Usuários comuns visualizam apenas seus próprios registros.
 * 5. Constrói query SQL dinâmica:
 *    - INNER JOIN com tabela de usuários.
 *    - LEFT JOIN com pausas (ligação por id_usuario + data).
 * 6. Executa consulta utilizando prepared statements.
 * 7. Agrupa resultados em array multidimensional:
 *    - Cada ponto contém múltiplas pausas associadas.
 * 8. Para cada registro:
 *    - Executa validação de ajustes pendentes.
 *    - Define se o usuário pode solicitar ajuste.
 * 9. Renderiza tabela com dados formatados e ações disponíveis.
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Validação de sessão via função verificar_login.
 * - Restrição de dados conforme nível de acesso do usuário.
 * - Uso de prepared statements (bind_param) para evitar SQL Injection.
 * - Escapamento de saída com htmlspecialchars (proteção contra XSS).
 * - Validação de regras de negócio para solicitação de ajustes.
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * 1. ponto_dia: Registros principais da jornada de trabalho.
 * 2. usuario: Identificação do colaborador.
 * 3. pausa: Registros de intervalos realizados.
 * 4. pausa_config: Descrição dos tipos de pausa.
 * 5. ajustes_ponto: Controle de solicitações de ajuste.
 *
 * OBSERVAÇÕES TÉCNICAS
 * -------------------------------------------------------------
 * - JOIN por chave composta (id_usuario + data) garante associação
 *   correta entre ponto e pausas.
 * - Agrupamento backend evita duplicidade de linhas na interface.
 * - Função 'validar_ajustes_pendentes' controla regras de negócio:
 *   • Impede múltiplas solicitações simultâneas;
 *   • Bloqueia ajustes em pontos "Em Andamento";
 *   • Permite apenas estados válidos para nova solicitação.
 * - Função 'mostrar' padroniza formatação de data/hora e tratamento
 *   de valores nulos.
 * - Interface exibe "-" quando não há dados, mantendo consistência visual.
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
$nivel      = $_SESSION['nivel'];

function validar_ajustes_pendentes($conn, $id_usuario, $id_ponto)
{
    $validacao = $conn->prepare("
        SELECT status 
        FROM ajustes_ponto
        WHERE id_usuario = ?
        AND id_ponto = ?
    ");

    $validacao->bind_param("ii", $id_usuario, $id_ponto);
    $validacao->execute();
    $result = $validacao->get_result();

    if ($validacao->num_rows === 0) {
        $validacao_ponto_aberto = $conn->prepare("
            SELECT status FROM ponto_dia WHERE id_ponto = ?
        ");

        $validacao_ponto_aberto->bind_param("i", $id_ponto);
        $validacao_ponto_aberto->execute();
        $result_validacao = $validacao_ponto_aberto->get_result();

        if ($result_validacao->fetch_assoc()['status'] === "Em Andamento") {
            return false;
        }

        return true;
    } else {
        $status = $result->fetch_assoc()['status'];
        
        if ($status != "Revisar" && $status != "Em Andamento") {
            return true;
        }
    }

    return false;
}

$f_from   = $_GET['from']   ?? '';
$f_to     = $_GET['to']     ?? '';
$f_status = $_GET['status'] ?? '';
$f_nome   = $_GET['nome']   ?? '';

$where  = [];
$params = [];
$types  = '';

$sql = "
    SELECT 
        p.*, 
        u.nome_usuario AS nome,
        ps.inicio AS inicio_pausa,       -- Novo
        ps.fim AS fim_pausa,             -- Novo
        pc.descricao_pausa               -- Novo
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
    -- O JOIN CRÍTICO: LIGAÇÃO POR CHAVE COMPOSTA (ID do Usuário e Data)
    LEFT JOIN pausa ps ON ps.id_usuario = p.id_usuario AND ps.data = p.data_ponto
    LEFT JOIN pausa_config pc ON pc.id_config = ps.id_config
";

/* CONTROLE DE PERMISSÕES */
if ($nivel > 0) {
    $where[]  = "p.id_usuario = ?";
    $params[] = $id_usuario;
    $types   .= 'i';
}

/* FILTROS */
if (!empty($f_from)) {
    $where[]  = "p.data_ponto >= ?";
    $params[] = $f_from;
    $types   .= 's';
}

if (!empty($f_to)) {
    $where[]  = "p.data_ponto <= ?";
    $params[] = $f_to;
    $types   .= 's';
}

if (!empty($f_status)) {
    $where[]  = "p.status = ?";
    $params[] = $f_status;
    $types   .= 's';
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.data_ponto DESC";
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$batidas = $stmt->get_result();
$pontos_agrupados = [];

while ($row = $batidas->fetch_assoc()) {
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

function mostrar($valor, $tipo = null)
{
    if (empty($valor)) {
        return "-";
    }

    if ($tipo === "data") {
        return date("d/m/Y", strtotime($valor));
    }

    if ($tipo === "hora") {
        return date("H:i", strtotime($valor));
    }

    return htmlspecialchars($valor);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Histórico de Batidas</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Histórico de Pontos</h1>

            <section class="container filtro-padrao">
                <form method="get" class="form-linha">
                    <article>
                        <label class="label">De:</label>
                        <input class="input" type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">
                    </article>

                    <article>
                        <label class="label">Até:</label>
                        <input class="input" type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">
                    </article>

                    <article>
                        <label class="label">Status:</label>
                        <select class="select-padrao" name="status">
                            <option value="">Todos</option>
                            <option value="Em Andamento" <?= $f_status == 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="Finalizado" <?= $f_status == 'Finalizado'   ? 'selected' : '' ?>>Finalizado</option>
                            <option value="Revisar" <?= $f_status == 'Revisar'      ? 'selected' : '' ?>>Revisar</option>
                            <option value="Aprovado" <?= $f_status == 'Aprovado'     ? 'selected' : '' ?>>Aprovado</option>
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
                                            echo htmlspecialchars(ucfirst($pausa['descricao_pausa'])) . ": $pausa_inicio - $pausa_fim <br>";
                                        }
                                    }
                                    ?>
                                </td>

                                <td><?= mostrar($r['status']) ?></td>
                                <?php $pode_solicitar = validar_ajustes_pendentes($conn, $id_usuario, $r['id_ponto']); ?>

                                <td>
                                    <?php if ($pode_solicitar): ?>
                                        <a class="btn-link btn-padrao" href="solicitar.php?id_ponto=<?= $r['id_ponto'] ?>">
                                            Solicitar ajuste
                                        </a>
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