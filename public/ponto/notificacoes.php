<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

$id_usuario = $_SESSION["id_usuario"];
$nivel = $_SESSION["nivel"];

/*
    NOTIFICAÇÕES REFERENTES A AJUSTES DE PONTO:
    ------------------------------------------------------
    Funcionário vê:
        - Pontos marcados como "Revisar" (RH pediu ajuste)
        - Ajustes enviados para análise (status "Em Analise")

    RH/Admin vê:
        - Ajustes pendentes enviados pelo funcionário
*/

if ($nivel >= 3) {
    // RH vê todas as solicitações de ajuste
    $sql = "
        SELECT a.*, u.nome_usuario, p.data_reg
        FROM ajustes_ponto a
        INNER JOIN usuario u ON u.id_usuario = a.id_usuario
        INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
        WHERE a.status = 'Pendente'
        ORDER BY a.data_solicitacao DESC
    ";
} else {
    // Funcionário vê pontos que precisam ser revisados
    $sql = "
        SELECT 
            p.id_ponto,
            p.data_reg,
            p.status
        FROM ponto_dia p
        WHERE p.id_usuario = $id_usuario
          AND (p.status = 'Revisar' OR p.status = 'Em Analise')
        ORDER BY p.data_reg DESC
    ";
}

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Notificações</title>
</head>
<body>

<h1>Notificações de Ponto</h1>
<a href="../index.php">Voltar</a>
<br><br>

<table border="1" cellpadding="8">

<?php if ($nivel >= 3): ?>

    <!-- ============================ -->
    <!-- NOTIFICAÇÕES PARA RH/ADMIN -->
    <!-- ============================ -->
    <tr>
        <th>Funcionário</th>
        <th>Data</th>
        <th>Campo</th>
        <th>Valor Antigo</th>
        <th>Valor Novo</th>
        <th>Motivo</th>
        <th>Solicitado em</th>
        <th>Ação</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['nome_usuario'] ?></td>
            <td><?= $row['data_reg'] ?></td>
            <td><?= $row['campo'] ?></td>
            <td><?= $row['valor_antigo'] ?: '-' ?></td>
            <td><?= $row['valor_novo'] ?></td>
            <td><?= $row['motivo'] ?></td>
            <td><?= $row['data_solicitacao'] ?></td>
            <td>
                <a href="gerenciar.php?aprovar=<?= $row['id_ponto'] ?>">Aprovar</a> |
                <a href="gerenciar.php?ajuste=<?= $row['id_ponto'] ?>">Solicitar novo ajuste</a>
            </td>
        </tr>
    <?php endwhile; ?>

<?php else: ?>

    <!-- ============================ -->
    <!-- NOTIFICAÇÕES PARA FUNCIONÁRIO -->
    <!-- ============================ -->
    <tr>
        <th>Data</th>
        <th>Status</th>
        <th>Ação</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['data_reg'] ?></td>
            <td><?= $row['status'] ?></td>
            <td>
                <?php if ($row['status'] === "Revisar"): ?>
                    <a href="editar_ponto.php?id=<?= $row['id_ponto'] ?>">Editar ponto</a>
                <?php elseif ($row['status'] === "Em Analise"): ?>
                    Ajuste enviado — aguardando RH
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>

<?php endif; ?>

</table>

</body>
</html>
