<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require_once __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    echo "Dados inválidos";
    exit;
}

$data = date("Y-m-d");
$hora = date("H:i:s");

// ------------------ LÓGICA DE PONTO ------------------ //

// Buscar registro do dia
$sql = "SELECT * FROM ponto_dia WHERE id_usuario = ? AND data_ponto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_usuario, $data);
$stmt->execute();
$res = $stmt->get_result();
$registro = $res->fetch_assoc();

// Se ainda não existe registro para o dia → criar agora
if (!$registro) {
    $sql = "INSERT INTO ponto_dia (id_usuario, data_ponto) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_usuario, $data);
    $stmt->execute();

    // Recapturar o registro recém-criado
    $registro = [
        "id_ponto" => $conn->insert_id,
        "inicio_ponto" => null,
        "fim_ponto" => null
    ];
}

// lógica de alternância automática
if (empty($registro['inicio_ponto'])) {
    $coluna = "inicio_ponto";
    $acao = "Entrada registrada";
}
elseif (empty($registro['fim_ponto'])) {
    $coluna = "fim_ponto";
    $acao = "Saída registrada";
}
else {
    echo "ponto já finalizado hoje.";
    exit;
}

// executa o registro na tabela ponto
$sql = "UPDATE ponto_dia SET " . $coluna . " = ? WHERE id_ponto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $hora, $registro['id_ponto']);

if ($stmt->execute()) {
    echo $acao;
} else {
    echo "Erro ao registrar: " . $stmt->error;
}
exit;
?>