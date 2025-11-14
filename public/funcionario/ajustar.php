<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

$id_usuario = $_SESSION['id_usuario'];

// busca registros do funcionário
$sql = "SELECT * FROM ponto_dia WHERE id_usuario = ? ORDER BY data_reg DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Solicitar Ajuste de Ponto</title>
</head>
<body>
<h1>Solicitar Ajuste de Ponto</h1>

<table border="1" cellpadding="8">
<tr>
    <th>Data</th>
    <th>Entrada</th>
    <th>Início Almoço</th>
    <th>Fim Almoço</th>
    <th>Saída</th>
    <th>Ajustar</th>
</tr>

<?php while ($row = $res->fetch_assoc()): ?>
<tr>
    <td><?= $row['data_reg'] ?></td>
    <td><?= $row['inicio_ponto'] ?></td>
    <td><?= $row['inicio_almoco'] ?></td>
    <td><?= $row['fim_almoco'] ?></td>
    <td><?= $row['fim_ponto'] ?></td>
    <td><a href="solicitar.php?id=<?= $row['id_ponto'] ?>">Solicitar ajuste</a></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
