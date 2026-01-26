<?php
require "funcoes/funcoes_banco_horas.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function verificar_login($conn) {
    $id_login = $_SESSION['id_login'];
    $id_usuario = $_SESSION['id_usuario'];

    if (!isset($id_login) || !isset($id_usuario)) {
        header("Location: /projeto-integrador/public/logout.php");
        exit;
    }
    $stmt = $conn->prepare("
        SELECT data_fim FROM login
        WHERE id_login = ? AND id_usuario = ? LIMIT 1
    ");
    $stmt->bind_param("ii", $id_login, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ativo = $conn->prepare("
        SELECT conta_ativa
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ");

    $ativo->bind_param("i", $id_usuario);
    $ativo->execute();
    $result_ativo = $ativo->get_result();
    
    if ($result->num_rows === 0 || $result_ativo->num_rows === 0) {
        header("Location: /projeto-integrador/public/logout.php");
        exit;
    }
    
    $resposta = $result_ativo->fetch_assoc();
    $row = $result->fetch_assoc();
    if ($row['data_fim'] !== null || $resposta['conta_ativa'] == 0) {
        header("Location: /projeto-integrador/public/logout.php");
        exit;
    }
    
    $resultado_tempo = verificar_tempo_por_ponto($conn, $id_usuario);

    verificar_tipo($conn, $id_usuario, $resultado_tempo);
}

function redirecionar() {
    header("Location: /projeto-integrador/public/logout.php");
}

function verificar_tipo($conn, $id_usuario, $resultado_tempo) {
    $tipo = $resultado_tempo['tipo'];
    $tempo = $resultado_tempo['resultado'];

    if ($tipo === "bloqueado") {
        fechar_pontos_pendentes($conn, $id_usuario);
        redirecionar();

    } else if ($tipo === "excedido") {
        adicionar_horas($conn, $id_usuario, $tempo);
        fechar_pontos_pendentes($conn, $id_usuario);
        redirecionar();
    
    } else if ($tipo === "tempo_extra") {
        adicionar_horas($conn, $id_usuario, $tempo);
        fechar_pontos_pendentes($conn, $id_usuario);
        redirecionar();

    } else if ($tipo === "tempo_faltante") {
        retirar_horas($conn, $id_usuario, $tempo);
        fechar_pontos_pendentes($conn, $id_usuario);
        redirecionar();
    }
}

function verificar_tempo_logado($conn, $id_usuario, $id_login) {
    $verificar_historico_ponto = $conn->prepare("
        SELECT id_ponto
        FROM ponto_dia
        WHERE data_ponto = CURDATE() AND fim_ponto IS NOT NULL
        AND id_usuario = ?;
    ");
    $verificar_historico_ponto->bind_param("i", $id_usuario);
    $verificar_historico_ponto->execute();
    $resultado = $verificar_historico_ponto->get_result();

    if ($resultado->num_rows > 0) {
        $id_ponto = $resultado->fetch_assoc()['id_ponto'];

        return [
            'tipo' => 'bloqueado',
            'resultado' => 0
        ];
    }

    // Busca tudo de uma vez: jornada, hora extra máxima e tempo logado
    // Por login
    $stmt = $conn->prepare("
    SELECT 
        TIME_TO_SEC(tempo_jornada.maximo_hora_extra) as hora_extra,
        TIME_TO_SEC(tempo_jornada.jornada) AS segundos_jornada,
        TIME_TO_SEC(ADDTIME(tempo_jornada.jornada, tempo_jornada.maximo_hora_extra)) AS segundos_maximos,
        TIMESTAMPDIFF(SECOND, login.data_inicio, NOW()) AS segundos_logado
    FROM login
    JOIN grupo_setor
        ON grupo_setor.id_usuario = login.id_usuario
    JOIN setor
		ON setor.id_setor = grupo_setor.id_setor
    JOIN tempo_jornada
        ON tempo_jornada.id_tempo = setor.id_tempo
    WHERE login.id_login = ?
        AND login.id_usuario = ?
        AND login.data_fim IS NULL
    LIMIT 1;
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

    if ($dados['segundos_logado'] >= $dados['segundos_maximos']) {
        $minutos = (int) $dados['hora_extra'];
        return [
            "tipo" => "excedido",
            "resultado" => $minutos,
        ];
    }

    if ($dados['segundos_logado'] >= $dados['segundos_jornada']) {
        $segundos_extra = $dados['segundos_logado'] - $dados['segundos_jornada'];
        if ($segundos_extra > 0) {
            $minutos = round($segundos_extra / 60);
        
            return [
                "tipo" => 'tempo_extra',
                "resultado" => $minutos,
            ];
        }
    }
    
    if ($dados['segundos_logado'] < $dados['segundos_jornada']) {
        $segundos_faltantes = $dados['segundos_logado'] - $dados['segundos_jornada'];
        if ($segundos_faltantes < 0) {
            $minutos = round($segundos_faltantes / 60);

            return [
                "tipo" => "tempo_faltante",
                "resultado" => $minutos,
            ];
        }
    }
    return $minutos_extra;
}

function fechar_pontos_pendentes($conn, $id_usuario) {
    $verificar_pausa = $conn->prepare("
        SELECT id_pausa
        FROM pausa
        WHERE id_usuario = ? AND fim IS NULL
        LIMIT 1;
    ");
    $verificar_pausa->bind_param("i", $id_usuario);
    $verificar_pausa->execute();
    $resultado_pausa = $verificar_pausa->get_result();

    $verificar_ponto = $conn->prepare("
        SELECT id_ponto
        FROM ponto_dia
        WHERE id_usuario = ? AND fim_ponto IS NULL
        LIMIT 1;
    ");
    $verificar_ponto->bind_param("i", $id_usuario);
    $verificar_ponto->execute();
    $resultado_ponto = $verificar_ponto->get_result();

    if ($resultado_pausa->num_rows > 0) {
        $id_pausa = $resultado_pausa->fetch_assoc()['id_pausa'];
        $update_pausa = $conn->prepare("
            UPDATE pausa
            SET fim = CURTIME()
            WHERE id_usuario = ? AND id_pausa = ?;
        ");
        $update_pausa->bind_param("ii", $id_usuario, $id_pausa);
        $update_pausa->execute();
    }
    
    if ($resultado_ponto->num_rows > 0) {
        $id_ponto = $resultado_ponto->fetch_assoc()['id_ponto'];
        $update_ponto = $conn->prepare("
            UPDATE ponto_dia
            SET fim_ponto = CURTIME()
            WHERE id_usuario = ? AND id_ponto = ?;
        ");
        $update_ponto->bind_param("ii", $id_usuario, $id_ponto);
        $update_ponto->execute();
    }
    
}


function verificar_tempo_por_ponto($conn, $id_usuario) {
    // verifica se tem ponto em aberto
    $verificar_ponto = $conn->prepare("
        SELECT id_ponto, inicio_ponto
        FROM ponto_dia
        WHERE id_usuario = ? 
        AND data_ponto = CURDATE() 
        AND fim_ponto IS NULL
        LIMIT 1;
    ");
    $verificar_ponto->bind_param("i", $id_usuario);
    $verificar_ponto->execute();
    $resultado_ponto = $verificar_ponto->get_result();

    if ($resultado_ponto->num_rows === 0) {
        $verificar_ponto->close();
        return [
            'tipo' => 'sem_ponto_aberto',
            'resultado' => 0,
            'mensagem' => 'Nenhum ponto aberto para hoje'
        ];
    }

    $ponto = $resultado_ponto->fetch_assoc();
    $id_ponto = $ponto['id_ponto'];
    $verificar_ponto->close();

    $verificar_ponto_finalizado = $conn->prepare("
        SELECT id_ponto
        FROM ponto_dia
        WHERE data_ponto = CURDATE() 
        AND fim_ponto IS NOT NULL
        AND id_usuario = ?
        LIMIT 1;
    ");
    $verificar_ponto_finalizado->bind_param("i", $id_usuario);
    $verificar_ponto_finalizado->execute();
    $resultado_finalizado = $verificar_ponto_finalizado->get_result();

    if ($resultado_finalizado->num_rows > 0) {
        $verificar_ponto_finalizado->close();
        return [
            'tipo' => 'bloqueado',
            'resultado' => 0,
            'mensagem' => 'Ponto do dia já foi finalizado'
        ];
    }
    $verificar_ponto_finalizado->close();

    /* verifica:
        - quanto tempo de hora extra o setor dele pode ter
        - tempo da jornada de trabalho
        - tempo maximo de trabalho
        - e o tempo trabalhado a partir do inico_ponto até agora
    */
    $stmt = $conn->prepare("
        SELECT 
            TIME_TO_SEC(tempo_jornada.maximo_hora_extra) AS hora_extra,
            TIME_TO_SEC(tempo_jornada.jornada) AS segundos_jornada,
            TIME_TO_SEC(ADDTIME(tempo_jornada.jornada, tempo_jornada.maximo_hora_extra)) AS segundos_maximos,
            TIMESTAMPDIFF(SECOND, ponto_dia.inicio_ponto, NOW()) AS segundos_trabalhados,
            ponto_dia.inicio_ponto,
            tempo_jornada.jornada AS jornada_formatada
        FROM ponto_dia
        JOIN grupo_setor
            ON grupo_setor.id_usuario = ponto_dia.id_usuario
        JOIN setor
            ON setor.id_setor = grupo_setor.id_setor
        JOIN tempo_jornada
            ON tempo_jornada.id_tempo = setor.id_tempo
        WHERE ponto_dia.id_ponto = ?
            AND ponto_dia.id_usuario = ?
            AND ponto_dia.fim_ponto IS NULL
        LIMIT 1;
    ");
    
    $stmt->bind_param("ii", $id_ponto, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return [
            'tipo' => 'erro',
            'resultado' => 0,
            'mensagem' => 'Erro ao buscar informações do ponto'
        ];
    }
    
    $dados = $result->fetch_assoc();
    $stmt->close();
    
    // Calcula pausas para descontar do tempo trabalhado
    $tempo_pausas = calcular_tempo_pausas($conn, $id_usuario, $id_ponto);
    $segundos_trabalhados_efetivos = $dados['segundos_trabalhados'] - $tempo_pausas;
    
    // Verifica se excedeu o tempo máximo
    if ($segundos_trabalhados_efetivos >= $dados['segundos_maximos']) {
        $minutos_extra = round($dados['hora_extra'] / 60);
        return [
            'tipo' => 'excedido',
            'resultado' => $minutos_extra,
            'segundos_trabalhados' => $segundos_trabalhados_efetivos,
            'segundos_jornada' => $dados['segundos_jornada'],
            'segundos_maximos' => $dados['segundos_maximos'],
            'mensagem' => "Tempo máximo excedido. Hora extra máxima: {$minutos_extra} minutos"
        ];
    }
    
    // Verifica se está em hora extra
    if ($segundos_trabalhados_efetivos >= $dados['segundos_jornada']) {
        $segundos_extra = $segundos_trabalhados_efetivos - $dados['segundos_jornada'];
        $minutos_extra = round($segundos_extra / 60);
        
        return [
            'tipo' => 'tempo_extra',
            'resultado' => $minutos_extra,
            'segundos_trabalhados' => $segundos_trabalhados_efetivos,
            'segundos_jornada' => $dados['segundos_jornada'],
            'segundos_maximos' => $dados['segundos_maximos'],
            'mensagem' => "Em hora extra: {$minutos_extra} minutos"
        ];
    }
    
    // Ainda falta tempo para completar a jornada
    $segundos_faltantes = $dados['segundos_jornada'] - $segundos_trabalhados_efetivos;
    $minutos_faltantes = round($segundos_faltantes / 60);
    
    return [
        'tipo' => 'tempo_faltante',
        'resultado' => -$minutos_faltantes, 
        'segundos_trabalhados' => $segundos_trabalhados_efetivos,
        'segundos_jornada' => $dados['segundos_jornada'],
        'segundos_maximos' => $dados['segundos_maximos'],
        'mensagem' => "Faltam {$minutos_faltantes} minutos para completar a jornada"
    ];
}

function calcular_tempo_pausas($conn, $id_usuario, $id_ponto) {
    // pegando o valor total das pausas ativadas hoje
    $stmt_pausas = $conn->prepare("
        SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(fim, inicio))), 0) AS segundos_pausas
        FROM pausa
        WHERE id_usuario = ?
        AND data = CURDATE()
        AND fim IS NOT NULL;
    ");
    $stmt_pausas->bind_param("i", $id_usuario);
    $stmt_pausas->execute();
    $result_pausas = $stmt_pausas->get_result();
    $pausas_finalizadas = $result_pausas->fetch_assoc()['segundos_pausas'];
    $stmt_pausas->close();


    // pegando o id para calcular o tempo na pausa atual
    $stmt_id_pausas = $conn->prepare("
        SELECT id_pausa FROM pausa
        WHERE id_usuario = ? AND fim is null
    ");
    $stmt_id_pausas->bind_param("i", $id_usuario);
    $stmt_id_pausas->execute();
    $result_id_pausas = $stmt_id_pausas->get_result();
    
    if ($result_id_pausas->num_rows > 0) {
        $id_pausa = $result_id_pausas->fetch_assoc()['id_pausa'];
        
        $stmt_pausa_atual = $conn->prepare("
            SELECT TIMESTAMPDIFF(SECOND, inicio, NOW()) AS segundos_pausa_atual
            FROM pausa
            WHERE id_usuario = ?
            AND id_pausa = ?
            AND fim IS NULL
            LIMIT 1
        ");
        $stmt_pausa_atual->bind_param("ii", $id_usuario, $id_pausa);
        $stmt_pausa_atual->execute();
        $result_pausa_atual = $stmt_pausa_atual->get_result();
        
        $pausa_atual = 0;
        if ($result_pausa_atual->num_rows > 0) {
            $pausa_atual = $result_pausa_atual->fetch_assoc()['segundos_pausa_atual'];
        }
        $stmt_pausa_atual->close();
        
        return $pausas_finalizadas + $pausa_atual;
    }
    return $pausas_finalizadas;
}
?>