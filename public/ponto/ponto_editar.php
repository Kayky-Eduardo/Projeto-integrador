<?php
include("../../BD/conexao.php");
include("ponto_funcoes.php");

$id = $_GET['id'] ?? 0;
$ponto = buscarPonto($conn, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'id_ponto' => $id,
        'hora_entrada' => $_POST['hora_entrada'],
        'hora_almoco_saida' => $_POST['hora_almoco_saida'],
        'hora_almoco_retorno' => $_POST['hora_almoco_retorno'],
        'hora_saida' => $_POST['hora_saida'],
        'observacao' => $_POST['observacao']
    ];
    atualizarPonto($conn, $dados);
    header("Location: ponto_admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Ponto</title>
</head>
<body>
  <h1>Editar Registro de Ponto</h1>

  <form method="post">
    <label>Entrada:</label><br>
    <input type="time" name="hora_entrada" value="<?= $ponto['hora_entrada'] ?>"><br><br>

    <label>Saída Almoço:</label><br>
    <input type="time" name="hora_almoco_saida" value="<?= $ponto['hora_almoco_saida'] ?>"><br><br>

    <label>Retorno Almoço:</label><br>
    <input type="time" name="hora_almoco_retorno" value="<?= $ponto['hora_almoco_retorno'] ?>"><br><br>

    <label>Saída:</label><br>
    <input type="time" name="hora_saida" value="<?= $ponto['hora_saida'] ?>"><br><br>

    <label>Observação:</label><br>
    <textarea name="observacao"><?= htmlspecialchars($ponto['observacao']) ?></textarea><br><br>

    <button type="submit">Salvar Alterações</button>
  </form>

  <br><a href="ponto_admin.php">Voltar</a>
</body>
</html>
