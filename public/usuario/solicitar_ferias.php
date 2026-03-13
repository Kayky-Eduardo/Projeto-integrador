<?php
session_start();
require "../../include/verificacao.php";
include "../../BD/conexao.php";
verificar_login($conn);

$nome_usuario = $_SESSION['nome_usuario'];

$erro = "";
$from = "";
$to = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $from = $_POST['from'] ?? "";
    $to = $_POST['to'] ?? "";

    $hoje = date("Y-m-d");

    if(empty($from) || empty($to)){
        $erro = "Preencha todas as datas.";
    }
    elseif($from < $hoje){
        $erro = "A data de início não pode ser no passado.";
    }
    elseif($to < $from){
        $erro = "A data final não pode ser menor que a inicial.";
    }
    else{

        $inicio = new DateTime($from);
        $fim = new DateTime($to);

        $dias = $inicio->diff($fim)->days + 1;

        if($dias > 30){
            $erro = "As férias não podem ultrapassar 30 dias.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar férias</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <!--Cabeçalho do Site-->
    <header>
        <nav>
            <?php include("../../include/navbar.php"); ?>
        </nav>
    </header>

    <!--Container para o conteúdo-->
    <main>
        <h1>Solicitação de Férias</h1>
        <h2>Olá, <?php echo $nome_usuario; ?></h2>

        <form method="POST">

        <label for="from">Data de Início</label>
        <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>" min="<?= date('Y-m-d')?>" required><br>

        <label for="to">Até:</label>
        <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>" required><br>

        <label for="obs">Observação (opcional)</label><br>
        <textarea name="obs" id="obs" rows="3" placeholder="Alguma observação sobre as férias..."></textarea><br>

        <button type="submit">Solicitar</button>

        </form>
        <?php
        if($erro != ""){
            echo "<p class='erro-login''>$erro</p>";
        }
        ?>
    </main>

</body>

</html>