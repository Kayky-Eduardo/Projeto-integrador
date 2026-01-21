<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'];

// pega pausa aberta
$sql = "SELECT p.id_pausa, p.id_config, p.inicio, pc.descricao_pausa FROM pausa p
        JOIN pausa_config pc ON pc.id_config = p.id_config
        WHERE p.id_usuario = ? AND p.fim IS NULL ORDER BY p.id_pausa DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();

if (!$row = $res->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma pausa aberta para finalizar.']);
    exit;
}

// calculos dos minutos para verificar duracao do guilherme
$id_pausa = $row['id_pausa'];
$inicio = strtotime($row['inicio']);
$fim = time();
$minutos = floor(($fim - $inicio) / 60);

// atualiza
$sqlUp = "UPDATE pausa SET fim = NOW(), duracao_minutos = ? WHERE id_pausa = ?";
$stmt2 = $conn->prepare($sqlUp);
$stmt2->bind_param("ii", $minutos, $id_pausa);
if ($stmt2->execute()) {
    echo json_encode(['success' => true, 'message' => "Pausa finalizada ({$row['descricao_pausa']}). Duração: {$minutos} min.", 'minutos' => $minutos]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao finalizar pausa.']);
}
exit;
