<?php
/*
Minha ideia nessa api é pegar as informações para exibir os avisos com pnotify
E guardar no localStorage para exibir as informações com consistência.
Para isso vou ter que: calcular os tempos exatos de ponto/pausa e informar de acordo

Pegar o tempo em pausa e o tempo restante para ela terminar
select
	tempo_min,
    tempo_max,
    ABS(tempo_max - tempo_min) AS tempo_diferenca,
	TIMESTAMPDIFF(MINUTE, inicio, now()) as tempo_em_pausa,
    ABS(TIMESTAMPDIFF(MINUTE, inicio, now()) - tempo_max) as tempo_restante
from pausa
join pausa_config on pausa.id_config = pausa_config.id_config
where pausa.id_usuario = 1
and fim is null
and data = curdate();


Existe uma função para calcular o tempo em pausa, tanto pausas fechadas quanto atuais
*/

header("Content-Type: application/json");
require_once '../include/verificacao.php';
require("../BD/conexao.php");


// Pega o método da requisição
$metodo = $_SERVER['REQUEST_METHOD'];

// Processa requisição
if ($metodo === 'POST') {
    $acao = $_GET['acao'] ?? 'aviso';
    $input = json_decode(file_get_contents('php://input'), true);

    if ($acao === 'aviso') {
        try {
            $resultado = coleta_dado_aviso($conn, $input['id_usuario']);
            if ($resultado) {
                echo json_encode([
                    'sucesso' => true,
                    'dados' => $resultado
                ]);
            } else {
                echo json_encode([
                    'sucesso' => true,
                    'dados' => "nenhuma informação coletada!"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar taxa de presença: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}
?>