<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'] ?? null;
$pausa = $_POST['pausa'] ?? null;       // 'ponto', 'almoco' ou 'pausa'
$tipo_pausa = $_POST['tipo'] ?? null;  // identifica o tipo de pausa comum

if (!$id_usuario || !$pausa) {
    echo "Dados inválidos";
    exit;
}

$data = date("Y-m-d");
$hora = date("H:i:s");

// ------------------ LÓGICA EXISTENTE DE PONTO / ALMOÇO ------------------ //
if ($pausa === "ponto" || $pausa === "almoco") {
    // Buscar registro do dia
    $sql = "SELECT * FROM ponto WHERE id_usuario = ? AND data_ponto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_usuario, $data);
    $stmt->execute();
    $res = $stmt->get_result();
    $registro = $res->fetch_assoc();

    // Se ainda não existe registro para o dia → criar agora
    if (!$registro) {
        $sql = "INSERT INTO ponto (id_usuario, data_ponto) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id_usuario, $data);
        $stmt->execute();

        // Recapturar o registro recém-criado
        $registro = [
            "id_ponto" => $conn->insert_id,
            "hora_entrada" => null,
            "hora_saida" => null,
            "hora_almoco_saida" => null,
            "hora_almoco_retorno" => null
        ];
    }

    // lógica de alternância automática
    if ($pausa === "ponto") {

        if (empty($registro['hora_entrada'])) {
            $coluna = "hora_entrada";
            $acao = "Entrada registrada";
        }
        elseif (empty($registro['hora_saida'])) {
            $coluna = "hora_saida";
            $acao = "Saída registrada";
        }
        else {
            echo "ponto já finalizado hoje.";
            exit;
        }
    }

    elseif ($pausa === "almoco") {

        if (empty($registro['hora_almoco_saida'])) {
            $coluna = "hora_almoco_saida";
            $acao = "Saída para almoço registrada";
        }
        elseif (empty($registro['hora_almoco_retorno'])) {
            $coluna = "hora_almoco_retorno";
            $acao = "Retorno do almoço registrado";
        }
        else {
            echo "Pausa de almoço já concluída.";
            exit;
        }
    }

    // executa o registro na tabela ponto
    $sql = "UPDATE ponto SET $coluna = ? WHERE id_ponto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hora, $registro['id_ponto']);

    if ($stmt->execute()) echo $acao;
    else echo "Erro ao registrar";
    exit; // interrompe para não cair na lógica de pausas
}

// verifica se é pausa comum
if ($pausa === "pausa") {
    if (!$tipo_pausa) {
        echo "Tipo de pausa não informado.";
        exit;
    }
}

// se chegou aqui: valor inválido
echo "Pausa inválida";
exit;
