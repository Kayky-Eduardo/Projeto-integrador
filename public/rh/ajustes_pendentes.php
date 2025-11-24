<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// Somente RH / Admin (nível 3 ou mais)
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

$sql = "
SELECT a.*, u.nome_usuario, p.data_reg
FROM ajustes_ponto a
INNER JOIN usuario u ON u.id_usuario = a.id_usuario
INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
WHERE a.status = 'Pendente'
ORDER BY a.data_solicitacao DESC
";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Ajustes Pendentes (RH)</title>
</head>

<body>

    <h1>Ajustes Pendentes</h1>
    <a href="../index.php">Voltar</a>
    <br><br>

    <table border="1" cellpadding="8">
        <tr>
            <th>Funcionário</th>
            <th>Dia</th>
            <th>Campo</th>
            <th>Antes</th>
            <th>Depois</th>
            <th>Motivo</th>
            <th>Ação</th>
        </tr>

        <?php while ($r = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $r['nome_usuario'] ?></td>
                <td><?= $r['data_reg'] ?></td>
                <td><?= $r['campo'] ?></td>
                <td><?= $r['valor_antigo'] ?></td>
                <td><?= $r['valor_novo'] ?></td>
                <td><?= $r['motivo'] ?></td>
                <td>
                    <a href="editar.php?id=<?= $r['id_ajuste'] ?>">Editar</a> |
                    <a href="aprovar.php?id=<?= $r['id_ajuste'] ?>">Aprovar</a> |
                    <a href="recusar.php?id=<?= $r['id_ajuste'] ?>">Recusar</a>
                </td>
            </tr>
        <?php endwhile; ?>

    </table>

</body>

</html>