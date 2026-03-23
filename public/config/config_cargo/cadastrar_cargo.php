<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$erro = "";
$max = $_SESSION['nivel'] >= 3 ? 3 : 2;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nome_cargo'], $_POST['nivel'], $_POST['salario'])) {
        if (!empty($_POST['nome_cargo']) && !empty($_POST['nivel']) && !empty($_POST['salario'])) {
            $nome_cargo = $_POST['nome_cargo'];

            $stmt_validacao = $conn->prepare("SELECT id_cargo FROM cargo WHERE nome_cargo = ?");
            $stmt_validacao->bind_param("s", $nome_cargo);
            $stmt_validacao->execute();
            $validacao = $stmt_validacao->get_result();
            if ($validacao->num_rows === 0) {
                $stmt = $conn->prepare("
                INSERT INTO cargo (nome_cargo, salario_bruto, nivel) VALUES
                (?, ?, ?)
                ");
                $stmt->bind_param("sdi", $nome_cargo, $_POST['salario'], $_POST['nivel']);
                if ($stmt->execute()) {
                    header("Location: cargos.php");
                } else {
                    $erro = "Verifique se está tudo preenchido de forma correta.";
                }
            } else {
                $erro = "Já existe um cargo com este nome!"; 
            }
        } else {
            $erro = "Preencha todos os campos!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar cargo</title>

</head>
<body>
    <header>
        <?php include("../../include/navbar.php");?>
    </header>

    <h2>Cadastrar Novo cargo</h2>

    <?php if (!empty($erro)): ?>
        <p class="erro" role="alert">
            <?= htmlspecialchars($erro) ?>
        </p>
    <?php endif; ?>

    <form id="form-cargo" method="POST">
        <fieldset>
            <label>Nome do Cargo:</label><br>
            <input type="text" id="nome_cargo" name="nome_cargo"
            placeholder="Digite o nome do cargo" required>
            <br><br>
    
            <label>Salário bruto</label><br>
            <input type="number" id="salario_bruto" name="salario" placeholder="Ex: 2000" required>
            <br><br>
    
            <label>Nível:</label><br>
            <input type="number" id="nivel" name="nivel" step="1" max="<?= $max ?>" min="1"
            placeholder="Ex: 1, 2, 3, 4, 5." required>
            <br><br>
    
            
            <br>
            <button id="cadastrar" type="submit">Cadastrar</button>
            <a href="cargos.php">Voltar</a>
        </fieldset>
    </form>
</body>
</html>