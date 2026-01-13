<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require_once __DIR__ . '/../../../include/verificacao.php';
verificar_login($conn);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Ponto</title>
    <style>
      button:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>
    <a href="../../index.php">voltar</a>
    <h2>Registro de Ponto</h2>

    <div id='controles'>
        <!-- Botão registrar ponto diário -->
        <button id="btnPonto">Iniciar Ponto</button>

        <!-- Pausa comum: select + botão -->
        <label for="pausa_tipo">Tipo de pausa:</label>
        <select id="pausa_tipo"></select>
        <button id="btnPausa">Carregando...</button>
    </div>

    <p id="resultado"></p>

    <!-- Inserindo lista de pausas ativas (será atualizada dinamicamente) -->
    <div id="pausas_ativas_container">
        <?php include __DIR__ . '/pausas_ativas.php'; ?>
    </div>

    <script>
        const controles = document.getElementById('controles');
        const btnPonto = document.getElementById('btnPonto');
        const btnPausa = document.getElementById('btnPausa');
        const selectPausa = document.getElementById('pausa_tipo');
        const resultado = document.getElementById('resultado');

        // Carrega estado inicial e popula select
        // clearResultado: se true (padrão) apaga a mensagem em `resultado`; se false preserva a mensagem atual
        async function carregarEstado(clearResultado = true) {
            try {
                const res = await fetch('pausa_estado.php');
                if (!res.ok) throw new Error('Erro ao obter estado');
                const data = await res.json();

                // Se ponto finalizado, desabilita todos os controles de ponto/pausa
                if (data.fim_ponto === true) {
                    btnPausa.disabled = true;
                    selectPausa.disabled = true;
                    btnPonto.disabled = true;
                    btnPonto.textContent = 'Finalizar Ponto';
                    if (clearResultado) resultado.textContent = 'Ponto diário finalizado. Não é possível iniciar pausas.';
                }
                // se ponto ainda não for registrado, desabilita select e botão de pausa
                else if (data.ponto_registrado === false) {
                    btnPausa.disabled = true;
                    selectPausa.disabled = true;
                    btnPonto.disabled = false;
                    btnPonto.textContent = 'Iniciar Ponto';
                    if (clearResultado) resultado.textContent = 'Registre o ponto diário antes de iniciar uma pausa.';
                } else {
                    // ponto iniciado e não finalizado
                    btnPonto.disabled = false;
                    btnPonto.textContent = 'Finalizar Ponto';
                    // popula select
                    selectPausa.innerHTML = '';
                    (data.tipos_comuns || []).forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id_config;
                        opt.textContent = t.descricao_pausa + (t.usado ? ' (já usado hoje)' : '');
                        opt.disabled = !!t.usado;
                        selectPausa.appendChild(opt);
                    });

                    // atualizar botões conforme estado
                    if (data.pausa_aberta) {
                        btnPausa.textContent = 'Finalizar Pausa';
                        btnPausa.disabled = false;
                        selectPausa.disabled = true;
                        btnPonto.disabled =true;
                        resultado.textContent = `Pausa aberta: ${data.descricao_aberta}`;
                    } else {
                        // sem pausa aberta
                            btnPausa.textContent = 'Iniciar Pausa';
                            btnPausa.disabled = false;
                            btnPonto.disabled = false;
                            selectPausa.disabled = false;

                        if (clearResultado) resultado.textContent = '';
                    }
                }
            } catch (err) {
                if (clearResultado) resultado.textContent = 'Erro ao carregar estado';
                console.error(err);
            }

            // Atualiza a lista de pausas ativas (fragmento HTML)
            try {
                const container = document.getElementById('pausas_ativas_container');
                const r2 = await fetch('pausas_ativas.php', { credentials: 'same-origin' });
                if (r2.ok) {
                    const html = await r2.text();
                    container.innerHTML = html;
                }
            } catch (e) {
                console.error('Erro ao atualizar pausas ativas', e);
            }
        }

        // Iniciar / finalizar pausa comum
        btnPausa.addEventListener('click', async () => {
            if (btnPausa.disabled) return;
            if (btnPausa.textContent.toLowerCase().includes('finalizar')) {
                await fetch('pausa_finalizar.php', { method: 'POST' })
                    .then(r => r.json())
                    .then(j => resultado.textContent = j.message || JSON.stringify(j))
                    .catch(e => resultado.textContent = 'Erro ao finalizar pausa');
            } else {
                const idConfig = selectPausa.value;
                if (!idConfig) { resultado.textContent = 'Selecione o tipo de pausa.'; return; }

                await fetch('pausa_iniciar.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'tipo=pausa&id_config=' + encodeURIComponent(idConfig)
                })
                .then(r => r.json())
                .then(j => resultado.textContent = j.message || JSON.stringify(j))
                .catch(e => resultado.textContent = 'Erro ao iniciar pausa');
            }
            await carregarEstado(false);
        });

        // Registrar ponto diário (entrada/saída)
        btnPonto.addEventListener('click', async () => {
            try {
                const r = await fetch('sql_registrar_ponto.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: ''
                });
                const text = await r.text();
                resultado.textContent = text;
            } catch (e) {
                resultado.textContent = 'Erro ao registrar ponto';
            }
            await carregarEstado(false);
        });

        // inicializa
        window.onload = () => {
            // corrige quaisquer links relativos para logout que venham do navbar incluído externamente
            try {
                const anchors = document.querySelectorAll('a[href$="logout.php"], a[href="logout.php"]');
                anchors.forEach(a => {
                    // ajusta para o caminho correto relativo a esta página
                    a.href = '../../logout.php';
                });
            } catch (e) {
                console.error('Erro ao ajustar links de logout', e);
            }
            carregarEstado();
        };
    </script>
</body>
</html>