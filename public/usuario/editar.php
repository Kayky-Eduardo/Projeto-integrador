<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// Verifica se foi passado o ID do usuário via GET
if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];

    // Busca o usuário correspondente
    $sql = "SELECT * FROM usuario WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se encontrou o usuário, guarda os dados
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    } else {
        die("<h3>Usuário não encontrado! <a href='lista.php'>Voltar</a></h3>");
    }
} else {
    die("<h3>Erro: nenhum usuário selecionado para edição.<br><a href='lista.php'>Voltar</a></h3>");
}

// Se enviou o formulário de edição
if (isset($_POST['editar_usuario'])) {
    $id = $_POST['id_usuario'];
    $nome = $_POST['nome_usuario'];
    $cpf = $_POST['cpf_usuario'];
    $rg = $_POST['rg_usuario'];
    $genero = $_POST['genero'];
    $email = $_POST['email_usuario'];
    $senha = $_POST['senha_usuario']; // pode estar vazio
    $telefone = $_POST['telefone'];
    $cep = $_POST['cep'];
    $id_cargo = $_POST['id_cargo'];
    $assiduidade = $_POST['assiduidade'];
    $data_admissao = $_POST['data_admissao'];

    // Verifica se a senha foi alterada
    if (!empty($senha)) {
        // Atualiza senha com hash
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE usuario SET 
            nome_usuario=?, cpf_usuario=?, rg_usuario=?, genero=?, 
            email_usuario=?, senha_usuario=?, telefone=?, cep=?, 
            id_cargo=?, assiduidade=?, data_admissao=?
            WHERE id_usuario=?";

        $stmt = $conn->prepare($sqlUpdate);
        $stmt->bind_param(
            "ssssssssidsi",
            $nome, $cpf, $rg, $genero,
            $email, $senha_hash, $telefone, $cep,
            $id_cargo, $assiduidade, $data_admissao, $id
        );
    } else {
        // Não altera a senha
        $sqlUpdate = "UPDATE usuario SET 
            nome_usuario=?, cpf_usuario=?, rg_usuario=?, genero=?, 
            email_usuario=?, telefone=?, cep=?, 
            id_cargo=?, assiduidade=?, data_admissao=?
            WHERE id_usuario=?";

        $stmt = $conn->prepare($sqlUpdate);
        $stmt->bind_param(
            "sssssssidsi",
            $nome, $cpf, $rg, $genero,
            $email, $telefone, $cep,
            $id_cargo, $assiduidade, $data_admissao, $id
        );
    }

    if ($stmt->execute()) {
        echo "Usuário atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar usuário: " . $stmt->error;
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Usuário</title>
  <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>
  <h1>Editar Usuário</h1>
  <a href="../index.php">Home</a> | 
  <a href="cadastro.php">Cadastro</a>
  <br><br>

  <form action="" method="POST">
    <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($usuario['id_usuario']) ?>">

    <label>Nome:</label><br>
    <input type="text" name="nome_usuario" value="<?= htmlspecialchars($usuario['nome_usuario']) ?>" required><br><br>

    <label>CPF:</label><br>
    <input type="text" name="cpf_usuario" maxlength="11" value="<?= htmlspecialchars($usuario['cpf_usuario']) ?>" required><br><br>

    <label>RG:</label><br>
    <input type="text" name="rg_usuario" maxlength="11" value="<?= htmlspecialchars($usuario['rg_usuario']) ?>" required><br><br>

    <label>Gênero:</label><br>
    <select name="genero" required>
      <option value="Masculino" <?= $usuario['genero'] == 'Masculino' ? 'selected' : '' ?>>Masculino</option>
      <option value="Feminino" <?= $usuario['genero'] == 'Feminino' ? 'selected' : '' ?>>Feminino</option>
      <option value="Outro" <?= $usuario['genero'] == 'Outro' ? 'selected' : '' ?>>Outro</option>
      <option value="Não Declarado" <?= $usuario['genero'] == 'Não Declarado' ? 'selected' : '' ?>>Não Declarado</option>
    </select><br><br>

    <label>Email:</label><br>
    <input type="email" name="email_usuario" value="<?= htmlspecialchars($usuario['email_usuario']) ?>" required><br><br>

    <label>Senha (deixe em branco para não alterar):</label><br>
    <input type="password" name="senha_usuario" placeholder="Nova senha (opcional)"><br><br>

    <label>Telefone:</label><br>
    <input type="text" name="telefone" value="<?= htmlspecialchars($usuario['telefone']) ?>"><br><br>

    <label>CEP:</label><br>
    <input type="text" name="cep" maxlength="8" value="<?= htmlspecialchars($usuario['cep']) ?>" required><br><br>

    <label>Cargo (ID):</label><br>
    <input type="number" name="id_cargo" value="<?= htmlspecialchars($usuario['id_cargo']) ?>" required><br><br>

    <label>Assiduidade (%):</label><br>
    <input type="number" name="assiduidade" step="0.01" value="<?= htmlspecialchars($usuario['assiduidade']) ?>" required><br><br>

    <label>Data de Admissão:</label><br>
    <input type="date" name="data_admissao" value="<?= htmlspecialchars($usuario['data_admissao']) ?>" required><br><br>

    <button type="submit" name="editar_usuario">Salvar</button>
    <button><a href="lista.php">Voltar</a></button>
  </form>
</body>
</html>
