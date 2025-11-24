<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// Somente RH / Admin
if ($_SESSION['nivel'] < 2) die("Acesso restrito.");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID inválido.");
}

$id = intval($_GET['id']);

// Buscar dados do ajuste
$sql = "
SELECT 
    a.*, 
    p.inicio_ponto, p.inicio_almoco, p.fim_almoco, p.fim_ponto, p.data_reg, 
    u.nome_usuario 
FROM ajustes_ponto a
INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
INNER JOIN usuario u ON u.id_usuario = a.id_usuario
WHERE a.id_ajuste = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$ajuste = $stmt->get_result()->fetch_assoc();

if (!$ajuste) {
    die("Ajuste não encontrado.");
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Editar Ajuste</title>
</head>

<body>

    <h1>Editar Ajuste do Funcionário</h1>
    <a href="editar_ponto.php">Voltar</a>
    <br><br>

    <form method="post" action="salvar.php">

        <input type="hidden" name="id_ajuste" value="<?= $ajuste['id_ajuste'] ?>">
        <input type="hidden" name="id_ponto" value="<?= $ajuste['id_ponto'] ?>">

        <p><strong>Funcionário:</strong> <?= htmlspecialchars($ajuste['nome_usuario']) ?></p>
        <p><strong>Data:</strong> <?= htmlspecialchars($ajuste['data_reg']) ?></p>

        <label>Entrada:</label>
        <input type="time" name="inicio_ponto" value="<?= $ajuste['inicio_ponto'] ?>"><br><br>

        <label>Início Almoço:</label>
        <input type="time" name="inicio_almoco" value="<?= $ajuste['inicio_almoco'] ?>"><br><br>

        <label>Fim Almoço:</label>
        <input type="time" name="fim_almoco" value="<?= $ajuste['fim_almoco'] ?>"><br><br>

        <label>Saída:</label>
        <input type="time" name="fim_ponto" value="<?= $ajuste['fim_ponto'] ?>"><br><br>

        <label>Motivo do Ajuste:</label><br>
        <textarea name="motivo" rows="4" cols="50"><?= htmlspecialchars($ajuste['motivo']) ?></textarea><br><br>

        <button type="submit">Salvar Alterações</button>
    </form>

</body>

</html>