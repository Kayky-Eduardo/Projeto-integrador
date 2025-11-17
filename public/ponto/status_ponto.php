<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$id_usuario = $_SESSION['id_usuario'];
$data = date("Y-m-d");

$sql = "SELECT * FROM ponto WHERE id_usuario = ? AND data_ponto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_usuario, $data);
$stmt->execute();
$res = $stmt->get_result();
$registro = $res->fetch_assoc();

$status = [
    "entrada" => false,
    "saida" => false,
    "almoco_saida" => false,
    "almoco_retorno" => false
];

// se existe registro, preencher status
if ($registro) {
    $status["entrada"] = !empty($registro['hora_entrada']);
    $status["saida"] = !empty($registro['hora_saida']);
    $status["almoco_saida"] = !empty($registro['hora_almoco_saida']);
    $status["almoco_retorno"] = !empty($registro['hora_almoco_retorno']);
}

header('Content-Type: application/json');
echo json_encode($status);