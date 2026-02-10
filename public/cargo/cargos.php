<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// Consulta todos os usuários

$result = $conn->query("SELECT * FROM cargo");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>cargoes</title>
  <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>
<body>
    <header>
        <?php include("../../include/navbar.php");?>
    </header>

  <button type="submit"><a href="cadastrar_cargo.php">Novo</a></button>
  <table>
    <tr>
        <th>ID cargo</th>
        <th>Nome cargo</th>
        <th>Salário bruto</th>
        <th>Nível</th>
        <th>Ação</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>".$row["id_cargo"]."</td>";
            echo "<td>".$row["nome_cargo"]."</td>";
            echo "<td>".$row["salario_bruto"]."</td>";
            echo "<td>".$row["nivel"]."</td>";
            echo '
            <td>
                <form action="editar_cargo.php" method="GET">
                    <input type="hidden" name="id" value="'. $row['id_cargo'] . '">
                    <button type="submit">Editar</button>
                </form>
            </td>';            
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='12'>Nenhum cargo cadastrado.</td></tr>";
    }
    ?>
  </table>
</body>
</html>