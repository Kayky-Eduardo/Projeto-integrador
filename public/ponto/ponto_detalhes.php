<?php
include("../../BD/conexao.php");
include("ponto_funcoes.php");

$id = $_GET['id'] ?? 0;
$ponto = buscarPonto($conn, $id);

// Supondo que o RH logado tem id salvo na sessão:
session_start();
$id_rh = $_SESSION['id_usuario'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'];
    if (in_array($acao, ['aprovado', 'rejeitado'])) {
        atualizarStatusPonto($conn, $id, $acao, $id_rh);
        header("Location: ponto_admin.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Detalhes do Ponto</title>
</head>
<body>
  <h1>Detalhes do Ponto</h1>
  <p><b>Funcionário:</b> <?= htmlspecialchars($ponto['nome_usuario']) ?></p>
  <p><b>Data:</b> <?= $ponto['data_ponto'] ?></p>
  <p><b>Entrada:</b> <?= $ponto['hora_entrada'] ?></p>
  <p><b>Saída Almoço:</b> <?= $ponto['hora_almoco_saida'] ?></p>
  <p><b>Retorno Almoço:</b> <?= $ponto['hora_almoco_retorno'] ?></p>
  <p><b>Saída:</b> <?= $ponto['hora_saida'] ?></p>
  <p><b>Observação:</b> <?= nl2br($ponto['observacao']) ?></p>
  <p><b>Status:</b> <?= ucfirst($ponto['status']) ?></p>

  <form method="post">
    <button type="submit" name="acao" value="aprovado">Aprovar</button>
    <button type="submit" name="acao" value="rejeitado">Rejeitar</button>
  </form>

  <br><a href="ponto_admin.php">Voltar</a>
</body>
</html>
