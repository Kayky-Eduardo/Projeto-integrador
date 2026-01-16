<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$usuarios = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastro de setor</title>
    <link rel="stylesheet" href="../../assets/estilo.css">
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

    <h1>Cadastro de setor</h1>

    <form action="" method="POST">
        <label>Nome:</label><br>
        <input type="text" value="" required><br><br>

        <label>plano de horário:</label><br>
        <input type="text" value="" required><br><br>

        <Label>Pessoas:</Label>
        
        <div class="caixa_select">
            <button class="btn-ativar" type="button" onclick="ativar_select()">
                <span id="contador">0</span> selecionados
            </button>

            <div id="opcoes_select" class="oculto">
                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <label>
                        <input type="checkbox" 
                               name="usuarios[]" 
                               value="<?= $u['id_usuario'] ?>"
                               onchange="atualizar_contador()">
                        <?= htmlspecialchars($u['nome_usuario']) ?>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>
        <button type="submit">Cadastrar</button>
    </form>

    <br>
    <a href="setores.php">Voltar</a>

</body>
    <script>
        const coleta_usuarios = await fetch("../../api/api_relatorio_ponto.php?acao=usuarios");
        const resposta_usuarios = await coleta_usuarios.json();
        resposta_usuarios.forEach(u => {
            const tag_option = document.createElement("option");
            tag_option.textContent = u.nome_usuario;
            select.appendChild(tag_option);
        })

        window.addEventListener('DOMContentLoaded', async () => {
            await carregar_usuarios_setor();
        });

        const idSetor = <?= $id_setor ?>;
        let usuariosCarregados = false;

        // Carregar usuários do setor ao clicar no botão
        async function ativar_select() {
            const caixa = document.getElementById('opcoes_select');
            
            // Toggle visibilidade usando classe
            const estaOculto = caixa.classList.toggle('oculto');

            // Carregar usuários apenas na primeira vez E quando abrir
            if (!usuariosCarregados && !estaOculto) {
                await carregar_usuarios_setor();
                usuariosCarregados = true;
            }
        }

        // Buscar pessoas que já estão no setor via API
        async function carregar_usuarios_setor() {
            try {
                const coleta_usuarios = await fetch("../../api/api_relatorio_ponto.php?acao=usuarios");
                const resultado = await coleta_usuarios.json();

                if (resultado.sucesso) {
                    const usuariosNoSetor = resultado.dados.map(u => parseInt(u.id_usuario));
    
                } else {
                    mostrar_mensagem('Erro ao carregar usuários: ' + resultado.mensagem, 'erro');
                }
            } catch (error) {
                console.error('Erro ao carregar usuários:', error);
                mostrar_mensagem('Erro ao carregar usuários do setor', 'erro');
            }
        }

        // Atualizar contador de selecionados
        function atualizar_contador() {
            const checkboxes = document.querySelectorAll('input[name="usuarios[]"]:checked');
            document.getElementById('contador').innerText = checkboxes.length;
        }

        // Salvar alterações via API
        document.getElementById('form-setor').addEventListener('submit', async (e) => {
            e.preventDefault();

            const nomeSetor = document.getElementById('nome_setor').value;
            const checkboxes = document.querySelectorAll('input[name="usuarios[]"]:checked');
            const usuariosSelecionados = Array.from(checkboxes).map(cb => parseInt(cb.value));

            const dados = {
                id_setor: idSetor,
                nome_setor: nomeSetor,
                usuarios_selecionado: usuariosSelecionados
            };

            try {
                const response = await fetch(`../../api/api_setores.php?acao=set_setor`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const resultado = await response.json();

                if (resultado.sucesso) {

                    mostrar_mensagem('Setor atualizado com sucesso!', 'sucesso');
                
                } else {
                    mostrar_mensagem('Erro: ' + resultado.mensagem, 'erro');
                }
            } catch (error) {
                console.error('Erro ao salvar:', error);
                mostrar_mensagem('Erro ao salvar alterações', 'erro');
            }
        });

        // Mostrar mensagens ao usuário
        function mostrar_mensagem(texto, tipo) {
            const div = document.getElementById('mensagem');
            div.className = 'mensagem ' + tipo;
            div.textContent = texto;
            
            // Remover mensagem após 5 segundos
            setTimeout(() => {
                div.className = '';
                div.textContent = '';
            }, 5000);
        }
    </script>
</html>