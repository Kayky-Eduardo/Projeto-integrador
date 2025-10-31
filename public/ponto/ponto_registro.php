<?php
include_once("../../BD/conexao.php");

session_start();
$id_usuario = $_SESSION['id_usuario'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $data = date('Y-m-d');
    $hora = date('H:i:s');

    $sql = "SELECT * FROM ponto WHERE id_usuario=? AND data_ponto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_usuario, $data);
    $stmt->execute();
    $ponto = $stmt->get_result()->fetch_assoc();

    if (!$ponto) {
        $conn->query("INSERT INTO ponto (id_usuario, data_ponto, $tipo) VALUES ($id_usuario, '$data', '$hora')");
    } else {
      $colunas_validas = ['hora_entrada', 'hora_almoco_saida', 'hora_almoco_retorno', 'hora_saida'];

      if (in_array($tipo, $colunas_validas)) {
          if (!$ponto) {
              $stmt = $conn->prepare("INSERT INTO ponto (id_usuario, data_ponto, $tipo) VALUES (?, ?, ?)");
              $stmt->bind_param("iss", $id_usuario, $data, $hora);
          } else {
              $stmt = $conn->prepare("UPDATE ponto SET $tipo=? WHERE id_ponto=?");
              $stmt->bind_param("si", $hora, $ponto['id_ponto']);
          }
          $stmt->execute();
      }
    }

    echo "<p>Ponto registrado com sucesso!</p>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head><meta charset="UTF-8"><title>Registrar Ponto</title></head>
<body>
  <h1>Registrar Ponto</h1>
  <form method="post">
    <button type="submit" name="tipo" value="hora_entrada">Entrada</button>
    <button type="submit" name="tipo" value="hora_almoco_saida">Saída Almoço</button>
    <button type="submit" name="tipo" value="hora_almoco_retorno">Retorno Almoço</button>
    <button type="submit" name="tipo" value="hora_saida">Saída Final</button>
  </form>
</body>
</html>
