<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório ponto</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <?php include "../../include/link.html" ?>

</head>
<nav><?php include "../../include/navbar.php"; ?></nav>

<main class="main-center">
    <section class="pagina-padrao">
        <body>
        <dialog>
            <div class="filtrar-relatorio-grafico">
                <input type="date" id="data-filtro-relatorio-grafico">
            </div>
        </dialog>

        <section class="caixa-grafico">
            <h2>Relatório diário</h2>
            <article id="piechart_3d" style="width: 900px; height: 500px;"></article>

            <h2>Taxa de presença</h2>
            <article id="columnchart_material" style="width: 800px; height: 500px;"></article>
        </section>

        <section class="caixa-grafico">
            <h2>Hora extra</h2>
            <article id="columnchart_material2" style="width: 800px; height: 500px;"></article>
        </section>

        <section class="caixa-grafico">
            <h2>Evolução de Presença</h2>
        <article id="linechart_presenca" style="width: 900px; height: 500px;"></article>
        </section>

        <section class="tabela-padrao" id="resultado-caixa-grafico">
            <table>
                <thead>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Entrada</th>
                    <th>Saida</th>
                    <th>Data</th>
                    <th>Tempo logado</th>
                </thead>
                <tbody id="resposta-tbody">
                    <tr>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>           
        </section>

        <section id="exibicao-hora-extra"></section>
        </section>
    </section>
