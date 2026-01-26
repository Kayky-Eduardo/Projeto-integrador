<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Configurações</title>
    <link rel="stylesheet" href="../../assets/css/estilo_R01.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="configuracoes">
        <aside aria-label="Menu de configurações">
            <ul class="menu-config">
                <li><a href="#" class="ativo">Definir Jornadas de Trabalho</a></li>
                <li><a href="#">#</a></li>
                <li><a href="#">#</a></li>
                <li><a href="#">#</a></li>
            </ul>
        </aside>

        <section class="painel-config" aria-label="Definição de jornada">
            <h1>Definir Jornada de Trabalho</h1>
            <p>Configure a carga horária e o limite diário de horas extras.</p>

            <form id="form-jornada">
                <fieldset>
                    <label for="set-jornada">Jornada diária padrão:</label>
                    <input id="set-jornada" type="time" required>

                    <label for="set-hora-max">Limite de hora extra:</label>
                    <input id="set-hora-max" type="time" required>

                    <button type="submit">Salvar configuração</button>
                </fieldset>
            </form>

            <p id="resposta" role="alert" aria-live="polite" tabindex="0"></p>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>