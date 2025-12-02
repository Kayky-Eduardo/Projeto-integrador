<?php


function adicionar_horas($conn, $id_usuario, $minutos, $tipo) {
    $stmt_saldo = $conn->prepare("
    SELECT
        saldo_minutos
        ultima_atualizacao
    FROM banco_horas
    WHERE id_usuario = ?
    ");
    $stmt_saldo->bind_param('i', $id_usuario);
    $stmt_saldo->execute();
    $result_verificacao = $stmt->get_result();

    if ($result_verificacao->num_rows === 0) {
        $stmt_criar_banco = $conn->prepare("
        INSERT INTO banco_horas(id_usuario, saldo_minutos)
        VALUES (?, ?);
        ");
        $stmt_criar_banco->bind_param('ii', $id_usuario, $minutos);
        $stmt_criar_banco->execute();
    } else {
        $stmt_update_banco = $conn->prepare("
        UPDATE banco_horas
        SET saldo_minutos = ?
        WHERE id_banco = ?;
        ");
        $stmt_update_banco->bind_param('ii', $minutos);
    }


}

function get_banco_horas($conn, $id_usuario) {

}

function retirar_banco_horas($conn, $id_usuario) {

}