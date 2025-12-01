<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'];
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
    <a href="../relatorio_ponto.php">voltar</a>
    <h2>Registro de Ponto</h2>

    <div>
        <!-- Botão registrar ponto diário -->
        <button id="btnPonto">Registrar Ponto</button>

        <!-- Botão Almoço -->
        <button id="btnAlmoco">Carregando...</button>

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
    const btnAlmoco = document.getElementById('btnAlmoco');
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

            // popula select com tipos comuns (exclui almoco)
            selectPausa.innerHTML = '';
            (data.tipos_comuns || []).forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id_config;
                opt.textContent = t.descricao_pausa + (t.usado ? ' (já usado hoje)' : '');
                opt.disabled = !!t.usado;
                selectPausa.appendChild(opt);
            });

            // atualizar botões conforme estado
            if (data.aberta) {
                // se pausa almoço aberta
                if (data.tipo === 'almoco') {
                    btnAlmoco.textContent = 'Finalizar Almoço';
                    btnAlmoco.disabled = false;
                    btnPausa.textContent = 'Iniciar Pausa';
                    btnPausa.disabled = true;
                    selectPausa.disabled = true;
                } else { // se pausa comum aberta
                    btnPausa.textContent = 'Finalizar Pausa';
                    btnPausa.disabled = false;
                    selectPausa.disabled = true;
                    btnAlmoco.textContent = 'Iniciar Almoço';
                    btnAlmoco.disabled = true;
                }
                resultado.textContent = `Pausa aberta: ${data.descricao_aberta}`;
            } else {
                // sem pausa aberta
                // Almoço
                if (data.almoco_usado) {
                    btnAlmoco.textContent = 'Almoço usado';
                    btnAlmoco.disabled = true;
                } else {
                    btnAlmoco.textContent = 'Iniciar Almoço';
                    btnAlmoco.disabled = false;
                }

                // Pausa comum
                // se já fez todas as pausas comuns, desabilita botão
                const existeOpcaoHabilitada = (data.tipos_comuns || []).some(t => !t.usado);
                if (!existeOpcaoHabilitada) {
                    btnPausa.textContent = 'Pausas concluídas';
                    btnPausa.disabled = true;
                    selectPausa.disabled = true;
                } else {
                    btnPausa.textContent = 'Iniciar Pausa';
                    btnPausa.disabled = false;
                    selectPausa.disabled = false;
                }

                if (clearResultado) resultado.textContent = '';
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

    // Iniciar almoço
    btnAlmoco.addEventListener('click', async () => {
        if (btnAlmoco.disabled) return;
        // Se texto tem "Finalizar" -> finalizar
        if (btnAlmoco.textContent.toLowerCase().includes('finalizar')) {
            await fetch('pausa_finalizar.php', { method: 'POST' })
                .then(r => r.json())
                .then(j => resultado.textContent = j.message || JSON.stringify(j))
                .catch(e => resultado.textContent = 'Erro ao finalizar');
        } else {
            // iniciar almoço
            await fetch('pausa_iniciar.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'tipo=almoco'
            })
            .then(r => r.json())
            .then(j => resultado.textContent = j.message || JSON.stringify(j))
            .catch(e => resultado.textContent = 'Erro ao iniciar almoço');
        }
        await carregarEstado(false);
    });

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
    const btnPonto = document.getElementById('btnPonto');
    btnPonto.addEventListener('click', async () => {
        try {
            const r = await fetch('sql_registrar_ponto.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'pausa=ponto'
            });
            const text = await r.text();
            resultado.textContent = text;
        } catch (e) {
            resultado.textContent = 'Erro ao registrar ponto';
        }
        await carregarEstado(false);
    });

    // inicializa
    window.onload = () => carregarEstado();
    </script>
</body>
</html>
