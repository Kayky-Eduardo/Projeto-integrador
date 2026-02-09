<?php
function coleta_id_ponto($conn, $id_usuario) {
    $verificar_ponto = $conn->prepare("
    SELECT id_ponto
    FROM ponto_dia
    WHERE id_usuario = ? AND fim_ponto IS NULL
    LIMIT 1;
    ");
    $verificar_ponto->bind_param("i", $id_usuario);
    $verificar_ponto->execute();
    $resultado_ponto = $verificar_ponto->get_result();

    if ($resultado_ponto->num_rows > 0) {
        $id_ponto = $resultado_ponto->fetch_assoc()['id_ponto'];
        return [
            "sucesso" => true,
            "id_ponto" => $id_ponto
        ];
    }
    return [
        "sucesso" => false
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

function verificar_tempo_por_ponto($conn, $id_ponto, $id_usuario) {
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
    $tempo_pausas = calcular_tempo_pausas($conn, $id_ponto, $id_usuario);
    $segundos_trabalhados_efetivos = $dados['segundos_trabalhados'] - $tempo_pausas;

    
    // Verifica se excedeu o tempo máximo
    if ($segundos_trabalhados_efetivos >= $dados['segundos_maximos']) {
        return [
            'tipo' => 'excedido',
            'resultado' => $dados['hora_extra'],
            'segundos_trabalhados' => $segundos_trabalhados_efetivos,
            'segundos_jornada' => $dados['segundos_jornada'],
            'segundos_maximos' => $dados['segundos_maximos'],
            'mensagem' => "Tempo máximo excedido. Hora extra máxima: "
            ];
    }
            
    // Verifica se está em hora extra
    if ($segundos_trabalhados_efetivos >= $dados['segundos_jornada']) {
        $segundos_extra = $segundos_trabalhados_efetivos - $dados['segundos_jornada'];
        $minutos_extra = round($segundos_extra / 60);
        $segundos_restantes = $dados['hora_extra'] - $segundos_extra;

        return [
            'tipo' => 'tempo_extra',
            'resultado' => $minutos_extra,
            'segundos_restantes' => $segundos_restantes,
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
        'segundos_faltantes' => "$segundos_faltantes"
    ];
}

function formatar_tempo($segundos) {
    if ($segundos > 60) {
        $minutos = floor($segundos / 60);
    }

    $sinal = $minutos < 0 ? '-' : '+';
    $total = abs($minutos);

    $dias = floor($total / 1440); // 1440 = 24 * 60
    $resto = $total % 1440;

    $horas = floor($resto / 60);
    $mins  = $resto % 60;

    if ($dias > 0) {
        return sprintf('%dd %02d:%02d dias', $dias, $horas, $mins);
    }

    return sprintf('%02d:%02d horas', $horas, $mins);
}

function coleta_dado($conn, $id_usuario) {
    $resposta_id_ponto = coleta_id_ponto($conn, $id_usuario);

    if ($resposta_id_ponto['sucesso']) {
        $id_ponto = $resposta_id_ponto['id_ponto'];
        $dados = verificar_tempo_por_ponto($conn, $id_ponto, $id_usuario);
        $tipo = $dados['tipo'];
        
        if ($tipo === "tempo_extra") {
            $minutos = floor($dados['segundos_restantes'] / 60);
            
            if ($minutos <= 60) {
                $tempo = formatar_tempo($dados['segundos_restantes']);
    
                return [
                    "coleta" => true,
                    "mensagem" => "Você está em hora extra. Tempo de hora extra restante: " . $tempo,
                    "tempo_extra" => $dados['resultado'],
                    "tempo_restante" => $tempo,
                ];
            } else {
                return [
                    "coleta" => false,
                    "mensagem" => "Muito tempo para acabar"
                ];
            }
        }

        if ($tipo === "tempo_faltante") {
            $minutos = floor($dados['tempo'] / 60);
            
            $tempo = formatar_tempo($dados['segundos_faltantes']);

            return [
                "coleta" => true,
                "mensagem" => "faltam " . $tempo . " para completar a sua jornada",
                "tempo_faltante" => $tempo
            ];
        }

        if ($tipo === "excedido") {
            $tempo = formatar_tempo($dados['resultado']);

            return [
                "coleta" => true,
                "mensagem" => $dados['mensagem'] .  $tempo,
                "tipo" => "tempo_trabalhado",
                "tempo_trabalhado" => formatar_tempo($dados['segundos_trabalhados']),
            ];
        }
    } else {
        return [
            "coleta" => false,
            "mensagem" => "Não foi encontrado o id do ponto"
        ];
    }
}

function verificacao_aviso_pendente() {

}

function coleta_aviso() {

}
?>