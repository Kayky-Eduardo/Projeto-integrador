    <?php
    session_start();
    header('Content-Type: application/json');

    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['mostrar' => false]);
        exit;
    }

    require '../../BD/conexao.php';
    require '../../include/verificacao.php';

    $id_usuario = $_SESSION['id_usuario'];
    $resultado = verificar_tempo_por_ponto($conn, $id_usuario);

    if ($resultado['tipo'] === 'aviso') {
        $segundos_restantes = $dados['segundos_maximos'] - $dados["segundos_trabalhados_efetivos"];

        echo json_encode([
            'mostrar' => true,
            'mensagem' => 'Sua jornada está terminando',
            'segundos' => $segundos_restantes
        ]);
    } else {
        if (!isset($_SESSION['id_usuario'])) {
            http_response_code(401);
            echo json_encode(['erro' => 'nao_autenticado']);
            exit;
        }    
    }

    ?>