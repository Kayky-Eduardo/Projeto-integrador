<?php
session_start();
include("../BD/conexao.php");
include("../include/funcoes/funcoes_banco_horas.php");
include("../include/verificacao.php");
verificar_login($conn);

$dados = get_banco_horas($conn, $_SESSION['id_usuario']);

function entregar_dados($dados) {
    if (isset($dados['saldo_antigo'])) {
        $saldo_antigo = $dados['saldo_antigo'];
    } else {
        $saldo_antigo = "00:00";
    }

    $saldo_formatado = $dados['saldo_formatado'] ?? 0;
    $ultima_atualizacao = $dados['ultima_atualizacao'] ?? "";

    echo "
    <article>
        <h2>$saldo_antigo</h1>
        <p>Saldo anterior</p>
    </article>
    ";

    echo "
    <article>
        <h2>$saldo_formatado</h1>
        <p>Saldo atual</p>
    </article>
    ";

    echo "
    <article>
        <h2>$ultima_atualizacao</h1>
        <p>Ultima atualização</p>
    </article>
    ";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu banco</title>
</head>
<body>
    
    <header>
        <?php include("../include/navbar.php");?>
        <h1>Sistema de RH</h1>        
    </header>

    
    <h1>Meu banco</h1>
    <?php entregar_dados($dados) ?>
    
    <div class="set-tempo">
        <div class="formulario">
            <div class="form-group">
                <label for="set-inicio" >inicio: </label>
                <input id="set-inicio" type="date">
            </div>
            <br>
            <div class="form-group">
                <label for="set-fim">fim: </label>
                <input id="set-fim" type="date">
            </div>
            <br>
            <button onclick="setTempo(<?php echo $_SESSION['id_usuario']; ?>)">Aplicar</button>
        </div>
    </div>
    <div id="resposta"></p>

    <script>
        async function setTempo(id_usuario) {
            const inicio = document.getElementById('set-inicio').value;
            const fim = document.getElementById('set-fim').value;
            const resposta = document.getElementById('resposta');

            if (!inicio || !fim) {
                console.log("Por favor, preencha o inicio e fim.");
                return;
            }
            try {
                const response = await fetch(`../api/api_jornada.php?acao=historico`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_usuario: id_usuario,
                        inicio: inicio,
                        fim: fim
                    })
                });

                const resposta_json = await response.json();
                const resposta_historico = resposta_json.dados;

                if (!resposta_json.sucesso) {
                    resposta.innerHTML = `<p>Erro: ${resposta_json.mensagem}</p>`;
                    return;
                }
                resposta.innerHTML = `
                <table>
                    <thead>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Saldo anterior</th>
                        <th>Saldo</th>
                    </thead>
                    <tbody id="resposta-tbody">
                    </tbody>
                </table>
                `;

                const exibicao_resultado = document.getElementById('resposta-tbody');

                if (resposta_historico.length === 0) {
                    exibicao_resultado.innerHTML = `<tr><td colspan="4">${resposta_objeto.mensagem}</td></tr>`;
                    return;
                }



                resposta_historico.forEach(r => {
                    let id = r.id_historico ?? '-';
                    let data = r.data ?? '-';
                    let saldo_anterior = r.saldo_anterior ?? '-';
                    let saldo_novo = r.saldo_novo?? '-';

                    const tr = document.createElement("tr");
                    
                    tr.innerHTML = `
                        <td>${id}</td>
                        <td>${data}</td>
                        <td>${saldo_anterior}</td>
                        <td>${saldo_novo}</td>
                    `;

                   exibicao_resultado.appendChild(tr);
                }); 


            } catch (error) {
                console.log("Problema ao realizar ação" + error);
                resposta.textContent = "Erro ao procurar!";
            }
        }
    </script>
</body>
</html>