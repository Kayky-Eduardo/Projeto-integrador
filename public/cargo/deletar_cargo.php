<?php
require "../../include/verificacao.php";
include "../../BD/conexao.php";

if (!isset($_GET['cargo']) || empty($_GET['cargo'])) {
    header("Location: cargos.php");
    exit;
}

$id = $_GET['cargo'];

$stmt_validacao = $conn->prepare("select id_usuario from usuario where id_cargo = ?");
$stmt_validacao->bind_param("i", $id);
$stmt_validacao->execute();
$resultado = $stmt_validacao->get_result();

if ($resultado->num_rows == 0) {
    // alterar para limpar grupo_cargo tbm
    $stmt = $conn->prepare("DELETE FROM cargo WHERE id_cargo = ?");
    $stmt->bind_param("i", $id);
    
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        header("Location: cargos.php");
    } else {
        echo "cargo não encontrado.";
    }
} else {
    $_SESSION['erro_deletar_cargo'] = "Não é possível excluir, ainda existem usuários nesse cargo!" ;
    header("Location: editar_cargo.php?id=$id");
}

// Fecha o statement e a conexão
$stmt_validacao->close();
$stmt->close();
$conn->close();
