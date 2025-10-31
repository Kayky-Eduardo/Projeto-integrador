<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
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
    <div class="caixa-grafico">
        <div id="piechart_3d" style="width: 900px; height: 500px;"></div>
    </div>
    <div id="resultado-caixa-grafico">
        <table>
            <thead>
                <th>ID ponto</th>
                <th>Email</th>
                <th>Entrada</th>
                <th>Almoço(entrada - saida)</th>
                <th>Saida</th>
                <th>Data</th>
            </thead>
            <tbody id="resposta-tbody">

            </tbody>
        </table>
    </div>
</body>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        // Validar mais tarde
        let dadosDoGrafico = null;
        async function carregar_dados() {
            const valoresJSON = await fetch('../../api/api_relatorio_ponto.php');
            const valores = await valoresJSON.json();
            
            // se algum campo ficar vazio é porque o resultado do campo é igual 0
            dadosDoGrafico = google.visualization.arrayToDataTable([
                ['Task', 'Hours per Day'],
                ['Presentes', valores[0]],
                ['Ausentes',  valores[1]], 
                ['Pausa', valores[2]],
                ['Horario', valores[3]]
            ]);
            drawChart();
        }
        google.charts.load("current", {packages:["corechart"]});
        google.charts.setOnLoadCallback(carregar_dados);
        function drawChart() {
            if (!dadosDoGrafico) {
                return
            }
            var options = {
            title: 'Status Operador',
            pieHole: 0.4,
            };
            var chart = new google.visualization.PieChart(document.getElementById('piechart_3d'));
            // event listener
            google.visualization.events.addListener(chart, 'select', () => {
                // pegando qual grafico foi clidado
                // retorna array
                const selecionado = chart.getSelection();
                
                if (selecionado.length > 0) {
                    // o item selecionado é sempre uma linha (row).
                    // cada fatia é uma row
                    const itemSelecionado = selecionado[0];
                    const indiceLinha = itemSelecionado.row;
                    // pegando o nome do campo e valor atrelado
                    const tipo = dadosDoGrafico.getValue(indiceLinha, 0);
                    async function exibir_tipo(tipo) {
                        const resultado_relatorio = document.getElementById('resposta-tbody')
                        const resposta_api = await fetch(`../../api/api_relatorio_ponto.php?acao=${tipo}`);
                        const resposta = await resposta_api.json();
                        
                        resultado_relatorio.innerHTML = "";

                            resposta.forEach(r => {
                                let id = r.id_ponto ?? '-';
                                let entrada = r.inicio_ponto ?? '-';
                                let almoco_entrada = r.inicio_almoco ?? '-';
                                let almoco_saida = r.fim_almoco ?? '';
                                let saida = r.fim_ponto ?? '-';
                                let data =r.data_ponto ?? '-';
                                const tr = document.createElement("tr");
                                tr.innerHTML = `
                                <td>${id}</td>
                                <td>${r.email_usuario}</td>
                                <td>${entrada}</td>
                                <td>${almoco_entrada} - ${almoco_saida}</td>
                                <td>${saida}</td>
                                <td>${data}</td>
                                `;
                                resultado_relatorio.appendChild(tr);
                            }); 
                    }
                    exibir_tipo(tipo);
                }
            })
            
            chart.draw(dadosDoGrafico, options);
        }
    </script>
</html>