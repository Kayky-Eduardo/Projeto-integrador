<?php
include_once("../../BD/conexao.php");

// Função: listar todos os pontos com filtro opcional
function listarPontos($conn, $status = null) {
    $filtro = "";
    if ($status) {
        $filtro = "WHERE p.status = '" . $conn->real_escape_string($status) . "'";
    }

    $sql = "SELECT 
                p.id_ponto, u.nome_usuario, p.data_ponto,
                p.hora_entrada, p.hora_almoco_saida, p.hora_almoco_retorno, p.hora_saida,
                p.status
            FROM ponto p
            JOIN usuario u ON p.id_usuario = u.id_usuario
            $filtro
            ORDER BY p.data_ponto DESC";
    return $conn->query($sql);
}

// Função: buscar ponto por ID
function buscarPonto($conn, $id) {
    $sql = "SELECT p.*, u.nome_usuario 
            FROM ponto p 
            JOIN usuario u ON p.id_usuario = u.id_usuario 
            WHERE p.id_ponto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Função: atualizar registro de ponto
function atualizarPonto($conn, $dados) {
    $sql = "UPDATE ponto SET 
                hora_entrada=?, hora_almoco_saida=?, hora_almoco_retorno=?, 
                hora_saida=?, observacao=? 
            WHERE id_ponto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssi",
        $dados['hora_entrada'],
        $dados['hora_almoco_saida'],
        $dados['hora_almoco_retorno'],
        $dados['hora_saida'],
        $dados['observacao'],
        $dados['id_ponto']
    );
    return $stmt->execute();
}

// Função: aprovar ou rejeitar ponto
function atualizarStatusPonto($conn, $id_ponto, $status, $id_rh) {
    $sql = "UPDATE ponto SET status=?, aprovado_por=?, data_aprovacao=NOW() WHERE id_ponto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $id_rh, $id_ponto);
    return $stmt->execute();
}
?>
