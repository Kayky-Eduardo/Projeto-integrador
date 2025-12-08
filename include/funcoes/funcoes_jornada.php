<?php
// Busca a jornada de trabalho do usuário em uma data específica

function buscar_jornada_usuario($conn, $usuario_id, $data = null) {
    $data = $data ?? date('Y-m-d');
    
    $stmt = $conn->prepare("
        SELECT horas_diarias, dias_semana 
        FROM jornadas_trabalho 
        WHERE usuario_id = ? 
        AND data_inicio <= ?
        AND (data_fim IS NULL OR data_fim >= ?)
        ORDER BY data_inicio DESC 
        LIMIT 1"
    );
    $stmt->bind_param("iss", $usuario_id, $data, $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $jornada = $result->fetch_assoc();
    $stmt->close();
    
    // Retorna jornada padrão se não encontrar
    if (!$jornada) {
        return [
            'horas_diarias' => 8.0,
            'dias_semana' => json_encode([1, 2, 3, 4, 5])
        ];
    }
    
    return $jornada;
}

// Soma todas as horas que o usuário trabalhou em um período
// (baseado nos registros da tabela ponto).
function calcular_horas_trabalhadas($conn, $usuario_id, $data_inicio, $data_fim) { 
    // Busca todos os pontos aprovados no período   
    $stmt = $conn->prepare("
    SELECT data_ponto,
    inicio_ponto,
    fim_ponto
    FROM ponto_dia
    WHERE id_usuario = ?
    AND data_ponto BETWEEN ? AND ?
    AND status = 'aprovado'
    ORDER BY data_ponto
    ");
    $stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_minutos = 0;
    $detalhes = [];
    
    while ($ponto = $result->fetch_assoc()) {
        $minutos_dia = calcular_minutos_dia($ponto);
        $total_minutos += $minutos_dia;
        
        $detalhes[] = [
            'data' => $ponto['data_ponto'],
            'minutos' => $minutos_dia,
            'horas' => round($minutos_dia / 60, 2)
        ];
    }
    
    $stmt->close();
    
    return [
        'total_horas' => round($total_minutos / 60, 2),
        'total_minutos' => $total_minutos,
        'detalhes' => $detalhes
    ];
}

// Calcula quantos minutos o usuário trabalhou em um dia específico, descontando o almoço.
function calcular_minutos_dia($ponto) {
    // Se não tem entrada ou saída, não trabalhou
    if (!$ponto['inicio_ponto'] || !$ponto['fim_ponto']) {
        return 0;
    }
    
    $entrada = strtotime($ponto['inicio_ponto']);
    $saida = strtotime($ponto['fim_ponto']);
    
    $minutos_total = ($saida - $entrada) / 60;
    
    // Desconta intervalo de almoço se houver
    // if ($ponto['hora_almoco_saida'] && $ponto['hora_almoco_retorno']) {
    //     $almoco_saida = strtotime($ponto['hora_almoco_saida']);
    //     $almoco_retorno = strtotime($ponto['hora_almoco_retorno']);
    //     $minutos_almoco = ($almoco_retorno - $almoco_saida) / 60;
    //     $minutos_total -= $minutos_almoco;
    // }
    
    return max(0, $minutos_total); // garantindo que não é 0
}

// Calcula quantas horas o usuário DEVERIA ter trabalhado no período,
// baseado na jornada configurada.

function calcular_horas_esperadas($conn, $usuario_id, $data_inicio, $data_fim) {
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $current = clone $inicio;
    
    $total_horas = 0;
    $detalhes = [];
    
    while ($current <= $fim) {
        $data_atual = $current->format('Y-m-d');
        $dia_semana = (int)$current->format('N'); // 1=segunda, 7=domingo
        
        // Busca jornada válida para esta data
        $jornada = buscar_jornada_usuario($conn, $usuario_id, $data_atual);
        $dias_semana = json_decode($jornada['dias_semana'], true);
        
        // Verifica se é dia útil (não é feriado e está nos dias de trabalho)
        $dia_util = in_array($dia_semana, $dias_semana);
        $feriado = verificar_feriado($conn, $data_atual);
        
        if ($dia_util && !$feriado) {
            $horas_dia = floatval($jornada['horas_diarias']);
            $total_horas += $horas_dia;
            
            $detalhes[] = [
                'data' => $data_atual,
                'horas' => $horas_dia,
            ];
        }
        
        $current->modify('+1 day');
    }
    
    return [
        'total_horas' => round($total_horas, 2),
        'detalhes' => $detalhes
    ];
}

// simples, preciso explicar isto também?
function verificar_feriado($conn, $data) {
    $stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM feriados WHERE data = ?
    ");
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $linha = $result->fetch_assoc();
    $stmt->close();
    
    return $linha['total'] > 0;
}

// Verifica a jornada de trabalho
function verificar_jornada($conn, $usuario_id, $data_inicio, $data_fim) {
    // Calcula horas trabalhadas (baseado nos pontos aprovados)
    $trabalhadas = calcular_horas_trabalhadas($conn, $usuario_id, $data_inicio, $data_fim);
    
    // Calcula horas esperadas (baseado na jornada configurada)
    $esperadas = calcular_horas_esperadas($conn, $usuario_id, $data_inicio, $data_fim);
    
    $diferenca = $trabalhadas['total_horas'] - $esperadas['total_horas'];
    $percentual = $esperadas['total_horas'] > 0 ?
    ($trabalhadas['total_horas'] / $esperadas['total_horas']) * 100 : 0;
    
    $taxa_presenca = $trabalhadas['total_horas'] / $esperadas['total_horas'];
    return [
        'usuario_id' => $usuario_id,
        'horas_trabalhadas' => $trabalhadas['total_horas'],
        'horas_esperadas' => $esperadas['total_horas'],
        'taxa_presenca' => $taxa_presenca,
        'diferenca' => round($diferenca, 2),
        'percentual' => round($percentual, 2),
    ];
}

// Atualiza a assiduidae do usuário

function atualizar_assiduidade($conn, $usuario_id, $percentual) {
    $stmt = $conn->prepare("UPDATE usuario SET assiduidade = ? WHERE id_usuario = ?");
    $stmt->bind_param("di", $percentual, $usuario_id);
    $sucesso = $stmt->execute();
    $stmt->close();
    
    return $sucesso;
}

function set_jornada($conn, $jornada, $hora_extra) {
    $conn->begin_transaction();

    try {
        $conn->query("TRUNCATE TABLE tempo_jornada");

        $jornada_formatada = $jornada . ':00';
        $hora_extra_formatada = $hora_extra . ':00';

        $stmt = $conn->prepare("
            INSERT INTO tempo_jornada (jornada, maximo_hora_extra)
            VALUES (?, ?);
        ");

        $stmt->bind_param('ss', $jornada_formatada, $hora_extra_formatada);
        $sucesso = $stmt->execute();

        $stmt->close();
        $conn->commit();

        return $sucesso;
    } catch(Exception $e) {
        $conn->rollback();
        throw new Exception("Erro ao configurar jornada: " . $e->getMessage());
    }
}

?>