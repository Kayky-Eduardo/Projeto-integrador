<?php
header("Content-Type: application/json");
require_once '../include/funcoes/funcoes_jornada.php';
include("../BD/conexao.php");


// Pega o método da requisição
$method = $_SERVER['REQUEST_METHOD'];

// Processa requisição
if ($method === 'GET') {
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
    
} elseif ($method === 'POST') {
    // POST: Atualizar assiduidade
    $input = json_decode(file_get_contents('php://input'), true);
    
    $jornada = $input['jornada'] ?? null;
    $hora_extra = $input['hora_extra'] ?? null;
    
    if (!$jornada || $hora_extra === null) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Parâmetros obrigatórios: jornada e hora extra'
        ]);
        exit;
    }
    
    try {
        $sucesso = set_jornada($conn, $jornada, $hora_extra);
        
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
    
} else {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido'
    ]);
}

$conn->close();
?>