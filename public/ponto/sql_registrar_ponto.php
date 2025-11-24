<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$id_usuario = $_SESSION['id_usuario'] ?? null;
$pausa = $_POST['pausa'] ?? null;       // 'ponto', 'almoco' ou 'pausa'
$tipo_pausa = $_POST['tipo'] ?? null;  // quando pausa === 'pausa'

if (!$id_usuario || !$pausa) {
    echo "Dados inválidos";
    exit;
}

$data = date("Y-m-d");
$hora = date("H:i:s");

// ------------------ LÓGICA EXISTENTE DE PONTO / ALMOÇO (preservada) ------------------
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

    // lógica de alternância automática (mesma que você tinha)
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

// ------------------ LÓGICA DE PAUSAS (tabela 'pausas') ------------------
if ($pausa === "pausa") {
    if (!$tipo_pausa) {
        echo "Tipo de pausa não informado.";
        exit;
    }

    // NOVA REGRA: impedir nova pausa se EXISTIR QUALQUER pausa aberta hoje (fim IS NULL)
    $sql = "SELECT * FROM pausas WHERE id_usuario = ? AND data_pausa = ? AND fim IS NULL LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_usuario, $data);
    $stmt->execute();
    $res = $stmt->get_result();
    $pausa_aberta_qualquer = $res->fetch_assoc();

    if ($pausa_aberta_qualquer) {
        // Se existe uma pausa aberta (qualquer tipo), não permita abrir outra.
        // Se o usuário clicar no botão do tipo que está aberto, podemos interpretar como "fechar".
        // Para isso, checamos se o tipo da pausa aberta é igual ao tipo enviado: se sim, fechamos; senão impedimos.
        if ($pausa_aberta_qualquer['tipo_pausa'] === $tipo_pausa) {
            // fechar a pausa atual (mesmo tipo)
            $sql = "UPDATE pausas SET fim = ? WHERE id_pausa = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hora, $pausa_aberta_qualquer['id_pausa']);
            if ($stmt->execute()) {
                echo "Pausa (" . $tipo_pausa . ") encerrada.";
            } else {
                echo "Erro ao encerrar pausa.";
            }
            exit;
        } else {
            // existe uma pausa aberta de outro tipo — bloquear abertura
            echo "Já existe uma pausa aberta (" . $pausa_aberta_qualquer['tipo_pausa'] . "). Termine-a antes de iniciar outra.";
            exit;
        }
    } else {
        // Não existe pausa aberta → inserir nova pausa com inicio
        $sql = "INSERT INTO pausas (id_usuario, data_pausa, tipo_pausa, inicio) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $id_usuario, $data, $tipo_pausa, $hora);
        if ($stmt->execute()) {
            echo "Pausa (" . $tipo_pausa . ") iniciada.";
        } else {
            echo "Erro ao iniciar pausa.";
        }
        exit;
    }
}

// se chegou aqui: valor inválido
echo "Pausa inválida";
exit;
