<?php
include "../../BD/conexao.php";

if (isset($_POST['id_usuario'])) {
    $id = $_POST['id_usuario'];
}

// Consulta SQL para deletar o cadastro
$sqld = "DELETE FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sqld);
$stmt->bind_param("i", $id);

//Executa a consulta e verifica se foi bem-sucedida
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("Location: ../../public/usuario/deletar.php");
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