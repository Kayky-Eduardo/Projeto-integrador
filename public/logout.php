<?php
session_start();
include("../BD/conexao.php");
include_once("../include/verificacao.php");
date_default_timezone_set('America/Sao_Paulo');
$data = new datetime();
$agora = $data->format('Y-m-d H:i:s');

if (isset($_SESSION['id_usuario'], $_SESSION['id_login'])) {
    $id_usuario = $_SESSION['id_usuario'];
    $id_login = $_SESSION['id_login'];
}

if (isset($id_login)) {
    $update = $conn->prepare("UPDATE login SET data_fim = ? WHERE id_login = ?");
    $update->bind_param("si", $agora, $id_login);
    $update->execute();
}

session_unset();
session_destroy();

// Redireciona para a página de login
header("Location: login.php");
exit();
?>