<!--
    MÓDULO: CONFIGURAÇÕES DO SISTEMA

    OBJETIVO
        Permitir a administração de parâmetros operacionais do sistema.

    ESTRUTURA SEMÂNTICA
        nav     - Barra de navegação principal
        main    - Área principal da página
        aside   - Menu lateral de configurações
        section - Painel de conteúdo
        form    - Formulário de configuração
        fieldset- Agrupamento dos campos

    FUNCIONALIDADES
        1. Definir jornada diária de trabalho
        2. Definir limite máximo de horas extras
        3. Envio dos dados via fetch (AJAX)
        4. Validação de campos obrigatórios
        5. Exibição de mensagens de status ao usuário

    ACESSIBILIDADE
        role="navigation" - Navegação da aplicação
        role="main"       - Conteúdo principal
        aria-label        - Descrição para leitores de tela
        tabindex="0"      - Leitura dinâmica da resposta

    BENEFÍCIOS
        - Configuração centralizada
        - Respostas imediatas ao usuário
        - Código organizado para futuras expansões

    OBSERVAÇÕES TÉCNICAS
        - Comunicação via API REST (api_jornada.php)
        - Requisições usando JSON
        - Layout controlado por CSS centralizado
-->

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
    <title>Configurações | Sistema RH</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav role="navigation">
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main role="main" class="configuracoes">
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

                    <button type="submit">Salvar configuração</button>
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