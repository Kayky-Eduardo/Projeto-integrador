<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Ponto</title>
</head>

<body>
    <a href="relatorio_ponto.php">voltar</a>
    <h2>Registro de Ponto</h2>

    <label for="evento">Evento:</label>
    <select id="evento">
        <option value="entrada">Entrada</option>
        <option value="saida">Saída</option>
        <option value="almoco_saida">Almoço - Saída</option>
        <option value="almoco_retorno">Almoço - Retorno</option>
    </select>

    <button id="btn_registrar">Registrar Dados</button><br><br>

    <p id="resultado"></p>

    <script>
        const select = document.getElementById("evento");
        const btn = document.getElementById("btn_registrar");
        const resultado = document.getElementById("resultado");

        btn.onclick = () => {
            const evento = select.value;
            const now = new Date();
            const hora = now.toLocaleTimeString("pt-BR", { hour12: false });

            fetch("sql_registrar_ponto.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body:
                    "evento=" + encodeURIComponent(evento) +
                    "&hora=" + encodeURIComponent(hora)
            })
            .then(r => r.text())
            .then(t => resultado.textContent = t);
        }

        window.onload = () => {
            fetch("status_ponto.php")
            .then(r => r.json())
            .then(status => {
                const select = document.getElementById("evento");

                // percorre cada opção
                for (let opt of select.options) {
                    if (status[opt.value] === true) {
                        opt.disabled = true;
                    }
                }
            });
        };
    </script>
</body>
</html>