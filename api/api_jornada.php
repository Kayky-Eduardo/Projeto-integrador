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

    if ($acao === 'get_tempo') {
        try {
            $resultado = get_tempo($conn);
            
            echo json_encode([
                'sucesso' => true,
                'dados' => $resultado
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar Tempos: ' . $e->getMessage()
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
    $input = json_decode(file_get_contents('php://input'), true);

    $white_list = [
        'jornada', 'historico'
    ];
    
    if ($acao) {
        $acao_formatada = strtolower($acao);
        if (in_array($acao_formatada, $white_list)) {
            if ($acao_formatada === 'jornada') {
                if (!$input['jornada'] || $input['hora_extra'] === null) {
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
                        'mensagem' => $sucesso ? 'jornada setada' : 'Erro ao setar a jornada'
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'sucesso' => false,
                        'mensagem' => 'Erro: ' . $e->getMessage()
                    ]);
                }           
            } else if ($acao_formatada === 'historico') {
                if (!$input['inicio'] || !$input['fim'] || !$input['id_usuario'] ) {
                    echo json_encode([
                        'sucesso' => false,
                        'mensagem' => 'Parâmetros obrigatórios: inicio e fim'
                    ]);
                    exit;
                }
                
                try {
                    $dados_historico = get_banco_data($conn, $input['id_usuario'], $input['inicio'], $input['fim']); 
                    
                    if (is_array($dados_historico) && count($dados_historico) > 0) {
                        
                        echo json_encode([
                            'sucesso' => true,
                            'mensagem' => 'Histórico encontrado com sucesso.',
                            'dados' => $dados_historico
                        ]);
                    } else {
                        echo json_encode([
                            'sucesso' => true,
                            'mensagem' => 'Nenhum registro de histórico encontrado para o período.',
                            'dados' => []
                        ]);
                    }
                } catch (Exception $e) {
                    echo json_encode([
                        'sucesso' => false,
                        'mensagem' => 'Erro: ' . $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    
} else {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido'
    ]);
}

$conn->close();
?>