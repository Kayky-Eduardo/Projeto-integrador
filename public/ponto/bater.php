<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

date_default_timezone_set('America/Sao_Paulo');
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$min_interval_seconds = 1;
$hoje = date("Y-m-d");
$agora = date("Y-m-d H:i:s");

// busca registro do dia
$q = $conn->prepare("SELECT * FROM ponto_dia WHERE id_usuario = ? AND data_reg = ?");
$q->bind_param("is", $id_usuario, $hoje);
$q->execute();
$res = $q->get_result();
$reg = $res->fetch_assoc();

function ultima_batida_ts($reg)
{
    $t = [];
    foreach (['inicio_ponto', 'inicio_almoco', 'fim_almoco', 'fim_ponto'] as $c) {
        if (!empty($reg[$c])) $t[] = strtotime($reg[$c]);
    }
    return $t ? max($t) : 0;
}

// se não existe registro do dia, cria com inicio_ponto = agora e status 'Em Andamento'
if (!$reg) {
    $ins = $conn->prepare("INSERT INTO ponto_dia (id_usuario, data_reg, inicio_ponto, status) VALUES (?, ?, ?, 'Em Andamento')");
    $ins->bind_param("iss", $id_usuario, $hoje, $agora);
    if ($ins->execute()) {
        echo json_encode(['ok' => true, 'mensagem' => 'Entrada registrada: ' . $agora, 'acao' => 'entrada']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar entrada.']);
    }
    exit;
}

// evita batidas muito próximas
$ultima_ts = ultima_batida_ts($reg);
if (time() - $ultima_ts < $min_interval_seconds) {
    echo json_encode(['ok' => false, 'mensagem' => 'Você já bateu há pouco tempo. Aguarde alguns segundos.']);
    exit;
}

// prioridade: inicio_almoco -> fim_almoco -> fim_ponto
if (empty($reg['inicio_almoco'])) {
    $up = $conn->prepare("UPDATE ponto_dia SET inicio_almoco = ? WHERE id_ponto = ?");
    $up->bind_param("si", $agora, $reg['id_ponto']);
    if ($up->execute()) {
        echo json_encode(['ok' => true, 'mensagem' => 'Início do almoço registrado: ' . $agora, 'acao' => 'inicio_almoco']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar início do almoço.']);
    }
    exit;
}

if (empty($reg['fim_almoco'])) {
    $up = $conn->prepare("UPDATE ponto_dia SET fim_almoco = ? WHERE id_ponto = ?");
    $up->bind_param("si", $agora, $reg['id_ponto']);
    if ($up->execute()) {
        echo json_encode(['ok' => true, 'mensagem' => 'Fim do almoço registrado: ' . $agora, 'acao' => 'fim_almoco']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar fim do almoço.']);
    }
    exit;
}

if (empty($reg['fim_ponto'])) {
    // Ao registrar saída, definimos status = 'Finalizado' (conforme seu schema)
    $up = $conn->prepare("UPDATE ponto_dia SET fim_ponto = ?, status = 'Finalizado' WHERE id_ponto = ?");
    $up->bind_param("si", $agora, $reg['id_ponto']);
    if ($up->execute()) {
        echo json_encode(['ok' => true, 'mensagem' => 'Saída registrada: ' . $agora . '. Ponto finalizado e aguardando aprovação.', 'acao' => 'saida']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar saída.']);
    }
    exit;
}

// já tem todas as batidas
echo json_encode(['ok' => false, 'mensagem' => 'Já há todas as batidas do dia.']);
exit;
