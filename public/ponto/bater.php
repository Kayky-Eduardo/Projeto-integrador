<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
date_default_timezone_set('America/Sao_Paulo');

// Busca ID do usuário logado
$id_usuario = $_SESSION['id_usuario'] ?? null;

// Se não houver usuário logado, retorna erro via JSON
if (!$id_usuario) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

// Tempo mínimo entre batidas (em segundos)
$min_interval_seconds = 1;

// Data atual (somente data)
$hoje = date("Y-m-d");

// Data e hora atual completas
$agora = date("Y-m-d H:i:s");

// ==============================
// BUSCA REGISTRO DE PONTO DO DIA
// ==============================

// Prepara consulta
$q = $conn->prepare("SELECT * FROM ponto_dia WHERE id_usuario = ? AND data_reg = ?");

// Aplica parâmetros na query
$q->bind_param("is", $id_usuario, $hoje);

// Executa consulta
$q->execute();

// Obtém resultados
$res = $q->get_result();
$reg = $res->fetch_assoc();

// =======================================
// FUNÇÃO PARA OBTER DATA DA ÚLTIMA BATIDA
// =======================================
function ultima_batida_ts($reg)
{
    $t = [];

    // Percorre todas as colunas de horários possíveis
    foreach (['inicio_ponto', 'inicio_almoco', 'fim_almoco', 'fim_ponto'] as $c) {
        if (!empty($reg[$c])) {
            $t[] = strtotime($reg[$c]);
        }
    }

    // Retorna o maior timestamp (última batida)
    return $t ? max($t) : 0;
}

// ===========================================
// CASO NÃO EXISTA REGISTRO DO DIA — CRIA NOVO
// ===========================================
if (!$reg) {

    // Insere primeiro ponto como entrada do dia
    $ins = $conn->prepare(
        "INSERT INTO ponto_dia (id_usuario, data_reg, inicio_ponto, status)
         VALUES (?, ?, ?, 'Em Andamento')"
    );

    $ins->bind_param("iss", $id_usuario, $hoje, $agora);

    if ($ins->execute()) {

        // Retorna sucesso via JSON
        echo json_encode([
            'ok' => true,
            'mensagem' => 'Entrada registrada: ' . $agora,
            'acao' => 'entrada'
        ]);
    } else {

        // Retorna erro se falhar
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar entrada.']);
    }

    exit;
}

// ============================
// EVITA BATIDAS MUITO PRÓXIMAS
// ============================
$ultima_ts = ultima_batida_ts($reg);

// Se o usuário bater ponto rápido demais, bloqueia
if (time() - $ultima_ts < $min_interval_seconds) {
    echo json_encode(['ok' => false, 'mensagem' => 'Você já bateu há pouco tempo. Aguarde alguns segundos.']);
    exit;
}

// Se ainda não marcou início do almoço
if (empty($reg['inicio_almoco'])) {

    $up = $conn->prepare("UPDATE ponto_dia SET inicio_almoco = ? WHERE id_ponto = ?");
    $up->bind_param("si", $agora, $reg['id_ponto']);

    if ($up->execute()) {
        echo json_encode([
            'ok' => true,
            'mensagem' => 'Início do almoço registrado: ' . $agora,
            'acao' => 'inicio_almoco'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar início do almoço.']);
    }

    exit;
}

// Se ainda não marcou fim do almoço
if (empty($reg['fim_almoco'])) {

    $up = $conn->prepare("UPDATE ponto_dia SET fim_almoco = ? WHERE id_ponto = ?");
    $up->bind_param("si", $agora, $reg['id_ponto']);

    if ($up->execute()) {
        echo json_encode([
            'ok' => true,
            'mensagem' => 'Fim do almoço registrado: ' . $agora,
            'acao' => 'fim_almoco'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar fim do almoço.']);
    }

    exit;
}

// Se ainda não marcou a saída
if (empty($reg['fim_ponto'])) {

    // Ao bater saída, muda o status para Finalizado
    $up = $conn->prepare(
        "UPDATE ponto_dia SET fim_ponto = ?, status = 'Finalizado'
         WHERE id_ponto = ?"
    );

    $up->bind_param("si", $agora, $reg['id_ponto']);

    if ($up->execute()) {
        echo json_encode([
            'ok' => true,
            'mensagem' => 'Saída registrada: ' . $agora . '. Ponto finalizado e aguardando aprovação.',
            'acao' => 'saida'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensagem' => 'Erro ao registrar saída.']);
    }

    exit;
}

// ==============================
// SE TODAS AS BATIDAS JÁ EXISTEM
// ==============================
echo json_encode(['ok' => false, 'mensagem' => 'Já há todas as batidas do dia.']);
exit;
