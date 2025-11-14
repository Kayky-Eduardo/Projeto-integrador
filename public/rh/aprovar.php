<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

$id = intval($_GET['id']);
$id_rh = $_SESSION['id_usuario'];

$q = $conn->prepare("SELECT * FROM ajustes_ponto WHERE id_ajuste = ?");
$q->bind_param("i", $id);
$q->execute();
$aj = $q->get_result()->fetch_assoc();

$campo = $aj['campo'];
$valor = $aj['valor_novo'];
$id_ponto = $aj['id_ponto'];

$up = $conn->prepare("UPDATE ponto_dia SET $campo = ? WHERE id_ponto = ?");
$up->bind_param("si", $valor, $id_ponto);
$up->execute();

$fin = $conn->prepare("
UPDATE ajustes_ponto SET status='Aprovado', data_resposta=NOW(), id_rh=?
WHERE id_ajuste = ?
");
$fin->bind_param("ii", $id_rh, $id);
$fin->execute();

header("Location: ajustes.php");
exit;
