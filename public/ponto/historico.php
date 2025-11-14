<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

// --------------------------
// HISTÓRICO DE BATIDAS (ponto_dia)
// --------------------------
if ($nivel >= 3) {
    // RH e Admin visualizam todos
    $sql_ponto = "
        SELECT p.*, u.nome_usuario
        FROM ponto_dia p
        INNER JOIN usuario u ON u.id_usuario = p.id_usuario
        ORDER BY p.data_reg DESC
    ";
} else {
    // Funcionário vê só o dele
    $sql_ponto = "
        SELECT * FROM ponto_dia
        WHERE id_usuario = $id_usuario
        ORDER BY data_reg DESC
    ";
}

$batidas = $conn->query($sql_ponto);


// --------------------------
// HISTÓRICO DE SOLICITAÇÕES DE AJUSTE (ajustes_ponto)
// --------------------------
if ($nivel >= 3) {
    $sql_ajustes = "
        SELECT a.*, u.nome_usuario
        FROM ajustes_ponto a
        INNER JOIN usuario u ON u.id_usuario = a.id_usuario
        ORDER BY a.data_solicitacao DESC
    ";
} else {
    $sql_ajustes = "
        SELECT *
        FROM ajustes_ponto
        WHERE id_usuario = $id_usuario
        ORDER BY data_solicitacao DESC
    ";
}

$ajustes = $conn->query($sql_ajustes);
?>

<h1>Histórico Geral</h1>

<a href="../index.php">Voltar</a>

<hr>

<!-- ====================== -->
<!-- HISTÓRICO DE BATIDAS   -->
<!-- ====================== -->
<h2>Histórico de Batidas</h2>

<table border="1" cellpadding="8">
    <tr>
        <?php if ($nivel >= 3) echo "<th>Usuário</th>"; ?>
        <th>Data</th>
        <th>Entrada</th>
        <th>Início Almoço</th>
        <th>Fim Almoço</th>
        <th>Saída</th>
        <th>Status</th>
    </tr>

    <?php while ($row = $batidas->fetch_assoc()): ?>
        <tr>
            <?php if ($nivel >= 3) echo "<td>{$row['nome_usuario']}</td>"; ?>
            <td><?= $row['data_reg'] ?></td>
            <td><?= $row['inicio_ponto'] ?? '-' ?></td>
            <td><?= $row['inicio_almoco'] ?? '-' ?></td>
            <td><?= $row['fim_almoco'] ?? '-' ?></td>
            <td><?= $row['fim_ponto'] ?? '-' ?></td>
            <td><?= $row['status'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<hr>

<!-- ============================= -->
<!-- HISTÓRICO DE SOLICITAÇÕES    -->
<!-- ============================= -->
<h2>Histórico de Solicitações de Ajuste</h2>

<table border="1" cellpadding="8">
    <tr>
        <?php if ($nivel >= 3) echo "<th>Usuário</th>"; ?>
        <th>Campo Ajustado</th>
        <th>Valor Antigo</th>
        <th>Valor Novo</th>
        <th>Motivo</th>
        <th>Status</th>
        <th>Solicitado em</th>
        <?php if ($nivel >= 3) echo "<th>Aprovado/Rejeitado por</th>"; ?>
    </tr>

    <?php while ($row = $ajustes->fetch_assoc()): ?>
        <tr>
            <?php if ($nivel >= 3) echo "<td>{$row['nome_usuario']}</td>"; ?>
            <td><?= $row['campo'] ?></td>
            <td><?= $row['valor_antigo'] ?: '-' ?></td>
            <td><?= $row['valor_novo'] ?></td>
            <td><?= $row['motivo'] ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= $row['data_solicitacao'] ?></td>

            <?php
            if ($nivel >= 3) {
                if ($row['id_rh']) {
                    // Busca nome do RH
                    $buscar = $conn->query("SELECT nome_usuario FROM usuario WHERE id_usuario = {$row['id_rh']}");
                    $rh = $buscar->fetch_assoc()['nome_usuario'] ?? '-';
                    echo "<td>$rh</td>";
                } else {
                    echo "<td>-</td>";
                }
            }
            ?>
        </tr>
    <?php endwhile; ?>
</table>
