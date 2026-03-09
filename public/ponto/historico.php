<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// Recupera dados da sessão
$id_usuario = $_SESSION['id_usuario'];
$nivel      = $_SESSION['nivel'];

/* ======================
FILTROS RECEBIDOS VIA GET
====================== */
$f_from   = $_GET['from']   ?? '';
$f_to     = $_GET['to']     ?? '';
$f_status = $_GET['status'] ?? '';
$f_nome   = $_GET['nome']   ?? '';

// Arrays para montagem dinâmica da query
$where  = [];
$params = [];
$types  = '';

/* =======
QUERY BASE
======= */
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

/* ===================
CONTROLE DE PERMISSÕES
=================== */
// Funcionário comum vê apenas seus dados
if ($nivel > 0) {
    $where[]  = "p.id_usuario = ?";
    $params[] = $id_usuario;
    $types   .= 'i';
}

/* ===========
APLICA FILTROS
=========== */

// Filtro por data inicial
if (!empty($f_from)) {
    $where[]  = "p.data_ponto >= ?";
    $params[] = $f_from;
    $types   .= 's';
}

// Filtro por data final
if (!empty($f_to)) {
    $where[]  = "p.data_ponto <= ?";
    $params[] = $f_to;
    $types   .= 's';
}

// Filtro por status
if (!empty($f_status)) {
    $where[]  = "p.status = ?";
    $params[] = $f_status;
    $types   .= 's';
}

// Filtro por nome do funcionário (RH)
if (!empty($f_nome)) {
    $where[]  = "u.nome_usuario LIKE ?";
    $params[] = "%$f_nome%";
    $types   .= 's';
}

/* =================
MONTA WHERE DINÂMICO
================= */
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

/* ==============
ORDENA RESULTADOS
============== */
$sql .= " ORDER BY p.data_ponto DESC";

/* ==================
PREPARA E EXECUTA SQL
================== */
$stmt = $conn->prepare($sql);

// Aplica parâmetros apenas se houver filtros
if ($params) {
    $stmt->bind_param($types, ...$params);
}

// Executa a query
$stmt->execute();

// Recupera resultados
$batidas = $stmt->get_result();

// NOVO PROCESSAMENTO: Agrupar resultados por ID do Ponto
$pontos_agrupados = [];

while ($row = $batidas->fetch_assoc()) {
    $id_ponto = $row['id_ponto'];

    // Se é a primeira vez que vemos este ponto, inicialize o registro principal
    if (!isset($pontos_agrupados[$id_ponto])) {
        // Armazena todos os dados do dia, menos os específicos de pausa
        $pontos_agrupados[$id_ponto] = [
            'data_ponto'   => $row['data_ponto'],
            'nome'         => $row['nome'],
            'inicio_ponto' => $row['inicio_ponto'],
            'fim_ponto'    => $row['fim_ponto'],
            'status'       => $row['status'],
            'id_ponto'     => $row['id_ponto'],
            'pausas'       => [], // Array para armazenar todas as pausas
        ];
    }

    // Se houver dados de pausa na linha (devido ao JOIN)
    if (!empty($row['inicio_pausa'])) {
        $pontos_agrupados[$id_ponto]['pausas'][] = [
            'descricao_pausa' => $row['descricao_pausa'],
            'inicio'          => $row['inicio_pausa'],
            'fim'             => $row['fim_pausa'],
        ];
    }
}
// Agora, $pontos_agrupados é o array que você irá iterar no HTML.
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Histórico de Batidas</title>
</head>

<body>

    <!-- Navegação -->
    <a href="../index.php">Voltar</a>
    <h1>Histórico de Batidas</h1>

    <!-- FORMULÁRIO DE FILTRO -->
    <form method="get">

        <!-- Filtro por data inicial -->
        <label>De:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">

        <!-- Filtro por data final -->
        <label>Até:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">

        <!-- Filtro por status -->
        <label>Status:</label>
        <select name="status">
            <option value="">Todos</option>
            <option value="Em Andamento" <?= $f_status == 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
            <option value="Finalizado" <?= $f_status == 'Finalizado'   ? 'selected' : '' ?>>Finalizado</option>
            <option value="Revisar" <?= $f_status == 'Revisar'      ? 'selected' : '' ?>>Revisar</option>
            <option value="Aprovado" <?= $f_status == 'Aprovado'     ? 'selected' : '' ?>>Aprovado</option>
        </select>

        <button type="submit">Filtrar</button>
    </form>
    <br>

    <!-- TABELA DE RESULTADOS -->
    <table border="1" cellpadding="8">
        <tr>
            <th>Data</th>
            <th>Funcionário</th>
            <th>Entrada</th>
            <th>Saída</th>
            <th>Pausas</th>
            <th>Status</th>

            <!-- Só funcionário comum vê coluna de ação -->
            <th>Ação</th>
        </tr>

        <?php foreach ($pontos_agrupados as $r): ?>
            <tr>
                <!-- Data formatada -->
                <td><?= date("d/m/Y", strtotime($r['data_ponto'])) ?></td>

                <!-- Nome do funcionário -->
                <td><?= htmlspecialchars($r['nome']) ?></td>

                <!-- Horários -->
                <td><?= $r['inicio_ponto']   ? date("H:i", strtotime($r['inicio_ponto']))   : '--:--' ?></td>
                <td><?= $r['fim_ponto']      ? date("H:i", strtotime($r['fim_ponto']))      : '--:--' ?></td>

                <!-- Pausas -->
                <td>
                    <?php foreach($r['pausas'] as $pausa): ?>
                        <?php 
                            $pausa_inicio = ($pausa['inicio'] ? date("H:i", strtotime($pausa['inicio'] )) : '--');
                            $pausa_fim    = ($pausa['fim']    ? date("H:i", strtotime($pausa['fim']    )) : '--');
                            echo htmlspecialchars($pausa['descricao_pausa']) . ": " . $pausa_inicio . ":" . $pausa_fim . "<br>";
                        ?>
                    <?php endforeach; ?>
                </td>

                <!-- Status -->
                <td><?= $r['status'] ?></td>

                <!-- AÇÕES -->
                <td>
                    <a href="solicitar.php?id_ponto=<?= $r['id_ponto'] ?>">
                        Solicitar ajuste
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>