<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// Consulta todos os usuários

$result = $conn->query("SELECT * FROM setor");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Setores</title>
  <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>
    <header>
        <?php include("../../include/navbar.php");?>
    </header>

  <button type="submit"><a href="cadastro_setor.php">Novo</a></button>
  <table>
    <tr>
      <th>ID setor</th>
      <th>Nome setor</th>
      <th>Edição</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>".$row["id_setor"]."</td>";
            echo "<td>".$row["nome_setor"]."</td>";
            echo '
            <td>
                <form action="editar_setor.php" method="GET">
                    <input type="hidden" name="id" value="'. $row['id_setor'] . '">
                    <button type="submit">Editar</button>
                </form>
            </td>';            
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='12'>Nenhum setor cadastrado.</td></tr>";
    }
    ?>
  </table>
</body>
</html>