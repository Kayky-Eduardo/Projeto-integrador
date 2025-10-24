<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
// Buscar cargos existentes
$cargos = [];
$result = $conn->query("SELECT id_cargo, nome_cargo FROM cargo ORDER BY nome_cargo ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cargos[] = $row;
    }
}

// Validações
function validarDados($dados) {
    $erros = [];

    // Nome
    if (empty($dados['nome_usuario'])) {
        $erros[] = "Campo 'Nome' está vazio.";
    }

    // CPF
    if (empty($dados['cpf_usuario'])) {
        $erros[] = "Campo 'CPF' está vazio.";
    } elseif (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $dados['cpf_usuario'])) {
        $erros[] = "Campo 'CPF' está preenchido de forma incorreta (use o formato xxx.xxx.xxx-xx).";
    }

    // RG
    if (empty($dados['rg_usuario'])) {
        $erros[] = "Campo 'RG' está vazio.";
    } elseif (!preg_match('/^\d{2}\.\d{3}\.\d{3}-\d{1}$/', $dados['rg_usuario'])) {
        $erros[] = "Campo 'RG' inválido (use o formato xx.xxx.xxx-x).";
    }

    // Gênero
    if (empty($dados['genero'])) {
        $erros[] = "Campo 'Gênero' é obrigatório.";
    }

    // Email
    if (empty($dados['email_usuario'])) {
        $erros[] = "Campo 'Email' está vazio.";
    } elseif (!filter_var($dados['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Campo 'Email' está preenchido de forma incorreta.";
    }

    // Senha
    if (empty($dados['senha_usuario'])) {
        $erros[] = "Campo 'Senha' está vazio.";
    } elseif (strlen($dados['senha_usuario']) < 6) {
        $erros[] = "A senha deve ter no mínimo 6 caracteres.";
    }

    // Telefone
    if (empty($dados['telefone'])) {
        $erros[] = "Campo 'Telefone' está vazio.";
    } elseif (!preg_match('/^\(\d{2}\) \d{5}-\d{4}$/', $dados['telefone'])) {
        $erros[] = "Campo 'Telefone' inválido (use o formato (xx) xxxxx-xxxx).";
    }

    // CEP
    if (empty($dados['cep'])) {
        $erros[] = "Campo 'CEP' está vazio.";
    } elseif (!preg_match('/^\d{5}-\d{3}$/', $dados['cep'])) {
        $erros[] = "Campo 'CEP' inválido (use o formato xxxxx-xxx).";
    }

    // Cargo
    if (empty($dados['id_cargo']) || !is_numeric($dados['id_cargo'])) {
        $erros[] = "Campo 'Cargo' é obrigatório.";
    }

    // Assiduidade
    if (!isset($dados['assiduidade']) || $dados['assiduidade'] < 0 || $dados['assiduidade'] > 100) {
        $erros[] = "Campo 'Assiduidade' deve estar entre 0 e 100.";
    }

    // Data de admissão
    if (empty($dados['data_admissao'])) {
        $erros[] = "Campo 'Data de Admissão' é obrigatório.";
    }

    return $erros;
}

// Cadastrar usuário
function cadastrarUsuario($conn, $dados) {
    $sql = "INSERT INTO usuario (
                nome_usuario, cpf_usuario, rg_usuario, genero,
                email_usuario, senha_usuario, telefone, cep, id_cargo,
                assiduidade, data_admissao, conta_ativa
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Erro na preparação: " . $conn->error);

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

    return $stmt->execute();
}

// Processar o formulário
$erros = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dados = [
        "nome_usuario"   => $_POST['nome_usuario'] ?? '',
        "cpf_usuario"    => $_POST['cpf_usuario'] ?? '',
        "rg_usuario"     => $_POST['rg_usuario'] ?? '',
        "genero"         => $_POST['genero'] ?? '',
        "email_usuario"  => $_POST['email_usuario'] ?? '',
        "senha_usuario"  => $_POST['senha_usuario'] ?? '',
        "telefone"       => $_POST['telefone'] ?? '',
        "cep"            => $_POST['cep'] ?? '',
        "id_cargo"       => (int)($_POST['id_cargo'] ?? 0),
        "assiduidade"    => (float)($_POST['assiduidade'] ?? 0),
        "data_admissao"  => $_POST['data_admissao'] ?? '',
        "conta_ativa"    => 1
    ];

    $erros = validarDados($dados);

    // Verificar se o cargo existe no banco
    $checkCargo = $conn->prepare("SELECT id_cargo FROM cargo WHERE id_cargo = ?");
    $checkCargo->bind_param("i", $dados['id_cargo']);
    $checkCargo->execute();
    $checkCargo->store_result();
    if ($checkCargo->num_rows === 0) {
        $erros[] = "O cargo selecionado não existe no banco de dados.";
    }
    $checkCargo->close();

    if (empty($erros)) {
        if (cadastrarUsuario($conn, $dados)) {
            header("Location: lista.php");
            exit();
        } else {
            echo "<p style='color:red;'>Erro ao cadastrar o usuário.</p>";
        }
    }
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

  <?php
  if (!empty($erros)) {
      echo "<div style='color:red;'><ul>";
      foreach ($erros as $erro) {
          echo "<li>$erro</li>";
      }
      echo "</ul></div>";
  }
  ?>

  <!-- Formulário -->
  <form action="" method="POST">
    <label>Nome:</label><br>
    <input type="text" name="nome_usuario" value="<?= $_POST['nome_usuario'] ?? '' ?>" required><br><br>

    <label>CPF:</label><br>
    <input type="text" name="cpf_usuario" maxlength="14" value="<?= $_POST['cpf_usuario'] ?? '' ?>" required><br><br>

    <label>RG:</label><br>
    <input type="text" name="rg_usuario" maxlength="12" value="<?= $_POST['rg_usuario'] ?? '' ?>" required><br><br>

    <label>Gênero:</label><br>
    <select name="genero" required>
      <option value="">Selecione</option>
      <option value="Masculino" <?= (($_POST['genero'] ?? '') == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
      <option value="Feminino" <?= (($_POST['genero'] ?? '') == 'Feminino') ? 'selected' : '' ?>>Feminino</option>
      <option value="Outro" <?= (($_POST['genero'] ?? '') == 'Outro') ? 'selected' : '' ?>>Outro</option>
      <option value="Não Declarado" <?= (($_POST['genero'] ?? '') == 'Não Declarado') ? 'selected' : '' ?>>Não Declarado</option>
    </select><br><br>

    <label>Email:</label><br>
    <input type="email" name="email_usuario" value="<?= $_POST['email_usuario'] ?? '' ?>" required><br><br>

    <label>Senha:</label><br>
    <input type="password" name="senha_usuario" required><br><br>

    <label>Telefone:</label><br>
    <input type="text" name="telefone" maxlength="15" value="<?= $_POST['telefone'] ?? '' ?>" required><br><br>

    <label>CEP:</label><br>
    <input type="text" name="cep" maxlength="9" value="<?= $_POST['cep'] ?? '' ?>" required><br><br>

    <label>Cargo:</label><br>
    <select name="id_cargo" required>
        <option value="">Selecione o cargo</option>
        <?php foreach ($cargos as $cargo): ?>
            <option value="<?= $cargo['id_cargo']; ?>" <?= (($_POST['id_cargo'] ?? '') == $cargo['id_cargo']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cargo['nome_cargo']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Assiduidade (%):</label><br>
    <input type="number" name="assiduidade" step="0.01" value="<?= $_POST['assiduidade'] ?? '' ?>" required><br><br>

    <label>Data de Admissão:</label><br>
    <input type="date" name="data_admissao" value="<?= $_POST['data_admissao'] ?? '' ?>" required><br><br>

    <button type="submit">Cadastrar</button>
  </form>

  <br>
  <a href="lista.php">Voltar</a>

  <!-- Máscaras automáticas com JS Puro -->
  <script src="https://unpkg.com/imask"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      IMask(document.querySelector('[name="cpf_usuario"]'), { mask: '000.000.000-00' });
      IMask(document.querySelector('[name="rg_usuario"]'), { mask: '00.000.000-0' });
      IMask(document.querySelector('[name="telefone"]'), { mask: '(00) 00000-0000' });
      IMask(document.querySelector('[name="cep"]'), { mask: '00000-000' });
    });
  </script>
</body>
</html>