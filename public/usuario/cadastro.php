<?php
include("../../BD/conexao.php");

function cadastrarUsuario($conn, $dados) {
    $sql = "INSERT INTO usuario (
                nome_usuario, cpf_usuario, rg_usuario, genero,
                email_usuario, senha_usuario, telefone, cep, id_cargo,
                assiduidade, data_admissao, conta_ativa
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro na preparação: " . $conn->error);
    }

    // Hash da senha
    $senhaHash = password_hash($dados['senha_usuario'], PASSWORD_DEFAULT);

    $stmt->bind_param(
        "ssssssssidsi",
        $dados['nome_usuario'],
        $dados['cpf_usuario'],
        $dados['rg_usuario'],
        $dados['genero'],
        $dados['email_usuario'],
        $senhaHash,
        $dados['telefone'],
        $dados['cep'],
        $dados['id_cargo'],
        $dados['assiduidade'],
        $dados['data_admissao'],
        $dados['conta_ativa']
    );

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Usuário cadastrado com sucesso! ID: " . $stmt->insert_id . "</p>";
    } else {
        echo "<p style='color:red;'>Erro ao cadastrar: " . $stmt->error . "</p>";
    }
}

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dados = [
        "nome_usuario"   => $_POST['nome_usuario'],
        "cpf_usuario"    => $_POST['cpf_usuario'],
        "rg_usuario"     => $_POST['rg_usuario'],
        "genero"         => $_POST['genero'],
        "email_usuario"  => $_POST['email_usuario'],
        "senha_usuario"  => $_POST['senha_usuario'],
        "telefone"       => $_POST['telefone'],
        "cep"            => $_POST['cep'],
        "id_cargo"       => (int)$_POST['id_cargo'],
        "assiduidade"    => (float)$_POST['assiduidade'],
        "data_admissao"  => $_POST['data_admissao'],
        "conta_ativa"    => 1
    ];
    cadastrarUsuario($conn, $dados);
    header("Location: lista.php");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro de Usuário</title>
  <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>
  <h1>Cadastro de Usuário</h1>
  <form action="" method="POST">
    <label>Nome:</label><br>
    <input type="text" name="nome_usuario" required><br><br>

    <label>CPF:</label><br>
    <input type="text" name="cpf_usuario" maxlength="11" required><br><br>

    <label>RG:</label><br>
    <input type="text" name="rg_usuario" maxlength="11" required><br><br>

    <label>Gênero:</label><br>
    <select name="genero" required>
      <option value="Masculino">Masculino</option>
      <option value="Feminino">Feminino</option>
      <option value="Outro">Outro</option>
      <option value="Não Declarado">Não Declarado</option>
    </select><br><br>

    <label>Email:</label><br>
    <input type="email" name="email_usuario" required><br><br>

    <label>Senha:</label><br>
    <input type="password" name="senha_usuario" required><br><br>

    <label>Telefone:</label><br>
    <input type="text" name="telefone"><br><br>

    <label>CEP:</label><br>
    <input type="text" name="cep" maxlength="8" required><br><br>

    <label>Cargo (ID):</label><br>
    <input type="number" name="id_cargo" required><br><br>

    <label>Assiduidade (%):</label><br>
    <input type="number" name="assiduidade" step="0.01" required><br><br>

    <label>Data de Admissão:</label><br>
    <input type="date" name="data_admissao" required><br><br>

    <button type="submit">Cadastrar</button>
  </form>
    <a href="lista.php">Voltar</a>

</body>
</html>