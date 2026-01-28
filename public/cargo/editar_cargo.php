<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);


if(isset($_GET['id'])) {
    $id_cargo = $_GET['id'];
}


$stmt = $conn->prepare("SELECT * FROM cargo WHERE id_cargo = ?");
$stmt->bind_param("i", $id_cargo);
$stmt->execute();
$cargo = $stmt->get_result()->fetch_assoc();

if (!$cargo) {
    header("Location: cargos.php");
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cargo</title>
</head>
<body>
    <header>
        <?php include("../../include/navbar.php");?>
    </header>

    <h2>Editar cargo</h2>

    <div id="mensagem"></div>

    <form id="form-cargo" method="POST">
        <fieldset>
            <label>Id Cargo</label><br>
            <input type="text" value="<?= $cargo['id_cargo'] ?>" readonly>    
            <br><br>
    
            
            <label>Nome do cargo:</label><br>
            <input type="text" id="nome_cargo" name="nome_cargo"
            value="<?= htmlspecialchars($cargo['nome_cargo']) ?>" required>
            <br><br>
    
            <label>Salário bruto</label><br>
            <input type="number" name="salario" value="<?= $cargo['salario_bruto'] ?>" required>
            <br><br>
    
            <label>Nível:</label><br>
            <input type="number" id="nivel" name="nivel" value="<?= $cargo['nivel'] ?>"
            step="1" max="5" min="1" required>
            <br><br>
            
            </div>
    
            <button id="editar" type="submit">Salvar</button>
            <a class="btn-excluir" href="deletar_cargo.php?cargo=<?= $id_cargo ?>">Excluir</a>
    
            <a href="cargoes.php">Voltar</a>
            <br>
        </fieldset>
    </form>
</body>
</html>