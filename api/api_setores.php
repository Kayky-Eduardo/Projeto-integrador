<?php
// Configuração de erros (não exibir na tela, apenas logar)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Iniciar buffer de saída para capturar qualquer output indesejado
ob_start();

// Header DEVE ser enviado antes de qualquer output
header("Content-Type: application/json");

try {
    // Verificar método da requisição
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    if ($metodo === 'GET') {
        throw new Exception("Este endpoint requer método POST");
    }
    
    if ($metodo !== 'POST') {
        throw new Exception("Método não permitido: $metodo");
    }
    
    // Ler e decodificar input JSON
    $input = json_decode(file_get_contents('php://input'), true);

    
    // Obter ação da query string
    $acao = $_GET['acao'] ?? null;
    
    if (!$acao) {
        throw new Exception("Parâmetro 'acao' não especificado");
    }
    
    // Lista de ações permitidas
    $white_list = ['set_setor', 'get_pessoas_setor', 'cadastrar_setor'];
    
    if (!in_array($acao, $white_list)) {
        throw new Exception("Ação não permitida: $acao");
    }
    
    // Incluir arquivos necessários (após validações iniciais)
    require_once '../include/funcoes/funcoes_jornada.php';
    include("../BD/conexao.php");
    
    // Processar ações
    if ($acao === 'get_pessoas_setor') {
        
        if (empty($input['id_setor'])) {
            throw new Exception("Parâmetro obrigatório: id_setor");
        }
        
        $resultado = get_pessoas_setor($conn, $input['id_setor']);
        
        $resposta = [
            'sucesso' => true,
            'dados' => $resultado
        ];
        
    } elseif ($acao === 'set_setor') {
        
        // Validar parâmetros obrigatórios
        if (!isset($input['id_setor'])) {
            throw new Exception("Parâmetro obrigatório: id_setor");
        }
        
        if (!isset($input['nome_setor']) || trim($input['nome_setor']) === '') {
            throw new Exception("Parâmetro obrigatório: nome_setor");
        }
        
        if (!isset($input['usuarios_selecionado'])) {
            throw new Exception("Parâmetro obrigatório: usuarios_selecionado");
        }
        
        // Executar função
        set_setor(
            $conn,
            $input['usuarios_selecionado'],
            $input['nome_setor'],
            $input['id_setor']
        );
        
        $resposta = [
            'sucesso' => true,
            'mensagem' => 'Setor atualizado com sucesso!'
        ];
    } elseif ($acao === 'cadastrar_setor') {        
        if (!isset($input['nome_setor']) || trim($input['nome_setor']) === '') {
            throw new Exception("Parâmetro obrigatório: nome_setor");
        }
        
        if (!isset($input['usuarios_selecionado'])) {
            throw new Exception("Parâmetro obrigatório: usuarios_selecionado");
        }
        
        // Executar função
        $resultado = cadastrar_setor(
            $conn,
            $input['nome_setor'],
            $input['usuarios_selecionado'],
        );
        
        $resposta = [
            'sucesso' => true,
            'dados' => $resultado
        ];
    } 
    
    // Limpar buffer de saída antes de enviar JSON
    ob_clean();
    
    // Enviar resposta
    echo json_encode($resposta);
    
    // Fechar conexão
    if (isset($conn)) {
        $conn->close();
    }
    
} catch (Exception $e) {
    // Em caso de erro, limpar buffer e enviar erro como JSON
    ob_clean();
    
    // Log do erro (opcional, para debug)
    error_log("API Error: " . $e->getMessage());
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}

exit;
?>