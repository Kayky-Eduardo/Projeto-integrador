<?php
session_start();
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';
date_default_timezone_set('America/Sao_Paulo');

$id_usuario = $_SESSION['id_usuario'] ?? null;
$data = date("Y-m-d");

if (!$id_usuario) {
    echo json_encode(["error" => "Usuário não logado"]);
    exit;
}

// busca registro de ponto do dia (se existir)
$sql = "SELECT * FROM ponto WHERE id_usuario = ? AND data_ponto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_usuario, $data);
$stmt->execute();
$res = $stmt->get_result();
$registro = $res->fetch_assoc();

// determina estados de ponto/almoco
$ponto_aberto = false;
$almoco_aberto = false;

if ($registro) {
    if (!empty($registro['hora_entrada']) && empty($registro['hora_saida'])) {
        $ponto_aberto = true;
    }
    if (!empty($registro['hora_almoco_saida']) && empty($registro['hora_almoco_retorno'])) {
        $almoco_aberto = true;
    }
}

// BUSCA qualquer pausa aberta (qualquer tipo) — LIMIT 1
$sql = "SELECT * FROM pausa WHERE id_usuario = ? AND data_pausa = ? AND fim IS NULL LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_usuario, $data);
$stmt->execute();
$res = $stmt->get_result();
$pausa = $res->fetch_assoc();

if ($pausa) {
    $pausa_aberta = [
        "tipo" => $pausa['tipo_pausa'],
        "inicio" => $pausa['inicio']
    ];
} else {
    $pausa_aberta = false;
}

echo json_encode([
    "ponto_aberto" => $ponto_aberto,
    "almoco_aberto" => $almoco_aberto,
    "pausa_aberta" => $pausa_aberta
]);