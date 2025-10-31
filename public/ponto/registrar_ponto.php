<?php
include("../../BD/conexao.php");
include_once("../../BD/conexao.php");

session_start();
$id_usuario = $_SESSION['id_usuario'] ?? 0;

$stmt = $conn->prepare(
    "INSERT INTO ponto
    (id_usuario,
    id_aprovador,
    data_ponto,
    hora_entrada,
    hora_saida,
    hora_almoco_saida,
    hora_almoco_retorno,
    observacao,
    status,
    data_aprovacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("iisssssssss", $id_usuario, $id_aprovador, $data_ponto, $hora_entrada, $hora_saida, $hora_almoco_saida, $hora_almoco_retorno, $observacao, $status, $data_aprovacao);
$stmt->execute();

echo "<p>Ponto registrado com sucesso!</p>";