<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// ID do ajuste
$id = $_GET['id'] ?? 0;

// Verifica se o ajuste existe
$busca = $conn->query("SELECT * FROM ajustes_ponto WHERE id_ajuste = $id");

if ($busca && $busca->num_rows > 0) {

    // Pega os dados do ajuste
    $aj = $busca->fetch_assoc();

    // Retorna o ponto para status Finalizado
    $conn->query("
        UPDATE ponto_dia
        SET status = 'Finalizado'
        WHERE id_ponto = {$aj['id_ponto']}
    ");

    // Remove o ajuste da lista de pendentes
    $conn->query("
        DELETE FROM ajustes_ponto
        WHERE id_ajuste = $id
    ");

    // Registra notificação para o usuário
    $conn->query("
        INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem)
        VALUES ({$aj['id_usuario']}, {$aj['id_ponto']},
        'Seu ajuste de ponto foi analisado e o status foi mantido como Finalizado.')
    ");
}

// Redireciona de volta para a lista
header("Location: ajustes_pendentes.php");
exit;
