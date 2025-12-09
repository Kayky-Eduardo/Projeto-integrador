<?php
function adicionar_horas($conn, $id_usuario, $minutos, $tipo = 'hora_extra', $descricao = null) {
    if ($minutos === null) {
        return;
    }
   
    $stmt_saldo = $conn->prepare("
    SELECT
        id_banco,
        saldo_minutos
    FROM banco_horas
    WHERE id_usuario = ?
    ");
    $stmt_saldo->bind_param('i', $id_usuario);
    $stmt_saldo->execute();
    $result_verificacao = $stmt_saldo->get_result();
    
    if ($result_verificacao->num_rows === 0) {
        $stmt_criar_banco = $conn->prepare("
        INSERT INTO banco_horas(id_usuario, saldo_minutos)
        VALUES (?, ?);
        ");
        $stmt_criar_banco->bind_param('ii', $id_usuario, $minutos);
        $stmt_criar_banco->execute();
        $saldo_anterior = 0;
        $id_banco = $stmt_criar_banco->insert_id;
        $stmt_criar_banco->close();
    } else {
        $array_antigo = $result_verificacao->fetch_assoc();
        $saldo_anterior = $array_antigo['saldo_minutos'];
        $id_banco = $array_antigo['id_banco'];
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
        (id_usuario, id_banco, data, minutos, tipo, descricao, saldo_anterior, saldo_novo)
        VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)
    ");
    $stmt_historico->bind_param("iiissii",
    $id_usuario, $id_banco, $minutos, $tipo, $descricao,
    $saldo_anterior, $saldo_novo
    );

    $stmt_historico->execute();
    $stmt_historico->close();
}

function get_banco_data($conn, $id_usuario, $inicio, $fim) {
    $dados_antigos = [];

    $stmt = $conn->prepare("
    SELECT id_historico, data, saldo_anterior, saldo_novo
    FROM banco_horas_historico
    WHERE data >= ?
    AND data <= ?
    AND id_usuario = ?;
    ");
    $stmt->bind_param("ssi", $inicio, $fim, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    while ($linha = $result->fetch_assoc()) {
        $dados_antigos[] = $linha;
    };

    $stmt->close();
    
    return $dados_antigos;
}


function get_banco_horas($conn, $id_usuario) {
    $stmt = $conn->prepare("
        SELECT
            id_banco,
            saldo_minutos,
            ultima_atualizacao
        FROM banco_horas 
        WHERE id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
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
    
    $stmt_saldo_anterior = $conn->prepare("
        SELECT
            saldo_anterior
        FROM banco_horas_historico 
        WHERE id_banco = ?
        order by criado_em desc
        limit 1;
    ");
    $stmt_saldo_anterior->bind_param("i", $dados['id_banco']);
    $stmt_saldo_anterior->execute();

    $result_saldo = $stmt_saldo_anterior->get_result();
    if ($result_saldo->num_rows == 0) {
        return [
        'saldo_formatado' => "00:00",
        'ultima_atualizacao' => 'Sem registros'
        ];
    }

    $saldo_antigo = $result_saldo->fetch_assoc()['saldo_anterior'];

    $stmt_saldo_anterior->close();
    
    $horas_antiga = floor(abs($saldo_antigo) / 60);
    $mins_antigo = abs($saldo_antigo) % 60; 
    $sinal_antigo = $saldo_antigo < 0 ? '-' : '+';
    $saldo_anterior_formatado = sprintf('%s%02d:%02d', $sinal_antigo, $horas_antiga, $mins_antigo);

    $minutos = $dados['saldo_minutos'];
    $horas = floor(abs($minutos) / 60);
    $mins = abs($minutos) % 60;
    $sinal = $minutos < 0 ? '-' : '+';
    $saldo_formatado = sprintf('%s%02d:%02d', $sinal, $horas, $mins);
    
    return [
        'saldo_antigo' => "$saldo_anterior_formatado",
        'saldo_formatado' => "$saldo_formatado",
        'ultima_atualizacao' => $dados['ultima_atualizacao']
    ];
}

function retirar_horas($conn, $id_usuario, $minutos, $tipo = 'falta', $descricao = null) {
    adicionar_horas($conn, $id_usuario, -abs($minutos), $tipo, $descricao);
}

function calcular_debito_falta($conn, $id_usuario, $data) {
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
    retirar_horas($conn, $id_usuario, $minutos_jornada, 'falta', "Falta no dia {$data}");
    
    return $minutos_jornada;
}