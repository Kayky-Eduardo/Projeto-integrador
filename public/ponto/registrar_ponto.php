<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
$ponto = [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Ponto</title>
</head>
<body>
    <h2>Registro de Ponto</h2>

    <form method="post" action="sql_registrar_ponto.php">
        <!-- <a>Entrada</a>
        <input type="time" name="entrada">
        <a>Saída</a>
        <input type="time" name="saida">
        <a>Almoço Entrada</a>
        <input type="time" name="almoco_entrada">
        <a>Almoço Saída</a>
        <input type="time" name="almoco_saida">
        <a>Observações</a>
        <textarea name="observacao"></textarea>
        <button type="submit" name="registro">Registrar</button> -->

        <label for="entradas">Entrada:</label>

        <select name="entradas" id="entrada">
        <option value="entrada">Expediente</option>
        <option value="almoco_entrada">Almoço</option>
        </select>
    </form>
        <button id="btn">Iniciar</button>

    <script>
        const btn = document.getElementById("btn");
        const select = document.getElementById("entrada");
        let alternado = true;
        var array = {
            entrada: {
                tempo_inicial:`${'tempo_atual'}`,
                tempo_final:`${'tempo_atual'}`,
                registrado: 0
            },
            almoco_entrada: {
                tempo_inicial:`${'tempo_atual'}`,
                tempo_final:`${'tempo_atual'}`,
                registrado: 0
            }
        };

        btn.addEventListener("click", () => {
            alternado = !alternado;
            btn.textContent = alternado ? "Iniciar" : "Sair";
        });

        select.addEventListener("change", () =>{
            if (select.value = 'almoco_entrada'){
                if (array.almoco_entrada.registrado < 2){
                    btn.textContent = alternado ? "Iniciar" : "Sair";
                }
                
            }
            
        })
    </script>

    <?php
    if (empty($_POST['entrada']) || empty($_POST['saida'])) {
        echo "Preencha todos os campos obrigatórios.";
        exit;
    }
    if (isset($_POST['registro'])) {
        echo "Registrado em: " . date('d-m-Y H:i:s');
    }
    ?>
</body>
</html>