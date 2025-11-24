<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
include "../../include/navbar.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório ponto</title>
    <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>
    <table>
        <thead>
            <th></th>
            <th>ID login</th>
            <th>Email</th>
            <th>Entrada</th>
            <th>Tempo logado</th>
        </thead>
        <tbody id="resposta-tbody">
            <tr>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
    <!-- Perfil de usuario geral(com filtro), porém irei fazer um para o profissional ver o próprio -->
    <hr>
</body>
<script>
    async function get_logados() {
    // pegando a tabela
    const resultado_relatorio = document.getElementById('resposta-tbody')
    
    // esperando a resposta em json da api
    const resposta_api = await fetch(`../../api/api_relatorio_ponto.php?acao=get_logados`);
    
    // transformando a array em json para array normal
    const resposta = await resposta_api.json();
    
    // esvaziando a tabela
    resultado_relatorio.innerHTML = "";

    // exibindo o resultado
    resposta.forEach(r => {
        let id = r.id_login ?? '-';
        let email = r.email_login ?? '-';
        let entrada = r.data_inicio ?? '-';
        // if (r.tempo_logado > 60) {
        //     const hora = Math.floor(r.tempo_logado / 60);
        //     const minutos = r.tempo_logado % 60;
        //     let tempo_logado = hora > 0 ? `${hora}h ${minutos}min` : `${minutos}min`;
        // } else {
        // }
        let tempo_logado = `${r.tempo_logado} Minutos` ?? '-';
        const tr = document.createElement("tr");
        
        tr.innerHTML = `
            <td><button onclick="deslogar()">Deslogar</button>
            <td>${id}</td>
            <td>${r.email_usuario}</td>
            <td>${entrada}</td>
            <td>${tempo_logado}</td>
        `;
        resultado_relatorio.appendChild(tr);
        });
        async function deslogar(id_usuario) {
        }
    }
    get_logados();
</script>
</html>