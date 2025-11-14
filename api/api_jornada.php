<?php
header("Content-Type: application/json");
require_once '../include/funcoes_jornada.php';
include("../BD/conexao.php");


// Pega o método da requisição
$method = $_SERVER['REQUEST_METHOD'];

// Processa requisição
if ($method === 'GET') {
    // GET: Verificar jornada
    $usuarioId = $_GET['usuario_id'] ?? null;
    $dataInicio = $_GET['data_inicio'] ?? null;
    $dataFim = $_GET['data_fim'] ?? null;
    
    if (!$usuarioId || !$dataInicio || !$dataFim) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Parâmetros obrigatórios: usuario_id, data_inicio, data_fim'
        ]);
        exit;
    }
    
    try {
        $resultado = verificarJornada($conn, $usuarioId, $dataInicio, $dataFim);
        
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
    
} elseif ($method === 'POST') {
    // POST: Atualizar assiduidade
    $input = json_decode(file_get_contents('php://input'), true);
    
    $usuarioId = $input['usuario_id'] ?? null;
    $percentual = $input['percentual'] ?? null;
    
    if (!$usuarioId || $percentual === null) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Parâmetros obrigatórios: usuario_id, percentual'
        ]);
        exit;
    }
    
    // try {
    //     $sucesso = atualizarAssiduidade($conn, $usuarioId, $percentual);
        
    //     echo json_encode([
    //         'sucesso' => $sucesso,
    //         'mensagem' => $sucesso ? 'Assiduidade atualizada com sucesso' : 'Erro ao atualizar'
    //     ]);
    // } catch (Exception $e) {
    //     echo json_encode([
    //         'sucesso' => false,
    //         'mensagem' => 'Erro: ' . $e->getMessage()
    //     ]);
    // }
    
} else {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido'
    ]);
}

$conn->close();
?>