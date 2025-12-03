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

function verificar_tempo_logado($conn, $id_usuario, $id_login) {
    // Busca tudo de uma vez: jornada, hora extra máxima e tempo logado
    $stmt = $conn->prepare("
        SELECT 
            TIME_TO_SEC(tempo_jornada.jornada) as segundos_jornada,
            TIME_TO_SEC(ADDTIME(tempo_jornada.jornada, tempo_jornada.maximo_hora_extra)) as segundos_maximos,
            TIMESTAMPDIFF(SECOND, login.data_inicio, NOW()) as segundos_logado
        FROM tempo_jornada
        CROSS JOIN login
        WHERE login.id_login = ? 
        AND login.id_usuario = ?
        AND login.data_fim IS NULL
        LIMIT 1
    ");
    
    $stmt->bind_param("ii", $id_login, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return 0;
    }
    $dados = $result->fetch_assoc();
    $stmt->close();
    
    $minutos_extra = 0;

    if ($dados['segundos_logado'] >= $dados['segundos_jornada']) {
        $segundos_extra = $dados['segundos_logado'] - $dados['segundos_jornada'];
        if ($segundos_extra > 0) {
            $minutos_extra = round($segundos_extra / 60);
        
            return $minutos_extra;
        }
    }

    if ($dados['segundos_logado'] < $dados['segundos_jornada']) {
        $segundos_faltantes = $dados['segundos_logado'] - $dados['segundos_jornada'];
        if ($segundos_faltantes < 0) {
            $minutos_faltantes = round($segundos_faltantes / 60);

            return $minutos_faltantes;
        }
    }
    // // Verifica se ultrapassou o tempo máximo
    // if ($dados['segundos_logado'] >= $dados['segundos_maximos']) {
        
    //     // 1. Desloga no banco
    //     $stmt_update = $conn->prepare("
    //     UPDATE login SET data_fim = NOW() WHERE id_login = ?
    //     ");
    //     $stmt_update->bind_param("i", $id_login);
    //     $stmt_update->execute();
    //     $stmt_update->close();
        
    //     // 2. Calcula hora extra (se houver)
    //     $segundos_extra = $dados['segundos_logado'] - $dados['segundos_jornada'];
        
    //     if ($segundos_extra > 0) {
    //         $minutos_extra = round($segundos_extra / 60);
            
    //         // $stmt_hora_extra = $conn->prepare("
    //         //     INSERT INTO horas_extras (id_usuario, data, tipo, minutos) 
    //         //     VALUES (?, CURDATE(), 'dia_he', ?)
    //         // ");
    //         // $stmt_hora_extra->bind_param("ii", $id_usuario, $minutos_extra);
    //         // $stmt_hora_extra->execute();
    //         // $stmt_hora_extra->close();
        
    //         return $minutos_extra;
    //     }
    //     // header("Location: /Projeto-integrador/public/logout.php");
    //     // exit;
    //     return $minutos_extra;
    // }
    return $minutos_extra;
}
?>