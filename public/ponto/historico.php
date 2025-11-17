<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

$f_from = $_GET['from'] ?? '';
$f_to = $_GET['to'] ?? '';
$f_status = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

$sql = "
    SELECT 
        p.*, 
        u.nome_usuario AS nome
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
";

// Se usuário comum, só mostra o histórico dele
if ($nivel < 3) {
    $where[] = "p.id_usuario = ?";
    $params[] = $id_usuario;
    $types .= 'i';
}

if ($f_from) {
    $where[] = "p.data_reg >= ?";
    $params[] = $f_from;
    $types .= 's';
}

if ($f_to) {
    $where[] = "p.data_reg <= ?";
    $params[] = $f_to;
    $types .= 's';
}

if ($f_status) {
    $where[] = "p.status = ?";
    $params[] = $f_status;
    $types .= 's';
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.data_reg DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$batidas = $stmt->get_result();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Histórico de Batidas</title>
</head>

<body>
    <a href="../index.php">Voltar</a>
    <h1>Histórico de Batidas</h1>

    <form method="get">
        <label>De:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">

        <label>Até:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">

        <label>Status:</label>
        <select name="status">
            <option value="">Todos</option>
            <option value="Em Andamento" <?= $f_status == 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
            <option value="Finalizado" <?= $f_status == 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
            <option value="Revisar" <?= $f_status == 'Revisar' ? 'selected' : '' ?>>Revisar</option>
            <option value="Aprovado" <?= $f_status == 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
        </select>

        <button>Filtrar</button>
    </form>

    <br>

    <table border="1" cellpadding="8">
        <tr>
            <th>Data</th>
            <th>Funcionário</th>
            <th>Entrada</th>
            <th>Início Almoço</th>
            <th>Fim Almoço</th>
            <th>Saída</th>
            <th>Status</th>
        </tr>

        <?php while ($r = $batidas->fetch_assoc()): ?>
            <tr>
                <td><?= date("d-m-Y", strtotime($r['data_reg'])) ?></td>
                <td><?= htmlspecialchars($r['nome']) ?></td>
                <td><?= $r['inicio_ponto'] ? date("H:i", strtotime($r['inicio_ponto'])) : '-' ?></td>
                <td><?= $r['inicio_almoco'] ? date("H:i", strtotime($r['inicio_almoco'])) : '-' ?></td>
                <td><?= $r['fim_almoco'] ? date("H:i", strtotime($r['fim_almoco'])) : '-' ?></td>
                <td><?= $r['fim_ponto'] ? date("H:i", strtotime($r['fim_ponto'])) : '-' ?></td>
                <td><?= $r['status'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

</body>

</html>