<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
$ponto = [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Ponto</title>
</head>
<body>
    <h2>Registro de Ponto</h2>

    <form method="post" action="sql_registrar_ponto.php">
        <a>Entrada</a>
        <input type="time" name="entrada">
        <a>Saída</a>
        <input type="time" name="saida">
        <a>Almoço Entrada</a>
        <input type="time" name="almoco_entrada">
        <a>Almoço Saída</a>
        <input type="time" name="almoco_saida">
        <a>Observações</a>
        <textarea name="observacao"></textarea>
        <button type="submit" name="registro">Registrar</button>
    </form>

    <br>
    <?php
    if (empty($_POST['entrada']) || empty($_POST['saida'])) {
        echo "Preencha todos os campos obrigatórios.";
        exit;
    }
    if (isset($_POST['registro'])) {
        echo "Registrado em: " . date('d-m-Y H:i:s');
    }
    ?>
</body>
</html>