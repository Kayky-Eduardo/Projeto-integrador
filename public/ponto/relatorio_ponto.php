<?php
session_start();
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
    <dialog>
        <div class="filtrar-relatorio-grafico">
            <input type="date" id="data-filtro-relatorio-grafico">
        </div>
    </dialog>
    <div class="caixa-grafico">
        <div id="piechart_3d" style="width: 900px; height: 500px;"></div>
    </div>
    <div id="resultado-caixa-grafico">
        <table>
            <thead>
                <th>ID ponto</th>
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
                    <th>Tempo logado</th>
                </thead>
                <tbody id="filtro-usuarios-tabela">
                    <tr>
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
            
            <button id="btnVerificar" onclick="buscarJornada()">
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
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    // Validar mais tarde
        let dadosDoGrafico = null;
        async function carregar_dados() {
            const valoresJSON = await fetch('../../api/api_relatorio_ponto.php');
            const valores = await valoresJSON.json();
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
                    ['Task', 'Hours per Day'],
                    ['Presentes', valores[0]],
                    ['Ausentes',  valores[1]], 
                    ['Pausa', valores[2]],
                    ['Horario', valores[3]]
                ]);
                drawChart();
            }
        }
        google.charts.load("current", {packages:["corechart"]});
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
                        // pegando a tabela
                        const resultado_relatorio = document.getElementById('resposta-tbody')
                        
                        // esperando a resposta em json da api
                        const resposta_api = await fetch(`../../api/api_relatorio_ponto.php?acao=${tipo}`);
                        
                        // transformando a array em json para array normal
                        const resposta = await resposta_api.json();
                        
                        // esvaziando a tabela
                        resultado_relatorio.innerHTML = "";

                        // exibindo o resultado
                        resposta.forEach(r => {
                            let id = r.id_ponto ?? '-';
                            let entrada = r.inicio_ponto ?? '-';
                            let saida = r.fim_ponto ?? '-';
                            let data = r.data_ponto ?? '-';
                            let tempo_logado = '-';

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
                    }
                    exibir_tipo(tipo);
                }
            })
            chart.draw(dadosDoGrafico, options);
        }
        // coleta de dados horas extras usando o filtro para id_usuario
        const select = document.getElementById("filtro-usuarios");
        
        async function filtrar_tabela_hora(id_usuario) {
            const exibicao_tabela_hora = document.getElementById("filtro-usuarios-tabela");
                    const response = await fetch("../../api/api_relatorio_ponto.php?acao=filtrar_tabela_hora", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({id_usuario: id_usuario})
                });

                const tabela_hora = await response.json();
                exibicao_tabela_hora.innerHTML = "";

                if(tabela_hora.length === 0){
                    exibicao_tabela_hora.innerHTML = `<tr><td colspan="5">Nenhum registro encontrado</td></tr>`;
                    return;
                }

                tabela_hora.forEach(h => {
                    let id_login = h.id_login ?? '-';
                    let email_login = h.email_login ?? '-';
                    let entrada = h.data_inicio ?? '-';                
                    let saida = h.data_fim ?? '-';
                    let tempo = h.tempo_logado ?? '-';

                    if(tempo !== '-') {
                        const hora = Math.floor(tempo / 60);
                        const minutos = tempo % 60;
                        tempo = hora > 0 ? `${hora}h ${minutos}min` : `${minutos}min`;
                    }

                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${id_login}</td>
                        <td>${email_login}</td>
                        <td>${entrada}</td>
                        <td>${saida}</td>
                        <td>${tempo}</td>
                    `;
                    exibicao_tabela_hora.appendChild(tr);
                });
        }

        async function exibicao_usuarios() {
            select.innerHTML = `<option value="">Selecione um usuario</option>`
            const coleta_usuarios = await fetch("../../api/api_relatorio_ponto.php?acao=usuarios");
            const resposta_usuarios = await coleta_usuarios.json();
            // console.log(resposta_usuarios);
            resposta_usuarios.forEach(u => {
                const tag_option = document.createElement("option");
                tag_option.value = u.id_usuario;
                tag_option.textContent = u.nome_usuario;
                select.appendChild(tag_option);
            })
        }

        select.addEventListener("change", async function() {
            const exibicao_hora_extra = document.getElementById("exibicao-hora-extra");
            const tag_h1_hora_extra = document.createElement("h1")
            const tag_h2_hora_extra = document.createElement("h2")
         
            exibicao_hora_extra.textContent = "";

            let id_usuario = this.value;
         
            const coleta_hora_extra = await fetch("../../api/api_relatorio_ponto.php?acao=filtrar_usuario", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({id_usuario})
            })
            const resposta_hora_extra = await coleta_hora_extra.json();
            let hora = resposta_hora_extra;
            let min = 0;
            tag_h1_hora_extra.textContent = "Hora Extra";
            
            if (resposta_hora_extra >= 60) {
                hora = Math.floor(resposta_hora_extra / 60);
                min = resposta_hora_extra % 60;
            }
            
            tag_h2_hora_extra.textContent = `${hora} horas e ${min.toString().padStart(2,'0')} minutos`;

            exibicao_hora_extra.appendChild(tag_h1_hora_extra);
            exibicao_hora_extra.appendChild(tag_h2_hora_extra);
            filtrar_tabela_hora(id_usuario)
        })
        exibicao_usuarios();

        async function verificarJornada(usuarioId, dataInicio, dataFim) {
            try {
                const params = new URLSearchParams({
                    usuario_id: usuarioId,
                    data_inicio: dataInicio,
                    data_fim: dataFim
                });
                
                const response = await fetch(`../../api/api_jornada.php?${params}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const resultado = await response.json();
                
                if (!resultado.sucesso) {
                    throw new Error(resultado.mensagem);
                }
                
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

    function exibirResultado(resultado, elementoId) {
        const elemento = document.getElementById(elementoId);
        
        if (!elemento) {
            console.log('Elemento não encontrado:', elementoId);
            return;
        }
        
        const simboloDiferenca = resultado.diferenca >= 0 ? '+' : '';
        
        elemento.innerHTML = `
            <div class="jornada-resultado">
                <h3>Verificação de Jornada</h3>
                <div class="jornada-info">
                    <p><strong>Usuário ID:</strong> ${resultado.usuario_id}</p>
                </div>
                
                <div class="jornada-metricas">
                    <div class="metrica">
                        <span class="label">Horas Trabalhadas:</span>
                        <span class="valor">${formatarHoras(resultado.horas_trabalhadas)}</span>
                    </div>
                    <div class="metrica">
                        <span class="label">Horas Esperadas:</span>
                        <span class="valor">${formatarHoras(resultado.horas_esperadas)}</span>
                    </div>
                    <div class="metrica">
                        <span class="label">Diferença:</span>
                        <span class="valor">${simboloDiferenca}${formatarHoras(resultado.diferenca)}</span>
                    </div>
                    <div class="metrica">
                        <span class="label">Cumprimento:</span>
                        <span class="valor">${formatarPercentual(resultado.percentual)}</span>
                    </div>
                </div>
            
            </div>
        `;
    }

    function exibirDetalhes(resultado, elementoId) {
    const elemento = document.getElementById(elementoId);
    
    if (!elemento) {
        console.log('Elemento não encontrado:', elementoId);
        return;
    }
    
    let html = '<div class="jornada-detalhes">';
    
    // Dias trabalhados
    if (resultado.detalhes_trabalhados && resultado.detalhes_trabalhados.length > 0) {
        html += '<h4>Dias Trabalhados</h4>';
        html += '<table class="tabela-detalhes">';
        html += '<thead><tr><th>Data</th><th>Horas</th></tr></thead><tbody>';
        
        resultado.detalhes_trabalhados.forEach(dia => {
            html += `<tr>
                <td>${dia.data}</td>
                <td>${formatarHoras(dia.horas)}</td>
            </tr>`;
        });
        
        html += '</tbody></table>';
    }
    
    // Dias esperados
    if (resultado.detalhes_esperados && resultado.detalhes_esperados.length > 0) {
        html += '<h4>Dias Esperados</h4>';
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
    
    html += '</div>';
    
    elemento.innerHTML = html;
}
    async function buscarJornada() {
            const usuarioId = document.getElementById('usuarioId').value;
            const dataInicio = document.getElementById('dataInicio').value;
            const dataFim = document.getElementById('dataFim').value;
            
            // Validações
            if (!usuarioId || !dataInicio || !dataFim) {
                alert('Preencha todos os campos!');
                return;
            }
                document.getElementById('loading').style.display = 'block';
                document.getElementById('error').style.display = 'none';
                limparResultados();
                
            try {
                // Chama a API
                const resultado = await verificarJornada(usuarioId, dataInicio, dataFim);
                
                // Exibe resultados
                exibirResultado(resultado, 'resultado');
                
            } catch (error) {
                console.log('Erro ao buscar dados: ' + error.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
        
        function limparResultados() {
            document.getElementById('resultado').innerHTML = '';
            document.getElementById('detalhes').innerHTML = '';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        buscarJornada();
                    }
                });
            });
        });
    </script>
</html>