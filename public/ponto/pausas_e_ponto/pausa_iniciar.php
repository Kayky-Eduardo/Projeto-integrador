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

// Aceitamos apenas pausas por id_config (tratamos almoço como pausa comum)
if ($tipo === 'pausa') {
    $id_config = intval($_POST['id_config'] ?? 0);
    if ($id_config <= 0) {
        echo json_encode(['success' => false, 'message' => 'Tipo de pausa inválido.']);
        exit;
    }

    // checar limite diário definido em pausa_config
    $stmtL = $conn->prepare("SELECT limite_pausa_diario FROM pausa_config WHERE id_config = ? LIMIT 1");
    $stmtL->bind_param("i", $id_config);
    $stmtL->execute();
    $rowL = $stmtL->get_result()->fetch_assoc();
    $limite = intval($rowL['limite_pausa_diario'] ?? 0);
    if ($limite > 0) {
        $stmtC = $conn->prepare("SELECT COUNT(*) as cnt FROM pausa WHERE id_usuario = ? AND data = ? AND id_config = ?");
        $stmtC->bind_param("isi", $id_usuario, $hoje, $id_config);
        $stmtC->execute();
        $cnt = intval($stmtC->get_result()->fetch_assoc()['cnt'] ?? 0);
        if ($cnt >= $limite) {
            echo json_encode(['success' => false, 'message' => 'Limite diário para essa pausa atingido.']);
            exit;
        }
    }

} else {
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
