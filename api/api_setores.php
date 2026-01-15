<?php
header("Content-Type: application/json");
require_once '../include/funcoes/funcoes_jornada.php';
include("../BD/conexao.php");

// Pega o método da requisição
$metodo = $_SERVER['REQUEST_METHOD'];

// Processa requisição
if ($metodo === 'GET') {
    $acao = $_GET['acao'];
    
    // NOVA ROTA: Para o gráfico de taxa de presença
    if ($acao === 'get_pessoas_setor') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input['id_setor']) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Parâmetros obrigatórios: id_setor'
            ]);
            exit;
        }
        try {
            $resultado = get_pessoas_setor($conn, $input['id_setor']);
            
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
} elseif ($metodo === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $acao = $_GET['acao'] ?? null;

    $white_list = [
        'set_setor'
    ];
    
    if ($acao) {
        if (in_array($acao, $white_list)) {
            if ($acao === 'set_setor') {

                $usuarios_sel = $input['usuarios_selecionado'];
                $nome_setor = $input['nome_setor'];
                $id_setor = $input['id_setor'];

                if (!$usuarios_sel || !$nome_setor || !$id_setor) {
                    echo json_encode([
                        'sucesso' => false,
                        'mensagem' => 'Parâmetros obrigatórios: usuarios, nome_setor, id_setor'
                    ]);
                    exit;
                }

                try {
                    $sucesso = set_setor(
                        $conn, $usuarios_sel,
                        $nome_setor,
                        $id_setor
                    );
                    echo json_encode([
                        'sucesso' => $sucesso,
                        'mensagem' => $sucesso ? 'setor configurado!' : 'Erro ao configurar o setor'
                    ]);
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