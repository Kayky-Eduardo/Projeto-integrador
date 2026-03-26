<?php
// Salve como api/api_jornada_debug.php e teste com ele primeiro

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar na tela, só logar
ini_set('log_errors', 1);

// Header DEVE ser a primeira coisa
header("Content-Type: application/json");

// Tentar capturar qualquer output indesejado
ob_start();

try {
    // Log básico
    error_log("=== INICIO API DEBUG ===");
    error_log("Método: " . $_SERVER['REQUEST_METHOD']);
    error_log("GET: " . print_r($_GET, true));
    
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método não é POST");
    }
    
    // Ler input
    $inputRaw = file_get_contents('php://input');
    error_log("Body raw: " . $inputRaw);
    
    if (empty($inputRaw)) {
        throw new Exception("Body vazio");
    }
    
    $input = json_decode($inputRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
    }
    
    error_log("Input parseado: " . print_r($input, true));
    
    // Verificar ação
    $acao = $_GET['acao'] ?? null;
    error_log("Ação: " . $acao);
    
    if (!$acao) {
        throw new Exception("Ação não especificada");
    }
    
    // Processar ações
    if ($acao === 'get_pessoas_setor') {
        error_log("Processando get_pessoas_setor");
        
        if (empty($input['id_setor'])) {
            throw new Exception("id_setor não fornecido");
        }
        
        // AQUI: incluir conexão e função apenas se necessário
        require_once '../include/funcoes/funcoes_jornada.php';
        include("../BD/conexao.php");
        
        $resultado = get_pessoas_setor($conn, $input['id_setor']);
        
        $response = [
            'sucesso' => true,
            'dados' => $resultado
        ];
        
    } elseif ($acao === 'set_setor') {
        error_log("Processando set_setor");
        
        // Validar parâmetros
        if (!isset($input['id_setor'])) {
            throw new Exception("id_setor não fornecido");
        }
        if (!isset($input['nome_setor'])) {
            throw new Exception("nome_setor não fornecido");
        }
        if (!isset($input['usuarios_selecionado'])) {
            throw new Exception("usuarios_selecionado não fornecido");
        }
        
        error_log("Parâmetros validados OK");
        
        // AQUI: incluir conexão e função
        require_once '../include/funcoes/funcoes_jornada.php';
        include("../BD/conexao.php");
        
        error_log("Arquivos incluídos OK");
        
        set_setor(
            $conn, 
            $input['usuarios_selecionado'], 
            $input['nome_setor'], 
            $input['id_setor']
        );
        
        error_log("Função set_setor executada OK");
        
        $response = [
            'sucesso' => true,
            'mensagem' => 'Setor configurado com sucesso!'
        ];
        
    } else {
        throw new Exception("Ação desconhecida: $acao");
    }
    
    // Limpar qualquer output buffer antes de enviar JSON
    ob_clean();
    
    error_log("Enviando resposta: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("ERRO: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Limpar buffer
    ob_clean();
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

error_log("=== FIM API DEBUG ===");
exit;
?>