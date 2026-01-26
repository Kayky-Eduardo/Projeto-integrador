<?php
header("Content-Type: application/json");
require_once '../include/funcoes/funcoes_jornada.php';
require_once '../include/funcoes/funcoes_banco_horas.php';
include("../BD/conexao.php");


// Pega o método da requisição
$metodo = $_SERVER['REQUEST_METHOD'];

// Processa requisição
if ($metodo === 'GET') {
    $acao = $_GET['acao'] ?? 'verificar_individual';

    // NOVA ROTA: Para o gráfico de taxa de presença
    if ($acao === 'taxa_presenca_geral') {
        try {
            $resultado = verificar_jornada_todos_usuarios($conn);

            echo json_encode([
                'sucesso' => true,
                'dados' => $resultado
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar taxa de presença: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    // GET: Verificar jornada
    $usuario_id = $_GET['usuario_id'] ?? null;
    $data_inicio = $_GET['data_inicio'] ?? null;
    $data_fim = $_GET['data_fim'] ?? null;

    if (!$usuario_id || !$data_inicio || !$data_fim) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Parâmetros obrigatórios: usuario_id, data_inicio, data_fim'
        ]);
        exit;
    }

    try {
        $resultado = verificar_jornada($conn, $usuario_id, $data_inicio, $data_fim);

        echo json_encode([
            'sucesso' => true,
            'dados' => $resultado
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao verificar jornada: ' . $e->getMessage()
        ]);
    }
} elseif ($metodo === 'POST') {

    $acao = $_GET['acao'] ?? null;

    if (!$acao) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Ação não informada'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $white_list = [
        'jornada',
        'historico'
    ];

    $acao_formatada = strtolower($acao);

    if (!in_array($acao_formatada, $white_list)) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Ação inválida'
        ]);
        exit;
    }

    if ($acao_formatada === 'jornada') {
        if (empty($input['jornada']) || !isset($input['hora_extra'])) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Parâmetros obrigatórios: jornada e hora extra'
            ]);
            exit;
        }

        try {
            $sucesso = set_jornada($conn, $input['jornada'], $input['hora_extra']);

            echo json_encode([
                'sucesso' => $sucesso,
                'mensagem' => $sucesso ? 'Jornada setada com sucesso' : 'Erro ao setar a jornada'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro: ' . $e->getMessage()
            ]);
        }
    }

    if ($acao_formatada === 'historico') {
        if (
            empty($input['inicio']) ||
            empty($input['fim']) ||
            empty($input['id_usuario'])
        ) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Parâmetros obrigatórios: inicio, fim e id_usuario'
            ]);
            exit;
        }

        try {
            $dados_historico = get_banco_data(
                $conn,
                $input['id_usuario'],
                $input['inicio'],
                $input['fim']
            );

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Consulta realizada com sucesso',
                'dados' => $dados_historico ?? []
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro: ' . $e->getMessage()
            ]);
        }
    }
} else {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Ação inválida'
    ]);
    exit;
}

$conn->close();
