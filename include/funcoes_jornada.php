<?php
// Busca a jornada de trabalho do usuário em uma data específica

function buscarJornadaUsuario($conn, $usuarioId, $data = null) {
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
    $stmt->bind_param("iss", $usuarioId, $data, $data);
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
function calcularHorasTrabalhadas($conn, $usuarioId, $dataInicio, $dataFim) { 

    // Busca todos os pontos aprovados no período   
    $stmt = $conn->prepare("
    SELECT data_ponto,
    hora_entrada,
    hora_saida,
    hora_almoco_saida,
    hora_almoco_retorno
    FROM ponto
    WHERE id_usuario = ?
    AND data_ponto BETWEEN ? AND ?
    AND status = 'aprovado'
    ORDER BY data_ponto"
    );
    $stmt->bind_param("iss", $usuarioId, $dataInicio, $dataFim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $totalMinutos = 0;
    $detalhes = [];
    
    while ($ponto = $result->fetch_assoc()) {
        $minutosDia = calcularMinutosDia($ponto);
        $totalMinutos += $minutosDia;
        
        $detalhes[] = [
            'data' => $ponto['data_ponto'],
            'minutos' => $minutosDia,
            'horas' => round($minutosDia / 60, 2)
        ];
    }
    
    $stmt->close();
    
    return [
        'total_horas' => round($totalMinutos / 60, 2),
        'total_minutos' => $totalMinutos,
        'detalhes' => $detalhes
    ];
}

// Calcula quantos minutos o usuário trabalhou em um dia específico, descontando o almoço.
function calcularMinutosDia($ponto) {
    // Se não tem entrada ou saída, não trabalhou
    if (!$ponto['hora_entrada'] || !$ponto['hora_saida']) {
        return 0;
    }
    
    $entrada = strtotime($ponto['hora_entrada']);
    $saida = strtotime($ponto['hora_saida']);
    
    $minutosTotal = ($saida - $entrada) / 60;
    
    // Desconta intervalo de almoço se houver
    if ($ponto['hora_almoco_saida'] && $ponto['hora_almoco_retorno']) {
        $almocoSaida = strtotime($ponto['hora_almoco_saida']);
        $almocoRetorno = strtotime($ponto['hora_almoco_retorno']);
        $minutosAlmoco = ($almocoRetorno - $almocoSaida) / 60;
        $minutosTotal -= $minutosAlmoco;
    }
    
    return max(0, $minutosTotal); // garantindo que não é 0
}

// Calcula quantas horas o usuário DEVERIA ter trabalhado no período,
// baseado na jornada configurada.

function calcularHorasEsperadas($conn, $usuarioId, $dataInicio, $dataFim) {
    $inicio = new DateTime($dataInicio);
    $fim = new DateTime($dataFim);
    $current = clone $inicio;
    
    $total_horas = 0;
    $detalhes = [];
    
    while ($current <= $fim) {
        $data_atual = $current->format('Y-m-d');
        $dia_semana = (int)$current->format('N'); // 1=segunda, 7=domingo
        
        // Busca jornada válida para esta data
        $jornada = buscarJornadaUsuario($conn, $usuarioId, $data_atual);
        $dias_semana = json_decode($jornada['dias_semana'], true);
        
        // Verifica se é dia útil (não é feriado e está nos dias de trabalho)
        $dia_util = in_array($dia_semana, $dias_semana);
        $feriado = verificarFeriado($conn, $data_atual);
        
        if ($dia_util && !$feriado) {
            $horas_dia = floatval($jornada['horas_diarias']);
            $total_horas += $horas_dia;
            
            $detalhes[] = [
                'data' => $data_atual,
                'horas' => $horas_dia,
                'dia_semana' => obterNomeDiaSemana($dia_semana)
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
function verificarFeriado($conn, $data) {
    $stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM feriados WHERE data = ?
    ");
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'] > 0;
}

// Retorna nome do dia da semana
function obterNomeDiaSemana($numero) {
    $dias = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    
    return $dias[$numero] ?? 'Desconhecido';
}



// Verifica a jornada de trabalho
function verificarJornada($conn, $usuarioId, $dataInicio, $dataFim) {
    // Calcula horas trabalhadas (baseado nos pontos aprovados)
    $trabalhadas = calcularHorasTrabalhadas($conn, $usuarioId, $dataInicio, $dataFim);
    
    // Calcula horas esperadas (baseado na jornada configurada)
    $esperadas = calcularHorasEsperadas($conn, $usuarioId, $dataInicio, $dataFim);
    
    $diferenca = $trabalhadas['total_horas'] - $esperadas['total_horas'];
    $percentual = $esperadas['total_horas'] > 0 ?
    ($trabalhadas['total_horas'] / $esperadas['total_horas']) * 100 : 0;
    
    return [
        'usuario_id' => $usuarioId,
        'periodo' => [
            'inicio' => $dataInicio,
            'fim' => $dataFim
        ],
        'horas_trabalhadas' => $trabalhadas['total_horas'],
        'horas_esperadas' => $esperadas['total_horas'],
        'diferenca' => round($diferenca, 2),
        'percentual' => round($percentual, 2),
        'detalhes_trabalhados' => $trabalhadas['detalhes'],
        'detalhes_esperados' => $esperadas['detalhes']
    ];
}

// Atualiza a assiduidae do usuário

function atualizarAssiduidade($conn, $usuarioId, $percentual) {
    $stmt = $conn->prepare("UPDATE usuario SET assiduidade = ? WHERE id_usuario = ?");
    $stmt->bind_param("di", $percentual, $usuarioId);
    $sucesso = $stmt->execute();
    $stmt->close();
    
    return $sucesso;
}