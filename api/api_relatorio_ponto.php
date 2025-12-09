<?php
include(__DIR__ . "/../BD/conexao.php");
require_once '../include/funcoes/funcoes_relatorio_ponto.php';
require_once '../include/funcoes/funcoes_jornada.php';

header("Content-Type: application/json");


// pegando o tipo e entregando o resultado
$acao = $_GET['acao'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

$white_list = [
    'ausentes', 'pausa', 'horario',
    'presentes', 'usuarios', 'filtrar_usuario',
    'filtrar_tabela_hora', 'get_logados', 'deslogar',
    'verificar_jornada'
];

if ($acao) {
    $acao_formatada = strtolower($acao);

    if (in_array($acao_formatada, $white_list)) {
        if ($acao_formatada === 'usuarios') {
            echo json_encode(coleta_usuarios($conn));
            exit;
        } else if ($acao_formatada === 'filtrar_usuario') {
            echo json_encode(filtrar_usuario($conn, $input['id_usuario'] ?? null));
            exit;
        } else if ($acao_formatada === 'filtrar_tabela_hora') {
            echo json_encode(relatorio_ponto_filtrado($conn, $input['id_usuario']));
            exit;
        } else if ($acao_formatada === 'get_logados') {
            echo json_encode(get_logados($conn));
            exit;
        } else if ($acao_formatada === 'deslogar') {
            echo json_encode(deslogar_usuario($conn, $input['id_login'] ?? 0));
            exit;
        //} else if ($acao_formatada === 'verificar_jornada') {
            // echo json_encode(verificar_jornada($conn, $usuario_id, $data_inicio, $data_fim));
           // exit;
        } else {
            echo json_encode(filtrar($conn, $acao_formatada));
            exit;
        }
    }
}
// padrão entregar dados
echo json_encode(dados_grafico($conn));
exit;
?>