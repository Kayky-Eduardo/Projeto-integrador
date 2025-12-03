<?php
function adicionar_horas($conn, $id_usuario, $minutos, $tipo = 'hora_extra', $descricao = null) {
    $stmt_saldo = $conn->prepare("
    SELECT saldo_minutos
    FROM banco_horas
    WHERE id_usuario = ?
    ");
    $stmt_saldo->bind_param('i', $id_usuario);
    $stmt_saldo->execute();
    $result_verificacao = $stmt_saldo->get_result();

    if ($result_verificacao->num_rows === 0) {
        $stmt_criar_banco = $conn->prepare("
        INSERT INTO banco_horas(id_usuario, saldo_minutos)
        VALUES (?, 0);
        ");
        $stmt_criar_banco->bind_param('ii', $id_usuario, $minutos);
        $stmt_criar_banco->execute();
        $saldo_anterior = 0;
    } else {
        $saldo_anterior = $result_verificacao->fetch_assoc()['saldo_minutos'];
    }
    $stmt_saldo->close();

    $saldo_novo = $saldo_anterior + $minutos;

    $stmt_update = $conn->prepare("
        UPDATE banco_horas 
        SET saldo_minutos = ? 
        WHERE id_usuario = ?
    ");
    $stmt_update->bind_param("ii", $saldo_novo, $id_usuario);
    $stmt_update->execute();
    $stmt_update->close();
    
    $stmt_historico = $conn->prepare("
        INSERT INTO banco_horas_historico 
        (id_usuario, data, minutos, tipo, descricao, saldo_anterior, saldo_novo)
        VALUES (?, CURDATE(), ?, ?, ?, ?, ?)
    ");
    $stmt_historico->bind_param("iissii",
    $id_usuario, $minutos, $tipo, $descricao,
    $saldo_anterior, $saldo_novo
    );

    $stmt_historico->execute();
    $stmt_historico->close();
}

function get_banco_horas($conn, $usuarioId) {
    $stmt = $conn->prepare("
        SELECT 
            saldo_minutos,
            ultima_atualizacao
        FROM banco_horas 
        WHERE id_usuario = ?
    ");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'saldo_minutos' => 0,
            'saldo_horas' => 0,
            'saldo_formatado' => '00:00'
        ];
    }
    
    $dados = $result->fetch_assoc();
    $stmt->close();
    
    $minutos = $dados['saldo_minutos'];
    $horas = floor(abs($minutos) / 60);
    $mins = abs($minutos) % 60;
    $sinal = $minutos < 0 ? '-' : '+';
    
    return [
        'saldo_minutos' => $minutos,
        'saldo_horas' => $minutos / 60,
        'saldo_formatado' => "$sinal" + "$horas:" + "$mins",
        'ultima_atualizacao' => $dados['ultima_atualizacao']
    ];
}

function retirar_tempo_banco($conn, $id_usuario, $minutos, $tipo = 'falta', $descricao = null) {
    adicionar_horas($conn, $id_usuario, -abs($minutos), $tipo, $descricao);
}

function calcular_debito_falta($conn, $usuarioId, $data) {
    // Busca jornada do usuário
    $stmt = $conn->prepare("
        SELECT TIME_TO_SEC(jornada) as segundos_jornada
        FROM tempo_jornada
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return 0;
    }
    
    $jornada = $result->fetch_assoc();
    $stmt->close();
    
    $minutos_jornada = round($jornada['segundos_jornada'] / 60);
    
    // Debita do banco de horas
    retirar_tempo_banco($conn, $usuarioId, $minutos_jornada, 'falta', "Falta no dia {$data}");
    
    return $minutos_jornada;
}