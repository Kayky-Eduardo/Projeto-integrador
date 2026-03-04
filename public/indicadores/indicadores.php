<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
include "../../include/navbar.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório ponto</title>
    <!-- <link rel="stylesheet" href="../../assets/css/estilo.css"> -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

</head>
<body>
    <dialog>
        <div class="filtrar-relatorio-grafico">
            <input type="date" id="data-filtro-relatorio-grafico">
        </div>
    </dialog>
    <section class="caixa-grafico">
        <h2>Relatório diário</h2>
        <article id="piechart_3d" style="width: 900px; height: 500px;"></article>
    </section>

    <section class="caixa-grafico">
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

    <div id="resultado-caixa-grafico">
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
    <!-- Perfil de usuario geral(com filtro), porém irei fazer um para o profissional ver o próprio -->
        <hr>
        <h3>Perfil de usuario(ADM)</h3>
        <!--
        select para mostrar os usuarios, o valor das options vai ser o id, e para o usuario vai aparecer
        o nome do usuário
         -->
        <select id="filtro-usuarios">Usuarios</select>
        <!-- ficar embaixo do filtro -->
        <div>
            <table>
                <thead>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Inicio</th>
                    <th>Saida</th>
                    <th>Tempo trabalhado</th>
                    <th>Tempo logado</th>
                </thead>
                <tbody id="filtro-usuarios-tabela">
                    <tr>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="exibicao-hora-extra"></div>
    </div>
    <div class="container">        
        <div class="formulario">
            <div class="form-group">
                <label for="usuarioId">ID do Usuário:</label>
                <input type="number" id="usuarioId" value="1" min="1">
            </div>
            
            <div class="form-group">
                <label for="dataInicio">Data Início:</label>
                <input type="date" id="dataInicio">
            </div>
            
            <div class="form-group">
                <label for="dataFim">Data Fim:</label>
                <input type="date" id="dataFim">
            </div>
            
            <button id="btnVerificar">
                Verificar Jornada
            </button>
        </div>
        
        <div id="loading" class="loading" style="display: none;">
            Carregando...
        </div>
        
        <div id="error" class="error" style="display: none;"></div>
        
        <div id="resultado"></div>
        <div id="detalhes"></div>
    </div>
