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
        u.nome_usuario AS nome
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
";

/* ===================
CONTROLE DE PERMISSÕES
=================== */
// Funcionário comum vê apenas seus dados
if ($nivel < 2) {
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

$sql = "
    SELECT 
        ps.*,
        pc.descricao_pausa,
        pc.id_config,
        u.nome_usuario
    FROM pausa ps
    LEFT JOIN pausa_config pc ON pc.id_config = ps.id_config
    LEFT JOIN usuario u ON u.id_usuario = ps.id_usuario
    WHERE ps.id_usuario = ?
    ORDER BY ps.inicio ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $users = [];
    while($rowP = $res -> fetch_assoc()) $users[] = $rowP;
}

// pegar todas as pausas
$sql = "
    SELECT 
        ps.*,
        pc.descricao_pausa,
        pc.id_config,
        u.nome_usuario
    FROM pausa ps
    LEFT JOIN pausa_config pc ON pc.id_config = ps.id_config
    LEFT JOIN usuario u ON u.id_usuario = ps.id_usuario
    WHERE ps.id_usuario = ?
    ORDER BY ps.inicio ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $users = [];
    while($rowP = $res -> fetch_assoc()) $users[] = $rowP;
}

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

        <!-- Filtro por nome (RH) -->
        <label>Nome:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($f_nome) ?>">

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
            <?php if ($nivel < 2): ?>
                <th>Ação</th>
            <?php endif; ?>
        </tr>

        <?php while ($r = $batidas->fetch_assoc()): ?>
            <tr>

                <!-- Data -->
                <td><?= date("d/m/Y", strtotime($r['data_ponto'])) ?></td>

                <!-- Funcionário -->
                <td><?= htmlspecialchars($r['nome']) ?></td>

                <!-- Horários (com fallback visual) -->
                <td><?= $r['inicio_ponto']   ? date("H:i", strtotime($r['inicio_ponto']))   : '--:--' ?></td>
                <td><?= $r['fim_ponto']      ? date("H:i", strtotime($r['fim_ponto']))      : '--:--' ?></td>

                <!-- Pausas -->
                <td>
                    <?php foreach($users as $user):
                    if ($r['data_ponto'] == $user['data']){
                        $pausa_inicio = ($user['inicio'] ? date("H:i", strtotime($user['inicio'] )) : '--');
                        $pausa_fim = ($user['fim'] ? date("H:i", strtotime($user['fim'] )) : '--');
                        echo $user['descricao_pausa'] . ": " . $pausa_inicio . ":" . $pausa_fim . "<br>";}
                    ?>
                    <?php endforeach?>
                </td>

                <!-- Status -->
                <td><?= $r['status'] ?></td>

                <!-- Botão de ajuste apenas para funcionário -->
                <?php if ($nivel < 2): ?>
                    <td>
                        <a href="solicitar.php?id_ponto=<?= $r['id_ponto'] ?>">
                            Solicitar ajuste
                        </a>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>