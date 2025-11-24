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
        <option value="ponto">Ponto</option>
        <option value="almoco">Almoço</option>
        <option value="pausa">Pausa (outros)</option>
    </select>

    <!-- Select de tipo de pausa — aparece só quando evento === "pausa" -->
    <label for="pausa_tipo" id="label_pausa_tipo" style="display:none;">Tipo de pausa:</label>
    <select id="pausa_tipo" style="display:none;">
        <option value="banheiro">Banheiro</option>
        <option value="cafe">Café</option>
        <option value="descanso">Descanso</option>
    </select>

    <button id="btn_registrar">Registrar Dados</button><br><br>

    <p id="resultado"></p>
    <p id="timer"></p>

    <script>
        const select = document.getElementById("evento");
        const pausaTipo = document.getElementById("pausa_tipo");
        const labelPausaTipo = document.getElementById("label_pausa_tipo");
        const btn = document.getElementById("btn_registrar");
        const resultado = document.getElementById("resultado");
        const timerEl = document.getElementById("timer");

        let intervalo = null; // referência para setInterval do timer

        // Mostrar/ocultar select de tipo de pausa
        select.onchange = () => {
            if (select.value === "pausa") {
                pausaTipo.style.display = "inline";
                labelPausaTipo.style.display = "inline";
            } else {
                pausaTipo.style.display = "none";
                labelPausaTipo.style.display = "none";
            }
        };

        // Converte "HH:MM:SS" em Date e inicia contador que atualiza o timer a cada segundo
        function iniciarTimer(horaInicio) {
            if (!horaInicio) return;

            clearInterval(intervalo);

            const [h, m, s] = horaInicio.split(":").map(Number);
            const inicio = new Date();
            inicio.setHours(h, m, s, 0);

            intervalo = setInterval(() => {
                const agora = new Date();
                const diff = Math.floor((agora - inicio) / 1000);

                const hh = String(Math.floor(diff / 3600)).padStart(2, "0");
                const mm = String(Math.floor((diff % 3600) / 60)).padStart(2, "0");
                const ss = String(diff % 60).padStart(2, "0");

                timerEl.textContent = "Tempo da pausa aberta: " + hh + ":" + mm + ":" + ss;
            }, 1000);
        }

        // Requisita status atual do servidor (ponto, almoço, pausa)
        function carregarStatus() {
            fetch("status_ponto.php")
            .then(r => {
                if (!r.ok) throw new Error("Resposta inválida do servidor");
                return r.json();
            })
            .then(status => {
                // Reactiva tudo por padrão
                for (let opt of select.options) opt.disabled = false;
                for (let opt of pausaTipo.options) opt.disabled = false;

                // Bloqueios relativos ao ponto/almoco (mantive sem desabilitar 'ponto' automaticamente;
                // você pode alterar conforme sua política)
                if (status.almoco_aberto === true) {
                    // Se já está em almoço (saiu e não retornou), bloquear opção de 'almoco'
                    for (let opt of select.options) {
                        if (opt.value === "almoco") opt.disabled = true;
                    }
                }

                // Se existe qualquer pausa aberta, o backend retorna objeto com tipo e inicio
                if (status.pausa_aberta) {
                    // desabilita o select principal "pausa" para evitar nova abertura
                    for (let opt of select.options) {
                        if (opt.value === "pausa") opt.disabled = true;
                    }
                    // desabilita tipos de pausa, exceto possivelmente o tipo atual (mas não permitiremos abrir)
                    for (let opt of pausaTipo.options) {
                        opt.disabled = true;
                    }

                    // inicia timer com a hora de início retornada
                    iniciarTimer(status.pausa_aberta.inicio);
                    resultado.textContent = "Pausa aberta: " + status.pausa_aberta.tipo;
                } else {
                    // sem pausa aberta: limpa timer
                    clearInterval(intervalo);
                    timerEl.textContent = "";
                }
            })
            .catch(err => {
                console.error(err);
                resultado.textContent = "Erro ao carregar status";
            });
        }

        // Ao clicar em registrar: envia para o mesmo arquivo sql_registrar_ponto.php (lógica unificada)
        btn.onclick = () => {
            const evento = select.value;
            let body = "pausa=" + encodeURIComponent(evento);

            if (evento === "pausa") {
                body += "&tipo=" + encodeURIComponent(pausaTipo.value);
            }

            fetch("sql_registrar_ponto.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body
            })
            .then(r => r.text())
            .then(txt => {
                resultado.textContent = txt;
                // atualizar status para refletir mudança imediatamente (e iniciar/limpar timer)
                carregarStatus();
            })
            .catch(err => {
                resultado.textContent = "Erro: " + err;
            });
        };

        window.onload = carregarStatus;
    </script>
</body>
</html>
