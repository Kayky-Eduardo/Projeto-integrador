<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);


if(isset($_GET['id'])) {
    $id_setor = $_GET['id'];
}

$stmt = $conn->prepare("SELECT * FROM setor WHERE id_setor = ?");
$stmt->bind_param("i", $id_setor);
$stmt->execute();
$setor = $stmt->get_result()->fetch_assoc();

if (!$setor) {
    header("Location: setores.php");
}

// Buscar todos os usuários para o select
$usuarios = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");

$atual = $conn->prepare("
    SELECT tempo_jornada.id_tempo, descricao, jornada, maximo_hora_extra
    FROM tempo_jornada
    JOIN setor ON setor.id_tempo = tempo_jornada.id_tempo
    WHERE setor.id_setor = ?;"
);
$atual->bind_param('i', $id_setor);
$atual->execute();
$jornada_atual = $atual->get_result()->fetch_assoc();  // Nome consistente
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Setor</title>
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

    <h2>Editar Setor</h2>

    <div id="mensagem"></div>

    <form id="form-setor">
        <label>Nome do Setor:</label><br>
        <input type="text" id="nome_setor" data-id="<?= $id_setor ?>"
        value="<?= htmlspecialchars($setor['nome_setor']) ?>" required>
        <br><br>

        <div class="caixa_select">
            <button class="btn-ativar" type="button" onclick="ativar_select()">
                <span id="contador">0</span> selecionados
            </button>

            <select id="filtro-jornada">Jornada de trabalho</select>

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

        <button id="editar" type="submit">Salvar</button>
        <a class="btn-excluir" href="deletar_setor.php?setor=<?= $id_setor ?>">Excluir</a>

        <a href="setores.php">Voltar</a>
        <br>
    </form>

    <script>
        const idSetor = <?= json_encode((int)$id_setor) ?>;
        const jornadaAtualId = <?= $jornada_atual ? json_encode((int)$jornada_atual['id_tempo']) : 'null' ?>;
        let usuariosCarregados = false;

        window.addEventListener('DOMContentLoaded', async () => {
            await carregar_usuarios_setor();
            await exibicao_jornadas();
        });

        
        async function exibicao_jornadas() {
            const select = document.getElementById("filtro-jornada");
            try {
                const coleta_jornada = await fetch("../../api/api_jornada.php?acao=get_tempo");
                const resposta = await coleta_jornada.json();
                const resultado = resposta.dados;
    
                if(resposta.sucesso) {
                    select.innerHTML = `<option value="">selecione uma jornada</option>`;

                    resultado.forEach(t => {
    
                        const option = document.createElement("option");
                        option.value = t.id_tempo;
    
                        const jornada = t.tempo_jornada;
                        const maxExtra = t.max_hora_extra;
    
                        option.textContent = `${t.descricao} | ${t.tempo_jornada} : ${t.max_hora_extra} `  ;
                        
                        if (jornadaAtualId && parseInt(t.id_tempo) === parseInt(jornadaAtualId)) {
                            option.selected = true;
                            console.log('Jornada pré-selecionada:', t.descricao);
                        }
    
                        select.appendChild(option);
                    })
                } else {
                    console.log("erro: " + resultado.mensagem);
                    select.innerHTML = '<option value="">Erro ao carregar jornadas</option>';
                }
            } catch (erro) {
                console.log("erro na aquisição:" + erro);
            }
        }

        
         document.getElementById('form-setor').addEventListener('submit', async (e) => {
            e.preventDefault();

            const nomeSetor = document.getElementById('nome_setor').value.trim();
            const idTempo = document.getElementById('filtro-jornada').value;
            const checkboxes = document.querySelectorAll('input[name="usuarios[]"]:checked');
            const usuariosSelecionados = Array.from(checkboxes).map(cb => parseInt(cb.value));

            if (isNaN(idTempo)) {
                mostrar_mensagem('Jornada inválida!', 'erro');
                return;
            }

            if (!nomeSetor) {
                mostrar_mensagem('O nome do setor é obrigatório!', 'erro');
                return;
            }

            if (!idTempo) {
                mostrar_mensagem('Selecione uma jornada de trabalho!', 'erro');
                return;
            }

            const dados = {
                id_setor: idSetor,
                nome_setor: nomeSetor,
                usuarios_selecionado: usuariosSelecionados,
                id_tempo: parseInt(idTempo)
            };

            console.log('Enviando dados:', dados);

            try {
                const response = await fetch(`../../api/api_setores.php?acao=set_setor`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const resultado = await response.json();
                console.log('Resposta:', resultado);

                if (resultado.sucesso) {
                    mostrar_mensagem('Setor atualizado com sucesso!', 'sucesso');
                    window.location.href = "setores.php"
                } else {
                    mostrar_mensagem('Erro: ' + resultado.mensagem, 'erro');
                }
            } catch (error) {
                console.error('Erro ao salvar:', error);
                mostrar_mensagem('Erro ao salvar alterações', 'erro');
            }
        });

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
                const response = await fetch(`../../api/api_setores.php?acao=get_pessoas_setor`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_setor: idSetor
                    })
                });

                const resultado = await response.json();

                if (resultado.sucesso) {
                    // Marcar os checkboxes dos usuários que já estão no setor
                    const usuariosNoSetor = resultado.dados.map(u => parseInt(u.id_usuario));
                    
                    document.querySelectorAll('input[name="usuarios[]"]').forEach(checkbox => {
                        if (usuariosNoSetor.includes(parseInt(checkbox.value))) {
                            checkbox.checked = true;
                        }
                    });

                    atualizar_contador();
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

        
        // Mostrar mensagens ao usuário de acordo com o tipo proporcionado. exemplo: erro
        function mostrar_mensagem(texto, tipo = null) {
            const div = document.getElementById('mensagem');
            div.className = 'mensagem ' + tipo;
            div.textContent = texto;
        }
    </script>
</body>
</html>