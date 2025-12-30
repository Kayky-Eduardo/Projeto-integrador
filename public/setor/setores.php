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

  <table>
    <tr>
      <th>ID setor</th>
      <th>Nome setor</th>
      <th>Edição</th>
      <th>Deletar</th>
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

            echo '
            <td>
              <form action="deletar_setor.php" method="POST" onsubmit="return confirm(\'Tem certeza que deseja deletar?\');">
                <input type="hidden" name="id_setor" value="'. $row['id_setor'] . '">
                <button type="submit">Deletar</button>
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