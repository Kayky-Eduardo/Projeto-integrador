<?php
session_start();
include(__DIR__ . "../BD/conexao.php");
require "../include/verificacao.php";
verificar_login($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Config</title>
</head>
<body>
    <h1>Colocar hora máximo hora extra</h1>
    <div class="set-tempo">
        <form action="">
            <label for="set-jornada" >Jornada de trabalho: </label>
            <input id="set-tempo" type="time">
            <br>
            <label for="set-hora-max">Máximo de hora extra diário: </label>
            <input id="set-hora-max" type="time">
        </form> 
    </div>
</body>
<script>
    
</script>
</html>
