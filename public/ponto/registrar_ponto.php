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
        <label for="entradas">Entrada:</label>

        <select id="select">
            <option value="expediente">Expediente</option>
            <option value="almoco">Almoço</option>
        </select>
    </form>
    <button id="btn_registrar">Registrar Dados</button><br>
    <a id="resultado">Resultado: </a><br>


    <script>
        const btn = document.getElementById("btn");
        const btnImprimir = document.getElementById("btn_registrar");
        const select = document.getElementById("select");
        const resultado = document.getElementById("resultado");
        var array = {
            entrada: {registrado: 0},
            almoco_entrada: {registrado: 0}
        };

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
            fetch("sql_registrar_ponto.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body:
                    "tipo=" + encodeURIComponent(select.value) +
                    "&evento=" + encodeURIComponent(array[select.value].registrado == 1 ? "inicio" : "fim") +
                    "&time=" + encodeURIComponent(time)
            });
        }
    </script>
</body>

</html>