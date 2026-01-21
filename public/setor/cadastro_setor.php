<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// Buscar todos os usuários para o select
$usuarios = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Setor</title>
    <style>
        .caixa_select {
            position: relative;
            margin: 20px 0;
        }

        #opcoes_select {
            border: 1px solid #ccc;
            padding: 10px;
            max-height: 300px;
            background: white;
            margin-top: 10px;
        }
        #opcoes_select.oculto {
            display: none;
        }

        #opcoes_select label {
            display: block;
            margin: 5px 0;
        } 
    </style>
</head>
<body>
    <header>
        <?php include("../../include/navbar.php");?>
    </header>

    <h2>Cadastrar Novo Setor</h2>

    <div id="mensagem"></div>

    <form id="form-setor">
        <label>Nome do Setor:</label><br>
        <input type="text" 
               id="nome_setor" 
               name="nome_setor" 
               placeholder="Digite o nome do setor"
               required>
        <br><br>

        <label>Selecionar Usuários:</label><br>
        <div class="select-box">
        <button class="btn-ativar" type="button" onclick="ativar_select()">
            <span id="contador">0</span> selecionados
        </button>

        <select id="filtro-jornada">Jornada de trabalho</select>

        <div id="opcoes_select" class="oculto">
            <?php while ($u = $usuarios->fetch_assoc()): ?>
                <label>
                    <input type="checkbox" name="usuarios[]" 
                    value="<?= $u['id_usuario'] ?>" onchange="atualizar_contador()">
                    <?= htmlspecialchars($u['nome_usuario']) ?>
                </label>
                <?php endwhile; ?>
            </div>
        </div>
        
        <br>
        <button id="cadastrar" type="submit">Cadastrar</button>
        <a href="setores.php">Voltar</a>
    </form>
    
    <script>
        const select = document.getElementById("filtro-jornada") 
        
        async function exibicao_usuarios_option() {
            select.innerHTML = `<option value="">Selecione uma jornada</option>`
            const coleta_jornada = await fetch("../../api/api_jornada.php?acao=get_tempo");
            const resposta = await coleta_jornada.json();
            const resultado = resposta.dados;
            resultado.forEach(t => {
                const tag_option = document.createElement("option");
                tag_option.value = t.id_tempo;
                tag_option.textContent = `${t.descricao} | ${t.tempo_jornada} : ${t.max_hora_extra} `  ;
                select.appendChild(tag_option);
            })
        }

        exibicao_usuarios_option();        
        
        select.addEventListener("change", async function () {
            let id_tempo = this.value;

        // Cadastrar novo setor via API
        document.getElementById('cadastrar').addEventListener('click', async (e) => {
            e.preventDefault();

            const nomeSetor = document.getElementById('nome_setor').value.trim();
            
            if (!nomeSetor) {
                mostrar_mensagem('O nome do setor é obrigatório!', 'erro');
                return;
            }

            const checkboxes = document.querySelectorAll('input[name="usuarios[]"]:checked');
            const usuariosSelecionados = Array.from(checkboxes).map(cb => parseInt(cb.value));

            const dados = {
                nome_setor: nomeSetor,
                usuarios_selecionado: usuariosSelecionados,
                id_tempo: id_tempo
            };

            try {
                const response = await fetch(`../../api/api_setores.php?acao=cadastrar_setor`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const resultado = await response.json();

                if (resultado.dados.sucesso) {
                    mostrar_mensagem('Setor cadastrado com sucesso!', 'sucesso');
                    
                    // Limpar formulário
                    document.getElementById('nome_setor').value = '';
                    document.querySelectorAll('input[name="usuarios[]"]').forEach(cb => cb.checked = false);
                    atualizar_contador();
                
                    window.location.href = 'setores.php';
                } else {
                    mostrar_mensagem('Erro: ' + resultado.dados.mensagem, 'erro');
                }
            } catch (error) {
                console.error('Erro ao cadastrar:', error);
                mostrar_mensagem('Erro ao cadastrar setor', 'erro');
            }
        });
        });

        // Função para abrir/fechar select
        function ativar_select() {
            const caixa = document.getElementById('opcoes_select');
            caixa.classList.toggle('oculto');
        };

        // Atualizar contador de selecionados
        function atualizar_contador() {
            const checkboxes = document.querySelectorAll('input[name="usuarios[]"]:checked');
            document.getElementById('contador').innerText = checkboxes.length;
        };

        // Mostrar mensagens ao usuário
        function mostrar_mensagem(texto, tipo) {
            const div = document.getElementById('mensagem');
            div.className = 'mensagem ' + tipo;
            div.textContent = texto;
        
        }

    </script>
</body>
</html>