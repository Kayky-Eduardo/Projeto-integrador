<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$id_usuario = $_SESSION['id_usuario'] ?? null;  
$evento = $_POST['evento'] ?? null;
$hora = $_POST['hora'] ?? null;

if (!$evento || !$hora) {
    echo "Dados inválidos";
    exit;
}

$data = date("Y-m-d");

// Mapeia evento → coluna da tabela
$map = [
    'entrada' => 'hora_entrada',
    'saida' => 'hora_saida',
    'almoco_saida' => 'hora_almoco_saida',
    'almoco_retorno' => 'hora_almoco_retorno'
];

if (!isset($map[$evento])) {
    echo "Evento inválido";
    exit;
}

$coluna = $map[$evento];

// Verifica se já existe ponto do dia
$sql = "SELECT * FROM ponto WHERE id_usuario = ? AND data_ponto = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Erro no prepare: " . $conn->error;
    exit;
}

$stmt->bind_param("is", $id_usuario, $data);
$stmt->execute();
$result = $stmt->get_result();
$registro = $result->fetch_assoc();


// Se não existe da INSERT
if (!$registro) {

    $sql = "INSERT INTO ponto (id_usuario, data_ponto, $coluna)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "Erro no prepare (insert): " . $conn->error;
        exit;
    }

    $stmt->bind_param("iss", $id_usuario, $data, $hora);

    if ($stmt->execute()) echo "Registrado (insert)";
    else echo "Erro no insert";

    exit;
}


// não permite sobrescrever um evento já registrado
if (!is_null($registro[$coluna])) {
    echo "Evento já registrado";
    exit;
}

// UPDATE SOMENTE da coluna referente ao evento
$sql = "UPDATE ponto SET $coluna = ? WHERE id_ponto = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Erro no prepare (update): " . $conn->error;
    exit;
}

$stmt->bind_param("si", $hora, $registro['id_ponto']);

if ($stmt->execute()) echo "Registrado (update)";
else echo "Erro no update";
