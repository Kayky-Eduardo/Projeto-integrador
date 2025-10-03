<?php
include "../../BD/conexao.php";
Header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : '';
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido.']);
    exit;
}

// Consulta SQL para deletar o cadastro
$sqld = "DELETE FROM cargo WHERE id_cargo = ?";
$stmt = $conn->prepare($sqld);
$stmt->bind_param("i", $id);

// Executa a consulta e verifica se foi bem-sucedida
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Cadastro deletado com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum cadastro encontrado com o ID fornecido.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao deletar o cadastro: ' . $stmt->error]);
}

// Fecha o statement e a conexão
$stmt->close();
$conn->close();
?>