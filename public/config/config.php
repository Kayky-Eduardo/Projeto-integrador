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
    <link rel="stylesheet" href="../../assets/css/estilo.css">
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

            <form onsubmit="setJornada(); return false;">
                <fieldset>
                    <label for="set-jornada">Jornada diária padrão</label>
                    <input id="set-jornada" type="time" required>

                    <label for="set-hora-max">Limite de hora extra</label>
                    <input id="set-hora-max" type="time" required>

                    <button type="submit" class="btn-padrao">Salvar configuração</button>
                </fieldset>
            </form>

            <p id="resposta" tabindex="0"></p>
        </section>
    </main>

    <script>
        async function setJornada() {
            const jornada = document.getElementById('set-jornada').value;
            const hora_extra = document.getElementById('set-hora-max').value;
            const resposta = document.getElementById('resposta');

            if (!jornada || !hora_extra) {
                resposta.textContent = "Preencha todos os campos.";
                return;
            }

            try {
                const response = await fetch(`../../api/api_jornada.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        jornada: jornada,
                        hora_extra: hora_extra
                    })
                });

                if (response.ok) {
                    resposta.textContent = "Jornada configurada com sucesso!";
                } else {
                    resposta.textContent = "Erro ao salvar configuração.";
                }

            } catch (error) {
                resposta.textContent = "Falha na comunicação com o servidor.";
            }
        }
    </script>
</body>

</html>