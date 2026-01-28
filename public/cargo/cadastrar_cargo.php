<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nome_cargo'], $_POST['nivel'], $_POST['salario'])) {
        if (!empty($_POST['nome_cargo']) && !empty($_POST['nivel']) && !empty($_POST['salario'])) {
            $stmt = $conn->prepare("
            INSERT INTO cargo (nome_cargo, salario_bruto, nivel) VALUES
            (?, ?, ?)
            ");
            $stmt->bind_param("sdi", $_POST['nome_cargo'], $_POST['salario'], (int)$_POST['nivel']);
            if ($stmt->execute()) {
                header("Location: cargos.php");
            } else {
                echo "Verifique se está tudo correto";
            }
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

    <div id="mensagem"></div>

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
            <input type="number" id="nivel" name="nivel" step="1" max="5" min="1"
            placeholder="Ex: 1, 2, 3, 4, 5." required>
            <br><br>
    
            
            <br>
            <button id="cadastrar" type="submit">Cadastrar</button>
            <a href="cargos.php">Voltar</a>
        </fieldset>
    </form>
</body>
</html>