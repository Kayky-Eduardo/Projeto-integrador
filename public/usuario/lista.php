<?php
include(__DIR__ . "/../../BD/conexao.php");

// Consulta todos os usuários
$sql = "SELECT u.id_usuario, u.nome_usuario, u.cpf_usuario, u.rg_usuario, u.genero,
               u.email_usuario, u.telefone, u.cep, c.nome_cargo, u.assiduidade,
               u.data_admissao, u.conta_ativa
        FROM usuario u
        LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
        ORDER BY u.id_usuario ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Lista de Usuários</title>
</head>
<body>
  <h1>Usuários Cadastrados</h1>
  <a href="../index.php">inicio</a>
  <a href="cadastro.php">Cadastrar novo usuário</a>
  <br><br>

  <table>
    <tr>
      <th>ID</th>
      <th>Nome</th>
      <th>CPF</th>
      <th>RG</th>
      <th>Gênero</th>
      <th>Email</th>
      <th>Telefone</th>
      <th>CEP</th>
      <th>Cargo</th>
      <th>Assiduidade</th>
      <th>Admissão</th>
      <th>Status</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>".$row["id_usuario"]."</td>";
            echo "<td>".$row["nome_usuario"]."</td>";
            echo "<td>".$row["cpf_usuario"]."</td>";
            echo "<td>".$row["rg_usuario"]."</td>";
            echo "<td>".$row["genero"]."</td>";
            echo "<td>".$row["email_usuario"]."</td>";
            echo "<td>".$row["telefone"]."</td>";
            echo "<td>".$row["cep"]."</td>";
            echo "<td>".($row["nome_cargo"] ?? "Não definido")."</td>";
            echo "<td>".$row["assiduidade"]."%</td>";
            echo "<td>".$row["data_admissao"]."</td>";
            echo "<td>".($row["conta_ativa"] ? "<span class='ativo'>Ativo</span>" : "<span class='inativo'>Inativo</span>")."</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='12'>Nenhum usuário cadastrado.</td></tr>";
    }
    ?>
  </table>
</body>
</html>