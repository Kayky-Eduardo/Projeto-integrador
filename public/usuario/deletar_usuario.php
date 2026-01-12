<?php
require "../../include/verificacao.php";
include "../../BD/conexao.php";
verificar_login($conn);

if (!isset($_POST['id_usuario']) || empty($_POST['id_usuario'])) {
    header("Location: lista.php");
    exit;
}

$id = intval($_POST['id_usuario']);

if ($id === $_SESSION['id_usuario']) {
    die("Você não pode excluir a si mesmo.");
}

$stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $id);

$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: lista.php");
} else {
    echo "Usuário não encontrado.";
}

$stmt->close();
$conn->close();
