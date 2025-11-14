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
        <!-- <a>Entrada</a>
        <input type="time" name="entrada">
        <a>Saída</a>
        <input type="time" name="saida">
        <a>Almoço Entrada</a>
        <input type="time" name="almoco">
        <a>Almoço Saída</a>
        <input type="time" name="almoco_saida">
        <a>Observações</a>
        <textarea name="observacao"></textarea>
        <button type="submit" name="registro">Registrar</button> -->

        <label for="entradas">Entrada:</label>

        <select name="entradas" id="select">
            <option value="entrada">Expediente</option>
            <option value="almoco">Almoço</option>
        </select>
        <button id="btn" type="button"></button>
    </form>
    <button id="btn_registrar">Registrar Dados</button><br>
    <a id="resultado">Resultado: </a><br>


    <script>
        const btn = document.getElementById("btn");
        const btnImprimir = document.getElementById("btn_registrar");
        const select = document.getElementById("select");
        const resultado = document.getElementById("resultado");


        btn.textContent = 'Iniciar';
        btn.addEventListener("click", () => {
            array[select.value].registrado += 1;
            btn.textContent = array[select.value].registrado == 1 ? "Sair" : "Iniciar";
            if (array[select.value].registrado == 2) {
                retornarTempo();
                btn.setAttribute('disabled', 'disabled');
            } else if (array[select.value].registrado <= 1) {
                retornarTempo();
                btn.removeAttribute('disabled');
            }
        });

        select.addEventListener('change', () => {
            if (array[select.value].registrado <= 1) btn.removeAttribute('disabled');
            btn.textContent = array[select.value].registrado == 1 ? "Sair" : "Iniciar";
        })

        function retornarTempo() {
            var now = new Date();
            var time = now.getHours() + ":" + now.getMinutes() + ":" + now.getSeconds();
            fetch("processa.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "time=" + encodeURIComponent(time)
            })
            .then(r => r.text())
            .then(retorno => {
                console.log("Resposta do PHP:", retorno);
            });
        }
    </script>

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