<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// função para verificar se o login do usuário é válido

function verificar_login($conn) {
    $id_login = $_SESSION['id_login'];
    $id_usuario = $_SESSION['id_usuario'];

    if (!isset($id_login) || !isset($id_usuario)) {
        header("Location: /Projeto-integrador/public/logout.php");
        exit;
    }
    $stmt = $conn->prepare("
        SELECT data_fim FROM login
        WHERE id_login = ? AND id_usuario = ? LIMIT 1
    ");
    $stmt->bind_param("ii", $id_login, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        header("Location: /Projeto-integrador/public/logout.php");
        exit;
    }

    $row = $result->fetch_assoc();
    if (!is_null($row['data_fim'])) {
        header("Location: /Projeto-integrador/public/logout.php");
        exit;
    }
    
    verificar_tempo_logado($conn, $id_usuario, $id_login);
}

function verificar_tempo_logado($conn, $usuario_id, $id_login) {
    // Busca configuração e tempo logado em uma única query
    $stmt = $conn->prepare("
    SELECT 
        TIME_TO_SEC(ADDTIME(tempo_jornada.jornada, tempo_jornada.maximo_hora_extra)) as segundos_maximos,
        TIMESTAMPDIFF(SECOND, login.data_inicio, NOW()) as segundos_logado
    FROM tempo_jornada
    CROSS JOIN login
    WHERE login.id_login = ? 
    AND login.id_usuario = ?
    AND login.data_fim IS NULL
    LIMIT 1
    ");
    
    $stmt->bind_param("ii", $id_login, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Se não encontrar configuração ou login, não faz nada
    if ($result->num_rows === 0) {
        $stmt->close();
        return;
    }
    
    $dados = $result->fetch_assoc();
    $stmt->close();
    
    // Verifica se ultrapassou o tempo máximo
    if ($dados['segundos_logado'] >= $dados['segundos_maximos']) {
        // Desloga
        $stmt_update = $conn->prepare("UPDATE login SET data_fim = NOW() WHERE id_login = ?");
        $stmt_update->bind_param("i", $id_login);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Redireciona
        header("Location: /Projeto-integrador/public/logout.php");
        exit;
    }
}
?>