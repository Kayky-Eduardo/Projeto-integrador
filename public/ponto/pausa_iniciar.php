<?php
include '../../BD/conexao.php';
include '../../include/verificacao.php'; // garante que o usuário está logado

header('Content-Type: application/json');

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    echo json_encode(['erro' => true, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

// Verifica se já existe uma pausa ativa
$sql = "SELECT * FROM pausa WHERE id_usuario = ? AND status = 'ativa'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['erro' => true, 'mensagem' => 'Você já está em uma pausa ativa.']);
    exit;
}

// Inicia uma nova pausa
$sql = "INSERT INTO pausa (id_usuario, inicio, status) VALUES (?, NOW(), 'ativa')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);

if ($stmt->execute()) {
    echo json_encode(['erro' => false, 'mensagem' => 'Pausa iniciada com sucesso!']);
} else {
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao iniciar pausa.']);
}
?>
