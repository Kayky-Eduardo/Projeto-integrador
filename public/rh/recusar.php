<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

$id = intval($_GET['id']);
$id_rh = $_SESSION['id_usuario'];

$up = $conn->prepare("
UPDATE ajustes_ponto SET status='Recusado', data_resposta=NOW(), id_rh=?
WHERE id_ajuste = ?
");
$up->bind_param("ii", $id_rh, $id);
$up->execute();

header("Location: ajustes.php");
exit;
