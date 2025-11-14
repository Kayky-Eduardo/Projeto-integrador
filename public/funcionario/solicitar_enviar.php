<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

$id_usuario = $_SESSION['id_usuario'];
$id_ponto = intval($_POST['id_ponto']);
$campo = $_POST['campo'];
$valor_novo = $_POST['valor_novo'];
$motivo = $_POST['motivo'];

$q = $conn->prepare("SELECT $campo FROM ponto_dia WHERE id_ponto = ?");
$q->bind_param("i", $id_ponto);
$q->execute();
$row = $q->get_result()->fetch_row();
$valor_antigo = $row[0];

$ins = $conn->prepare("
    INSERT INTO ajustes_ponto (id_ponto, id_usuario, campo, valor_antigo, valor_novo, motivo)
    VALUES (?, ?, ?, ?, ?, ?)
");

$ins->bind_param("iissss", $id_ponto, $id_usuario, $campo, $valor_antigo, $valor_novo, $motivo);

if ($ins->execute()) {
    echo "Solicitação enviada!";
} else {
    echo "Erro ao solicitar.";
}
