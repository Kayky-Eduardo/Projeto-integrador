<?php
require "../../include/verificacao.php";
include "../../BD/conexao.php";
verificar_login($conn);

if (!isset($_POST['id_setor']) || empty($_POST['id_setor'])) {
    header("Location: setor.php");
    exit;
}

$id = intval($_POST['id_setor']);

$stmt = $conn->prepare("DELETE FROM setor WHERE id_setor = ?");
$stmt->bind_param("i", $id);

$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: setores.php");
} else {
    echo "Setor não encontrado.";
}

// Fecha o statement e a conexão
$stmt->close();
$conn->close();
