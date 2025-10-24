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
</head>
<body>
    <div id="piechart_3d" style="width: 900px; height: 500px;"></div>
</body>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        // Validar mais tarde
        let dadosDoGrafico = null;
        async function carregar_dados() {
            const valoresJSON = await fetch('../../api/api_relatorio_ponto.php');
            const valores = await valoresJSON.json();
            
            dadosDoGrafico = google.visualization.arrayToDataTable([
                ['Task', 'Hours per Day'],
                ['Presentes', valores[0]],
                ['Ausentes',  valores[1]],
                ['Almoço',  2],
                ['Pausa', 2],
                ['Horário concluido',    7]
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
            title: 'My Daily Activities',
            pieHole: 0.4,
            };

            var chart = new google.visualization.PieChart(document.getElementById('piechart_3d'));
            chart.draw(dadosDoGrafico, options);
        }
        
      
    </script>
</html>