</body>
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

        async function exibicao_usuarios_option() {
            select.innerHTML = `<option value="">Selecione um usuario</option>`
            await fetch("../../api/api_relatorio_ponto.php?acao=usuarios")
            .then((coleta_usuarios) => coleta_usuarios.json())
            .then((resposta_usuarios) => {
                resposta_usuarios.forEach(u => {
                    const tag_option = document.createElement("option");
                    tag_option.value = u.id_usuario;
                    tag_option.textContent = u.nome_usuario;
                    select.appendChild(tag_option);
                })
            });
        }
        
        select.addEventListener("change", async function () {
            const exibicao_hora_extra = document.getElementById("exibicao-hora-extra");

            exibicao_hora_extra.innerHTML = "";

            const id_usuario = this.value;

            await fetch("../../api/api_relatorio_ponto.php?acao=get_horas",
            {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id_usuario })
            })
            .then((coleta_hora_extra) => coleta_hora_extra.json())
            .then((resposta) => {
                const tag_h1 = document.createElement("h1");
                const tag_h2 = document.createElement("h2");
    
                tag_h1.textContent = "Hora Extra";
    
                // Usa direto o valor retornado pela API
                tag_h2.textContent = resposta.dados.saldo_formatado ?? "00:00";
    
                exibicao_hora_extra.appendChild(tag_h1);
                exibicao_hora_extra.appendChild(tag_h2);
    
                filtrar_tabela_hora(id_usuario);
            });

        });
        exibicao_usuarios_option();

        async function verificar_jornada(usuarioId, dataInicio, dataFim) {
            try {
                const params = new URLSearchParams({
                    usuario_id: usuarioId,
                    data_inicio: dataInicio,
                    data_fim: dataFim
                });
                
                await fetch(`../../api/api_jornada.php?${params}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then((response) => response.json())
                .then((resultado) => {
                    if (!resultado.sucesso) {
                        throw new Error(resultado.mensagem);
                    }
                    
                    return resultado.dados;
                });        
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

    function exibir_resultado(resultado, elementoId) {
        const elemento = document.getElementById(elementoId);
        
        if (!elemento) {
            console.log('Elemento não encontrado:', elementoId);
            return;
        }
        
        const simboloDiferenca = resultado.diferenca >= 0 ? '+' : '';
        
        elemento.innerHTML = `
            <section class="jornada-resultado">
                <h3>Verificação de Jornada</h3>
                <article class="jornada-info">
                    <p><strong>Usuário ID:</strong> ${resultado.usuario_id}</p>
                </article>
                
                <section class="jornada-metricas">
                    <article class="metrica">
                        <span class="label">Horas Trabalhadas:</span>
                        <span class="valor">${formatarHoras(resultado.horas_trabalhadas)}</span>
                    </article>
                    <article class="metrica">
                        <span class="label">Horas Esperadas:</span>
                        <span class="valor">${formatarHoras(resultado.horas_esperadas)}</span>
                    </article>
                    <article class="metrica">
                        <span class="label">Diferença:</span>
                        <span class="valor">${simboloDiferenca}${formatarHoras(resultado.diferenca)}</span>
                    </article>
                    <article class="metrica">
                        <span class="label">Cumprimento:</span>
                        <span class="valor">${formatarPercentual(resultado.percentual)}</span>
                    </article>
                </section>
            </section>
        `;
    }

    function exibir_detalhes(resultado, elementoId) {
    const elemento = document.getElementById(elementoId);
    
    if (!elemento) {
        console.log('Elemento não encontrado:', elementoId);
        return;
    }
    
    let html = '<section class="jornada-detalhes">';
    
    // horas trabalhados
    if (resultado.detalhes_trabalhados && resultado.detalhes_trabalhados.length > 0) {
        html += '<h4>horas Trabalhados</h4>';
        html += '<table class="tabela-detalhes">';
        html += '<thead><tr><th>Data</th><th>Horas</th></tr></thead><tbody>';
        
        resultado.detalhes_trabalhados.forEach(dia => {
            html +=
             `<tr>
                <td>${dia.data}</td>
                <td>${formatarHoras(dia.horas)}</td>
            </tr>`;
        });
        
        html += '</tbody></table>';
    }
    
    // horas esperados
    if (resultado.detalhes_esperados && resultado.detalhes_esperados.length > 0) {
        html += '<h4>horas Esperados</h4>';
        html += '<table class="tabela-detalhes">';
        html += '<thead><tr><th>Data</th><th>Dia da Semana</th><th>Horas</th></tr></thead><tbody>';
        
        resultado.detalhes_esperados.forEach(dia => {
            html += `<tr>
                <td>${dia.data}</td>
                <td>${dia.dia_semana}</td>
                <td>${formatarHoras(dia.horas)}</td>
            </tr>`;
        });
        
        html += '</tbody></table>';
    }
    
    html += '</section>';
    
    elemento.innerHTML = html;
    }

    async function buscar_jornada() {
        const usuarioId = document.getElementById('usuarioId').value;
        const dataInicio = document.getElementById('dataInicio').value;
        const dataFim = document.getElementById('dataFim').value;
        
        if (!usuarioId || !dataInicio || !dataFim) {
            console.log('Preencha todos os campos!');
            return;
        }
            document.getElementById('loading').style.display = 'block';
            document.getElementById('error').style.display = 'none';
            limpar_resultado();
            
        try {
            const resultado = await verificar_jornada(usuarioId, dataInicio, dataFim);
            
            exibir_resultado(resultado, 'resultado');
            
        } catch (error) {
            console.log('Erro ao buscar dados: ' + error.message);
        } finally {
            document.getElementById('loading').style.display = 'none';
        }
    }
    
    document.getElementById("btnVerificar").addEventListener("click", function () {
        buscar_jornada();
    })
    
    function limpar_resultado() {
        document.getElementById('resultado').innerHTML = '';
        document.getElementById('detalhes').innerHTML = '';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    buscar_jornada();
                }
            });
        });
    });

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
                    ['Funcionários', 'Saldo (Horas)', { role: 'style' }] // esta 3° coluna serve para definir qual vai ser a cor 
                ];
                
                if (resultado.sucesso) {
                    const usuarios = resultado.dados.usuarios;
                    usuarios.forEach(usuario => {
                        const horas = parseFloat((usuario.saldo_horas).toFixed(0)); 
                        
                        // trocando a cor de acordo com o valor se é + ou -
                        let corBarra = '#3b82f6';
                        if (horas < 0) {
                            corBarra = '#f59e0b'; 
                        } 
                        
                        // inserindo os dados no grafico
                        dadosGrafico.push([
                            usuario.nome_usuario,
                            horas,
                            corBarra 
                        ]);
                    });
                } else {
                    console.error('Erro ao buscar dados:', resultado.mensagem);
                    dadosGrafico.push(['Sem dados', 0, '#9ca3af']);
                    return;
                }
                
                var data = google.visualization.arrayToDataTable(dadosGrafico);
                
                var options = {
                    title: 'Banco de Horas - ' + resultado.dados.periodo.inicio + ' até ' + resultado.dados.periodo.fim,
                    subtitle: 'Saldo de horas dos funcionários',
                    
                    // texto de baixo
                    hAxis: {
                        title: 'Funcionários',
                    },
    
                    // texto do lado
                    vAxis: {
                        title: 'Horas'
                    }
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
                if (!resultado.sucesso) {
                    console.error('Erro ao buscar evolução:', resultado.mensagem);
                    return;
                }
                
                const dados = resultado.dados.dados;
                let primeiraData = resultado.dados.primeira_data;
                let ultimaData = resultado.dados.ultima_data;
                
    
                primeiraData = formatarData(primeiraData, true);
                ultimaData = formatarData(ultimaData, true);
    
                const dadosGrafico = [['Data', 'Presentes', 'Ausentes']];
                
                const [ano, mes, dia] = resultado.dados.periodo.fim.split('-');
    
                dados.forEach(dia => {
                    const dataFormatada = formatarData(dia.data);
                    dadosGrafico.push([dataFormatada, dia.presentes, dia.ausentes]);
                });
                
                const data = google.visualization.arrayToDataTable(dadosGrafico);
                
                const options = {
                    title: `Evolução de Presença - ${primeiraData} até ${ultimaData} no mês de ${nome_do_mes(mes)}`,
                    legend: { position: 'bottom' },
                    colors: ['#10b981', '#ef4444'],
                    areaOpacity: 0.050,
                    hAxis: {
                        title: 'Data',
                    },
                    vAxis: {
                        title: 'Quantidade',
                    },
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
</html>