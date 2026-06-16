<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNotify — Página de Testes</title>

    <!-- PNotify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/BrightTheme.css" rel="stylesheet">
    <link rel="stylesheet" href="/projeto-integrador/assets/css/estilo.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Syne:wght@400;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0d0d0d;
            --surface: #161616;
            --border: #2a2a2a;
            --accent: #e8ff47;
            --accent2: #ff5c5c;
            --accent3: #5caaff;
            --accent4: #c084fc;
            --text: #f0f0f0;
            --muted: #666;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Syne', sans-serif;
            min-height: 100vh;
            padding: 48px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Grid noise overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(232,255,71,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(232,255,71,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 720px;
        }

        header {
            margin-bottom: 48px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 24px;
        }

        .tag {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--accent);
            border: 1px solid var(--accent);
            padding: 3px 10px;
            border-radius: 2px;
            letter-spacing: 0.1em;
            margin-bottom: 14px;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        h1 span { color: var(--accent); }

        .subtitle {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--muted);
            margin-top: 10px;
        }

        /* Sections */
        .section {
            margin-bottom: 36px;
        }

        .section-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Buttons */
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
        }

        .btn {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 600;
            padding: 14px 18px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn:hover { transform: translateY(-2px); filter: brightness(1.2); }
        .btn:active { transform: translateY(0); }

        .btn .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .btn-success  { border-color: #22c55e33; }
        .btn-success .dot  { background: #22c55e; box-shadow: 0 0 6px #22c55e; }
        .btn-success:hover  { border-color: #22c55e; background: #22c55e15; }

        .btn-info  { border-color: var(--accent3)33; }
        .btn-info .dot  { background: var(--accent3); box-shadow: 0 0 6px var(--accent3); }
        .btn-info:hover  { border-color: var(--accent3); background: #5caaff15; }

        .btn-warning  { border-color: #f59e0b33; }
        .btn-warning .dot  { background: #f59e0b; box-shadow: 0 0 6px #f59e0b; }
        .btn-warning:hover  { border-color: #f59e0b; background: #f59e0b15; }

        .btn-error  { border-color: var(--accent2)33; }
        .btn-error .dot  { background: var(--accent2); box-shadow: 0 0 6px var(--accent2); }
        .btn-error:hover  { border-color: var(--accent2); background: #ff5c5c15; }

        .btn-confirm  { border-color: var(--accent4)33; }
        .btn-confirm .dot  { background: var(--accent4); box-shadow: 0 0 6px var(--accent4); }
        .btn-confirm:hover  { border-color: var(--accent4); background: #c084fc15; }

        .btn-accent  { border-color: var(--accent)33; }
        .btn-accent .dot  { background: var(--accent); box-shadow: 0 0 6px var(--accent); }
        .btn-accent:hover  { border-color: var(--accent); background: #e8ff4715; }

        /* Log */
        .log-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            min-height: 80px;
            max-height: 200px;
            overflow-y: auto;
            color: var(--muted);
        }

        .log-entry {
            padding: 3px 0;
            border-bottom: 1px solid #1e1e1e;
            display: flex;
            gap: 12px;
        }

        .log-entry:last-child { border-bottom: none; }
        .log-time { color: var(--accent); flex-shrink: 0; }
        .log-confirm { color: #22c55e; }
        .log-cancel  { color: var(--accent2); }

        /* Status badge */
        #status-pnotify {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 32px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-ok   { background: #22c55e15; border: 1px solid #22c55e44; color: #22c55e; }
        .status-err  { background: #ff5c5c15; border: 1px solid #ff5c5c44; color: var(--accent2); }

        .pulse {
            width: 8px; height: 8px; border-radius: 50%;
            background: currentColor;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    </style>
<? require_once "/projeto-integrador/include/link.html"; ?>
</head>
<body>
<div class="wrapper">

    <header>
        <div class="tag">debug / test</div>
        <h1>PNotify <span>Testes</span></h1>
        <p class="subtitle">// clique nos botões para disparar cada tipo de notificação</p>
    </header>

    <div id="status-pnotify"></div>

    <!-- Notificações simples -->
    <div class="section">
        <div class="section-title">Notificações Simples</div>
        <div class="btn-grid">
            <button class="btn btn-success" onclick="testarSuccess()">
                <span class="dot"></span> Success
            </button>
            <button class="btn btn-info" onclick="testarInfo()">
                <span class="dot"></span> Info
            </button>
            <button class="btn btn-warning" onclick="testarAviso()">
                <span class="dot"></span> Warning / Aviso
            </button>
            <button class="btn btn-error" onclick="testarErro()">
                <span class="dot"></span> Alert / Erro
            </button>
        </div>
    </div>

    <!-- Confirm -->
    <div class="section">
        <div class="section-title">Confirm</div>
        <div class="btn-grid">
            <button class="btn btn-confirm" onclick="testarConfirm()">
                <span class="dot"></span> Confirm padrão
            </button>
            <button class="btn btn-confirm" onclick="testarConfirmDestructive()">
                <span class="dot"></span> Confirm destrutivo
            </button>
        </div>
    </div>

    <!-- Duração personalizada -->
    <div class="section">
        <div class="section-title">Duração / Sticky</div>
        <div class="btn-grid">
            <button class="btn btn-accent" onclick="testarRapido()">
                <span class="dot"></span> Rápido (2s)
            </button>
            <button class="btn btn-accent" onclick="testarLento()">
                <span class="dot"></span> Lento (10s)
            </button>
            <button class="btn btn-accent" onclick="testarSticky()">
                <span class="dot"></span> Sticky (não fecha)
            </button>
        </div>
    </div>

    <!-- Log -->
    <div class="section">
        <div class="section-title">Log de ações</div>
        <div class="log-box" id="log-box">
            <div class="log-entry"><span class="log-time">--:--:--</span><span>Aguardando ação...</span></div>
        </div>
    </div>

</div>

<!-- PNotify Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@pnotify/mobile@5.2.0/dist/PNotifyMobile.js"></script>

<script>
    /* ── STATUS CHECK ─────────────────────────────── */
    const statusEl = document.getElementById('status-pnotify');

    window.addEventListener('load', () => {
        const okCore = typeof PNotify !== 'undefined';

        if (okCore) {
            statusEl.className = 'status-ok';
            statusEl.innerHTML = '<span class="pulse"></span> PNotify core carregado com sucesso ✓ &nbsp;|&nbsp; confirm via HTML customizado ✓';
        } else {
            statusEl.className = 'status-err';
            statusEl.innerHTML = '<span class="pulse"></span> Erro: PNotify core não encontrado';
        }
    });

    /* ── LOG ──────────────────────────────────────── */
    function log(msg, tipo = '') {
        const box = document.getElementById('log-box');
        const now = new Date().toLocaleTimeString('pt-BR');
        const entry = document.createElement('div');
        entry.className = 'log-entry';
        entry.innerHTML = `<span class="log-time">${now}</span><span class="log-${tipo}">${msg}</span>`;
        box.prepend(entry);
    }

    /* ── FUNÇÕES PNOTIFY ─────────────────────────── */
    function chamarPnotifySuccess(titulo, mensagem, ms) {
        log(`✓ Success disparado: "${mensagem}"`, 'confirm');
        PNotify.success({ title: titulo, text: mensagem, delay: ms ?? 5000 });
    }

    function chamarPnotifyInfo(titulo, mensagem, ms) {
        log(`ℹ Info disparado: "${mensagem}"`);
        PNotify.info({ title: titulo, text: mensagem, delay: ms ?? 5000 });
    }

    function chamarPnotifyAviso(titulo, mensagem, ms) {
        log(`⚠ Warning disparado: "${mensagem}"`);
        PNotify.info({ title: titulo, text: mensagem, delay: ms ?? 5000 });
    }

    function chamarPnotifyAlert(titulo, mensagem, ms) {
        log(`✖ Alert disparado: "${mensagem}"`, 'cancel');
        PNotify.alert({ title: titulo, text: mensagem, delay: ms ?? 5000 });
    }

    function chamarPnotifyConfirm(titulo, mensagem, aoConfirmar, aoCancelar) {
        log(`? Confirm aberto: "${mensagem}"`);
 
        const notice = PNotify.notice({
            title: titulo,
            text: `
                <p class="pn-mensagem">${mensagem}</p>
                <div class="pn-botoes">
                    <button id="pn-confirmar" class="pn-btn pn-btn-confirmar">Confirmar</button>
                    <button id="pn-cancelar" class="pn-btn pn-btn-cancelar">Cancelar</button>
                </div>
            `,
            textTrusted: true,
            hide: false,
            closer: false,
            sticker: false
        });
 
        setTimeout(() => {
            document.getElementById('pn-confirmar')?.addEventListener('click', () => {
                notice.close();
                log('→ Usuário clicou: CONFIRMAR', 'confirm');
                if (typeof aoConfirmar === 'function') aoConfirmar();
            });
            document.getElementById('pn-cancelar')?.addEventListener('click', () => {
                notice.close();
                log('→ Usuário clicou: CANCELAR', 'cancel');
                if (typeof aoCancelar === 'function') aoCancelar();
            });
        }, 100);
    }

    /* ── BOTÕES DE TESTE ─────────────────────────── */
    function testarSuccess() {
        chamarPnotifySuccess('Sucesso!', 'Operação realizada com sucesso.');
    }

    function testarInfo() {
        chamarPnotifyInfo('Informação', 'Isso é uma notificação informativa.');
    }

    function testarAviso() {
        chamarPnotifyAviso('Atenção', 'Isso é um aviso importante.');
    }

    function testarErro() {
        chamarPnotifyAlert('Erro', 'Algo deu errado na operação.');
    }

    function testarConfirm() {
        chamarPnotifyConfirm(
            'Confirmar ação',
            'Tem certeza que deseja continuar?',
            () => chamarPnotifySuccess('Confirmado!', 'Ação executada com sucesso.'),
            () => chamarPnotifyAlert('Cancelado', 'Ação cancelada pelo usuário.')
        );
    }

    function testarConfirmDestructive() {
        chamarPnotifyConfirm(
            'Deletar registro',
            'Esta ação é irreversível. Deseja deletar?',
            () => chamarPnotifySuccess('Deletado!', 'Registro removido com sucesso.'),
            () => log('→ Deleção cancelada.', 'cancel')
        );
    }

    function testarRapido() {
        PNotify.success({ title: 'Rápido', text: 'Fecha em 2 segundos.', delay: 2000 });
        log('⚡ Notificação rápida (2s)');
    }

    function testarLento() {
        PNotify.info({ title: 'Lento', text: 'Fecha em 10 segundos.', delay: 10000 });
        log('🕐 Notificação lenta (10s)');
    }

    function testarSticky() {
        PNotify.info({ title: 'Sticky', text: 'Feche manualmente clicando no ×.', hide: false });
        log('📌 Notificação sticky disparada');
    }
</script>
</body>
</html>