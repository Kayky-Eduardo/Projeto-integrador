<?php
session_start();
require "../../include/verificacao.php";
include "../../BD/conexao.php";
verificar_login($conn);

/* VALIDA ID */
if (!isset($_POST['id_usuario']) || empty($_POST['id_usuario'])) {
    header("Location: lista.php");
    exit;
}

$id = intval($_POST['id_usuario']);

/* IMPEDE EXCLUIR A SI MESMO */
if ($id === $_SESSION['id_usuario']) {
    $_SESSION['erros'][] = "Você não pode excluir a si mesmo.";
    header("Location: editar.php?id=$id");
    exit;
}

/* VERIFICA BANCO DE HORAS */
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total 
     FROM banco_horas_historico 
     WHERE id_usuario = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_assoc();
$stmt->close();

if ($dados['total'] > 0) {
    $_SESSION['erros'][] = "Não é possível excluir, pois já existem registros deste usuário!";
    header("Location: editar.php?id=$id");
    exit;
}

/* EXCLUI LOGIN */
$stmt = $conn->prepare("DELETE FROM login WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* EXCLUI USUÁRIO  */
$stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: lista.php");
} else {
    $_SESSION['erros'][] = "Usuário não encontrado.";
    header("Location: lista.php");
}

$stmt->close();
$conn->close();
