<?php
session_start();
include(__DIR__ . "/../../../BD/conexao.php");
require "../../../include/verificacao.php";
verificar_login($conn);


if(isset($_GET['id'])) {
    $id_cargo = $_GET['id'];
}

if (isset($_SESSION['erro_deletar_cargo'])) {
    $erro = $_SESSION['erro_deletar_cargo'];
    unset($_SESSION['erro_deletar_cargo']);
}

$max = $_SESSION['nivel'] >= 3 ? 3 : 2;

$stmt = $conn->prepare("SELECT * FROM cargo WHERE id_cargo = ?");
$stmt->bind_param("i", $id_cargo);
$stmt->execute();
$cargo = $stmt->get_result()->fetch_assoc();


if ($cargo['nivel'] > $_SESSION['nivel']) {
    header("Location: cargos.php");
}

$erro = "";
$erro_pagina = "";
$validacao = "";

if ($cargo['nivel'] > $_SESSION['nivel']) {
    $validacao = "readonly";
    $aviso = "Não é possível editar devido ao seu nível ser inferior ao do cargo!";
}

if (!$cargo) {
    header("Location: cargos.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nome_cargo'], $_POST['nivel'], $_POST['salario'])) {
        if ($_SESSION['nivel'] < (int)$_POST['nivel']) {
            $erro_pagina = "Não é possível criar um cargo com nível maior que o seu!";
        } else {
            if (!empty($_POST['nome_cargo']) && !empty($_POST['nivel']) && !empty($_POST['salario'])) {
                $stmt_validacao = $conn->prepare("SELECT id_cargo FROM cargo WHERE nome_cargo = ?");
                $stmt_validacao->bind_param("s", $nome_cargo);
                $stmt_validacao->execute();
                $validacao = $stmt_validacao->get_result();
                if ($validacao->num_rows === 0) {
                    $stmt = $conn->prepare("UPDATE cargo
                    SET nome_cargo = ?,
                    salario_bruto = ?,
                    nivel = ?
                    where id_cargo = ?;
                    ");
                    $stmt->bind_param("sdii", $_POST['nome_cargo'], $_POST['salario'], $_POST['nivel'], $id_cargo);
                    if ($stmt->execute()) {
                        header("Location: cargos.php");
                    } else {
                        $erro_pagina = "Verifique se está tudo preenchido de forma correta.";
                    }
                } else {
                    $erro_pagina = "Já existe um cargo com este nome!"; 
                }
            } else {
                $erro_pagina = "Preencha todos os campos!";
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
    <title>Editar Cargo</title>
    <link rel="stylesheet" href="../../../assets/css/estilo.css">

</head>
<body>
    <header>
        <?php include("../../include/navbar.php");?>
    </header>

    <h2>Editar cargo</h2>

    <p id="mensagem"></p>

    
    <form id="form-cargo" method="POST">
        <fieldset>
            <?php if (!empty($erro)): ?>
                <strong>Aviso:</strong>
                <p class="erro_pagina" role="alert">
                    <?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>

            <?php if (!empty($erro_pagina)): ?>
                <strong>Aviso:</strong>
                <p class="erro_pagina" role="alert">
                    <?= htmlspecialchars($erro_pagina) ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($aviso)): ?>
                <p class="aviso" role="alert">
                    <?= htmlspecialchars($aviso) ?>
                </p>
            <?php endif; ?>


            <label>Id Cargo</label><br>
            <input type="text" value="<?= $cargo['id_cargo'] ?>" readonly>    
            <br><br>
    
            
            <label>Nome do cargo:</label><br>
            <input type="text" id="nome_cargo" name="nome_cargo"
            value="<?= htmlspecialchars($cargo['nome_cargo']) ?>"  <?= $validacao ?> required>
            <br><br>
    
            <label>Salário bruto</label><br>
            <input type="number" name="salario" value="<?= $cargo['salario_bruto'] ?>" <?= $validacao ?> required>
            <br><br>
    
            <label>Nível:</label><br>
            <input type="number" id="nivel" name="nivel" value="<?= $cargo['nivel'] ?>" <?= $validacao ?>
            step="1" max="<?=$max?>" min="1" required>
            <br><br>
            
            <button id="editar" type="submit">Salvar</button>
            <a class="btn-excluir" href="deletar_cargo.php?cargo=<?= $id_cargo ?>">Excluir</a>
            <a href="cargos.php">Voltar</a>
            <br>
        </fieldset>
    </form>
</body>
</html>