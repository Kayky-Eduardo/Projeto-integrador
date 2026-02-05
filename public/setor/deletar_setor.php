<?php
require "../../include/verificacao.php";
include "../../BD/conexao.php";

if (!isset($_GET['setor']) || empty($_GET['setor'])) {
    header("Location: setores.php");
    exit;
}

$id = $_GET['setor'];
echo $id;
// alterar para limpar grupo_setor tbm
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
