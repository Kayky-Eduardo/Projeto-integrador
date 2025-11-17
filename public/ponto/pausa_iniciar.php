<?php
session_start();

include '../../BD/conexao.php';
include '../../include/verificacao.php';

header('Content-Type: application/json');

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    echo json_encode(['erro' => true, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

// Verifica se recebeu motivo
$motivo = $_POST['motivo'] ?? '';

if (empty($motivo)) {
    echo json_encode(['erro' => true, 'mensagem' => 'Motivo da pausa não informado.']);
    exit;
}

// Verifica se existe pausa ativa
$sql = "SELECT id_pausa FROM pausa WHERE id_usuario = ? AND status = 'ativa' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    echo json_encode(['erro' => true, 'mensagem' => 'Você já está em uma pausa ativa.']);
    exit;
}

// Inserir pausa com motivo
$sql = "INSERT INTO pausa (id_usuario, motivo, inicio, status) 
        VALUES (?, ?, NOW(), 'ativa')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_usuario, $motivo);

if ($stmt->execute()) {
    echo json_encode(['erro' => false, 'mensagem' => 'Pausa iniciada com sucesso!']);
} else {
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao iniciar pausa.']);
}
?>