</main>
    <script type="text/javascript">
        // Validar mais tarde
            let dadosDoGrafico = null;
            async function carregar_dados() {
                await fetch('../../api/api_relatorio_ponto.php')
                .then((valoresJSON) => valoresJSON.json())
                .then((valores) => {
                    let contador = 0;
                    for (let i=0; i<valores.length; i++) {
                        if (valores[i] == 0) {
                            contador++;
                        }
                    }
                    if (contador == 4) {
                    dadosDoGrafico = google.visualization.arrayToDataTable([
                        ['Task', 'Hours per Day'],
                        ['Sem pontos', 1],
                    ]);    
                    } else {
                        // se algum campo ficar vazio é porque o resultado do campo é igual 0
                        dadosDoGrafico = google.visualization.arrayToDataTable([
                            ['status', 'Pessoas por Status'],
                            ['Presentes', valores[0]],
                            ['Ausentes',  valores[1]], 
                            ['Pausa', valores[2]],
                            ['Horario', valores[3]]
                        ]);
                    }
                    drawChart();
                })
            }

            setInterval(carregar_dados, 5000);
            
            google.charts.load("current", {packages:['corechart', 'bar', 'line']});
            google.charts.setOnLoadCallback(carregar_dados);

            // função para dar forma ao gráfico.
            // fiz uma gambiarra para exibir os resultado na tabela
            function drawChart() {
                if (!dadosDoGrafico) {
                    return
                }
                var options = {
                title: 'Status Operador(diario)',
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
                            window.location.replace('/projeto-integrador/public/indicadores/indicadores.php#resultado-caixa-grafico')
                            // pegando a tabela
                            const resultado_relatorio = document.getElementById('resposta-tbody')
                            
                            const resposta_api = await fetch(`../../api/api_relatorio_ponto.php?acao=${tipo}`)
                            .then((respostaJSON) => respostaJSON.json())
                            .then((resposta) => {
                                // esvaziando a tabela
                                resultado_relatorio.innerHTML = "";

                                // exibindo o resultado
                                resposta.forEach(r => {
                                    let id = r.id_ponto ?? '-';
                                    let entrada = r.inicio_ponto ?? '-';
                                    let saida = r.fim_ponto ?? '-';
                                    let data = r.data_ponto ?? '-';
                                    let tempo_logado = '-';

                                    if (tipo === 'Pausa') {
                                        entrada = r.inicio;
                                        saida = r.fim ?? '-';
                                    }
                                    if (r.tempo_logado) {
                                        const horas = Math.floor(r.tempo_logado / 60);
                                        const minutos = r.tempo_logado % 60;
                                        tempo_logado = horas > 0 ? `${horas}h ${minutos}min` : `${minutos}min`;
                                    }

                                    const tr = document.createElement("tr");
                                    
                                    tr.innerHTML = `
                                        <td>${id}</td>
                                        <td>${r.email_usuario}</td>
                                        <td>${entrada}</td>
                                        <td>${saida}</td>
                                        <td>${data}</td>
                                        <td>${tempo_logado}</td>
                                    `;
                                    resultado_relatorio.appendChild(tr);
                                    }); 
                                })
                        }
                        exibir_tipo(tipo);
                    }
                })
                chart.draw(dadosDoGrafico, options);
            }
            // coleta de dados horas extras usando o filtro para id_usuario
            const select = document.getElementById("filtro-usuarios");
            
            function formatar_tempo(tempo) {
                if (tempo != '-') {
                    const hora = Math.floor(tempo / 60);
                    const minutos = tempo % 60;
                    return tempo = hora > 0 ? `${hora}h ${minutos}min` : `${minutos}min`;
                } else {
                    return "-"
                }
            } 

            async function filtrar_tabela_hora(id_usuario) {
                const exibicao_tabela_hora = document.getElementById("filtro-usuarios-tabela");
                await fetch("../../api/api_relatorio_ponto.php?acao=filtrar_tabela_hora", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({id_usuario: id_usuario})
                })
                .then((response) => response.json())
                .then((tabela_hora) => {

                    exibicao_tabela_hora.innerHTML = "";
        
                    if(tabela_hora.length === 0){
                        exibicao_tabela_hora.innerHTML = `<tr><td colspan="5">Nenhum registro encontrado</td></tr>`;
                        return;
                    }
        
                    tabela_hora.forEach(h => {
                        let id_ponto = h.id_ponto ?? '-';
                        let email_login = h.email_login ?? '-';
                        let entrada = h.inicio_ponto ?? '-';                
                        let saida = h.fim_ponto ?? '-';
                        let tempo_logado = h.tempo_logado ?? '-';
                        let tempo_trabalhado = h.tempo_trabalhado ?? '-';
        
                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                            <td>${id_ponto}</td>
                            <td>${email_login}</td>
                            <td>${entrada}</td>
                            <td>${saida}</td>
                            <td>${formatar_tempo(tempo_trabalhado)}</td>
                            <td>${formatar_tempo(tempo_logado)}</td>
                        `;
                        exibicao_tabela_hora.appendChild(tr);
                    });
                })
            }

            async function verificar_jornada(usuarioId, dataInicio, dataFim) {
                try {
                    const params = new URLSearchParams({
                        usuario_id: usuarioId,
                        data_inicio: dataInicio,
                        data_fim: dataFim
                    });
                    
                    const response = await fetch(`../../api/api_jornada.php?${params}`);
                    const resultado = await response.json();

                    if (!resultado.sucesso) throw new Error(resultado.mensagem);
                    
                    return resultado.dados;   
            
                } catch (error) {
                console.log('Erro ao verificar jornada:', error);
                throw error;
            }
        }

        // Formata horas para exibição
        function formatarHoras(horas) {
            return horas.toFixed(2).replace('.', ',') + 'h';
        }

        function formatarPercentual(percentual) {
            return percentual.toFixed(2).replace('.', ',') + '%';
        }
        
        function limpar_resultado() {
            document.getElementById('resultado').innerHTML = '';
            document.getElementById('detalhes').innerHTML = '';
        }

        async function carregar_grafico_bar1() {
            try {
                const response = await fetch('../../api/api_jornada.php?acao=taxa_presenca_geral')
                .then((response) => response.json())
                .then((resultado) => {
                    // Cabeçalho do gráfico
                    const dadosGrafico = [
                        [
                            'Funcionário',  
                            'Horas Trabalhadas', 
                            'Horas Esperadas', 
                            'Taxa de Presença (%)'
                        ]
                    ];

                    if (!resultado.sucesso) {
                        console.error('Erro ao buscar dados:', resultado.mensagem);
                        dadosGrafico.push(['Sem dados', 0, 0, 0]);
                        return;
                    }

                    const usuarios = resultado.dados.usuarios;
                    
                    usuarios.forEach(usuario => {
                        dadosGrafico.push([
                            usuario.nome,
                            parseFloat(usuario.horas_trabalhadas),
                            parseFloat(usuario.horas_esperadas),
                            parseFloat(usuario.taxa_presenca) // porcentagem
                        ]);
                    });            
                    
                    var data = google.visualization.arrayToDataTable(dadosGrafico);
        
                    var options = {
                        title: 'Taxa de Presença — ' + resultado.dados.periodo.inicio + ' até ' + resultado.dados.periodo.fim,
                        hAxis: { title: 'Funcionários' },
                        vAxes: {
                            0: { title: 'Horas' }, // esquerda
                            1: { title: 'Taxa de Presença (%)' } // direita
                        },
                        seriesType: 'bars',
                        series: { 2: { type: 'line', targetAxisIndex: 1 } },
                        colors: ['#3b82f6', '#10b981', '#f43f5e'],
                        legend: { position: 'bottom' }
                    };
        
                    var chart = new google.visualization.ComboChart(
                        document.getElementById('columnchart_material')
                    );
        
                    chart.draw(data, options);
                });            
            } catch (error) {
                console.error('Erro ao carregar gráfico:', error);
            }
        }

        google.charts.setOnLoadCallback(carregar_grafico_bar1);

        async function carregar_grafico_bar2() {
            try {
                await fetch("../../api/api_relatorio_ponto.php?acao=filtrar_usuario")
                .then((response => response.json()))
                .then((resultado) => {
                    const dadosGrafico = [
                        ['Funcionários', 'Saldo (Horas)', { role: 'style' }]
                    ];

                    if (!resultado.sucesso || !resultado.dados || resultado.dados.usuarios.length === 0) {
                        // Fallback simbólico
                        dadosGrafico.push(['Sem dados', 0, '#9ca3af']);
                    } else {
                        resultado.dados.usuarios.forEach(usuario => {
                            const horas = parseFloat((usuario.saldo_horas).toFixed(0));
                            const corBarra = horas < 0 ? '#f59e0b' : '#3b82f6';
                            dadosGrafico.push([usuario.nome_usuario, horas, corBarra]);
                        });
                    }

                    const periodoInicio = resultado.dados?.periodo?.inicio ?? '-';
                    const periodoFim = resultado.dados?.periodo?.fim ?? '-';

                    var data = google.visualization.arrayToDataTable(dadosGrafico);
                    var options = {
                        title: 'Banco de Horas - ' + periodoInicio + ' até ' + periodoFim,
                        hAxis: { title: 'Funcionários' },
                        vAxis: { title: 'Horas' }
                    };

                    var chart = new google.visualization.ColumnChart(document.getElementById('columnchart_material2'));
                    chart.draw(data, options);
                })
            } catch (error) {
                console.error('Erro ao carregar gráfico:', error);
            }
        }

        google.charts.setOnLoadCallback(carregar_grafico_bar2);

        function nome_do_mes(numero) {
            const meses = [
                "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
                "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
            ];

            return meses[numero - 1];
        }
        
        function formatarData(data, apenasDia = false) {
            if (!data) return '';
            const [ano, mes, dia] = data.split('-');
            return apenasDia ? dia : `${dia}/${mes}`
        };

        async function carregar_grafico_linha() {
            try {
                await fetch('../../api/api_relatorio_ponto.php?acao=evolucao_presenca')
                .then((response) => response.json())
                .then((resultado) => {
                    const dadosGrafico = [['Data', 'Presentes', 'Ausentes']];

                    if (!resultado.sucesso || !resultado.dados.dados || resultado.dados.dados.length === 0) {
                        dadosGrafico.push(['--', 0, 0]);

                        const data = google.visualization.arrayToDataTable(dadosGrafico);
                        const options = {
                            title: 'Evolução de Presença - Sem dados no período',
                            legend: { position: 'bottom' },
                            colors: ['#10b981', '#ef4444'],
                            hAxis: { title: 'Data' },
                            vAxis: { title: 'Quantidade', minValue: 0, maxValue: 1 },
                            pointSize: 4,
                        };
                        const chart = new google.visualization.AreaChart(document.getElementById('linechart_presenca'));
                        chart.draw(data, options);
                        return;
                    }

                    let primeiraData = formatarData(resultado.dados.primeira_data, true);
                    let ultimaData = formatarData(resultado.dados.ultima_data, true);

                    // Renomeado de 'dia' para 'diaPeriodo' para evitar conflito com o forEach abaixo
                    const [ano, mes, diaPeriodo] = resultado.dados.periodo.fim.split('-');

                    resultado.dados.dados.forEach(item => { // renomeado de 'dia' para 'item'
                        dadosGrafico.push([formatarData(item.data), item.presentes, item.ausentes]);
                    });

                    const data = google.visualization.arrayToDataTable(dadosGrafico);
                    const options = {
                        title: `Evolução de Presença - ${primeiraData} até ${ultimaData} no mês de ${nome_do_mes(mes)}`,
                        legend: { position: 'bottom' },
                        colors: ['#10b981', '#ef4444'],
                        areaOpacity: 0.050,
                        hAxis: { title: 'Data' },
                        vAxis: { title: 'Quantidade' },
                        pointSize: 4,
                    };

                    const chart = new google.visualization.AreaChart(document.getElementById('linechart_presenca'));
                    chart.draw(data, options);
                });
            } catch (error) {
                console.error('Erro ao carregar gráfico:', error);
            }
        }
        google.charts.setOnLoadCallback(carregar_grafico_linha);
        </script>
</body>
</html>