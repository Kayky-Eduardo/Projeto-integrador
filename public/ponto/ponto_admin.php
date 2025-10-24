<?php
include("../../BD/conexao.php");
include("ponto_funcoes.php");

$status = $_GET['status'] ?? '';
$result = listarPontos($conn, $status);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Ponto - Administrativo</title>
  <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>
  <h1>Controle de Ponto (Administrativo)</h1>

  <form method="get">
    <label>Status:</label>
    <select name="status" onchange="this.form.submit()">
      <option value="">Todos</option>
      <option value="pendente" <?= $status == 'pendente' ? 'selected' : '' ?>>Pendentes</option>
      <option value="aprovado" <?= $status == 'aprovado' ? 'selected' : '' ?>>Aprovados</option>
      <option value="rejeitado" <?= $status == 'rejeitado' ? 'selected' : '' ?>>Rejeitados</option>
    </select>
  </form>

  <table border="1" cellpadding="8" cellspacing="0">
    <tr>
      <th>Funcionário</th>
      <th>Data</th>
      <th>Entrada</th>
      <th>Almoço</th>
      <th>Saída</th>
      <th>Status</th>
      <th>Ações</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['nome_usuario']) ?></td>
        <td><?= htmlspecialchars($row['data_ponto']) ?></td>
        <td><?= $row['hora_entrada'] ?: '-' ?></td>
        <td><?= ($row['hora_almoco_saida'] ?: '-') . " / " . ($row['hora_almoco_retorno'] ?: '-') ?></td>
        <td><?= $row['hora_saida'] ?: '-' ?></td>
        <td><?= ucfirst($row['status']) ?></td>
        <td>
          <a href="ponto_detalhes.php?id=<?= $row['id_ponto'] ?>">Ver</a> |
          <a href="ponto_editar.php?id=<?= $row['id_ponto'] ?>">Editar</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
