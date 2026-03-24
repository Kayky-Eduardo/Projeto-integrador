<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();
header("Content-Type: application/json");

try {
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        throw new Exception("Este endpoint requer método POST");
    }

    if ($metodo !== 'POST') {
        throw new Exception("Método não permitido: $metodo");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $acao = $_GET['acao'] ?? null;

    if (!$acao) {
        throw new Exception("Parâmetro 'acao' não especificado");
    }

    $white_list = ['set_setor', 'get_pessoas_setor', 'cadastrar_setor', 'get_sem_setor'];

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
        if (!isset($input['id_setor'], $input['usuarios_selecionado'])) {
            throw new Exception("Parâmetros obrigatórios: id_setor e usuarios_selecionado");
        }

        // Buscar dados atuais do setor
        $stmt = $conn->prepare("SELECT nome_setor, id_tempo FROM setor WHERE id_setor = ?");
        $stmt->bind_param("i", $input['id_setor']);
        $stmt->execute();
        $dados = $stmt->get_result()->fetch_assoc();

        set_setor(
            $conn,
            $input['usuarios_selecionado'],
            $dados['nome_setor'],
            $input['id_setor'],
            $dados['id_tempo']
        );

        $resposta = [
            'sucesso' => true,
            'mensagem' => 'Setor atualizado com sucesso!'
        ];
    } elseif ($acao === 'cadastrar_setor') {

        if (!isset($input['nome_setor']) || trim($input['nome_setor']) === '') {
            throw new Exception("Parâmetro obrigatório: nome_setor");
        }

        if (!isset($input['id_tempo'])) {
            throw new Exception("Parâmetro obrigatório: id_tempo");
        }

        $usuarios = $input['usuarios_selecionado'] ?? [];

        $resultado = cadastrar_setor(
            $conn,
            $input['nome_setor'],
            $input['id_tempo'],
            $usuarios
        );

        $resposta = [
            'sucesso' => true,
            'dados' => $resultado
        ];
    } elseif ($acao === 'get_sem_setor') {

        $sql = "
        SELECT u.id_usuario, u.nome_usuario
        FROM usuario u
        LEFT JOIN grupo_setor gs ON gs.id_usuario = u.id_usuario
        WHERE gs.id_usuario IS NULL
        ORDER BY u.nome_usuario
    ";

        $result = $conn->query($sql);

        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }

        $resposta = [
            'sucesso' => true,
            'dados' => $usuarios
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

    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}

exit;
