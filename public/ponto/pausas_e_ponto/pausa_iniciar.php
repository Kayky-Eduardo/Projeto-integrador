<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'];
$tipo = $_POST['tipo'] ?? null;

if (!$tipo) {
    http_response_code(400);
    echo json_encode(['error' => 'tipo_invalido']);
    exit;
}

$hoje = date('Y-m-d');

// 1) se já existe pausa aberta -> bloqueia
$sql = "SELECT COUNT(*) as cnt FROM pausa WHERE id_usuario = ? AND fim IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$cnt = $stmt->get_result()->fetch_assoc()['cnt'];
if ($cnt > 0) {
    echo json_encode(['success' => false, 'message' => 'Já existe uma pausa aberta. Finalize-a antes.']);
    exit;
}

if ($tipo === 'almoco') {
    // verifica se almoco já foi registrado hoje
    $sql = "SELECT COUNT(*) as cnt FROM pausa p JOIN pausa_config pc ON pc.id_config = p.id_config
            WHERE p.id_usuario = ? AND p.data = ? AND pc.is_almoco = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_usuario, $hoje);
    $stmt->execute();
    $used = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
    if ($used) {
        echo json_encode(['success' => false, 'message' => 'Almoço já registrado hoje.']);
        exit;
    }

    // encontrar id_config do almoco
    $sql = "SELECT id_config FROM pausa_config WHERE is_almoco = 1 LIMIT 1";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Configuração de almoço não encontrada.']);
        exit;
    }
    $id_config = $row['id_config'];
}
// 2) se pausa normal -> validar id_config
elseif ($tipo === 'pausa') {
    $id_config = intval($_POST['id_config'] ?? 0);
    if ($id_config <= 0) {
        echo json_encode(['success' => false, 'message' => 'Tipo de pausa inválido.']);
        exit;
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Tipo desconhecido']);
    exit;
}

// se tudo ok -> inserir pausa
$sql = "INSERT INTO pausa (id_usuario, id_config, inicio, data) VALUES (?, ?, NOW(), ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $id_usuario, $id_config, $hoje);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pausa iniciada corretamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao iniciar pausa.']);
}
exit;
