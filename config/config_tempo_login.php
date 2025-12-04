<?php
session_start();
include(__DIR__ . "/../BD/conexao.php");
require "../include/verificacao.php";
require "../include/navbar.php";
verificar_login($conn);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Config</title>
</head>
<body>
    <h1>Colocar hora máximo hora extra</h1>
    <div class="set-tempo">
        <div class="formulario">
            <div class="form-group">
                <label for="set-jornada" >Jornada de trabalho: </label>
                <input id="set-jornada" type="time">
            </div>
            <br>
            <div class="form-group">
                <label for="set-hora-max">Máximo de hora extra diário: </label>
                <input id="set-hora-max" type="time">
            </div>
            <br>
            <button onclick="setJornada()">Aplicar</button>
        </div>
    </div>
    <p id="resposta"></p>
</body>
<script>
    async function setJornada() {
        const jornada = document.getElementById('set-jornada').value;
        const hora_extra = document.getElementById('set-hora-max').value;
        const resposta = document.getElementById('resposta');

        if (!jornada || !hora_extra) {
            console.log("Por favor, preencha os campos de jornada e hora extra.");
            return;
        }
        try {
            const response = await fetch(`../api/api_jornada.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    jornada: jornada,
                    hora_extra: hora_extra
                })
            });
            resposta.textContent = "Jornada configurada com sucesso!";
        } catch (error) {
            console.log("Problema ao realizar ação" + error);
            resposta.textContent = "Erro ao configurar jornada!";
        }
    }
</script>
</html>
