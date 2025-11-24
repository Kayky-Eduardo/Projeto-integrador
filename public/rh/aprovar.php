<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$id = $_GET['id'] ?? 0;

$aj = $conn->query("SELECT * FROM ajustes_ponto WHERE id_ajuste = $id")->fetch_assoc();

if (!$aj) {
    die("Ajuste não encontrado.");
}

$campo = $aj['campo'];
$valor = $aj['valor_novo'];

// Mapeamento correto para coluna do banco
$mapa = [
    'inicio_ponto' => 'entrada',
    'inicio_almoco' => 'inicio_almoco',
    'fim_almoco' => 'fim_almoco',
    'fim_ponto' => 'saida'
];

if (!isset($mapa[$campo])) {
    die("Campo inválido recebido: $campo");
}

$campo_corrigido = $mapa[$campo];

$conn->query("
    UPDATE ponto_dia 
    SET $campo_corrigido = '$valor' 
    WHERE id_ponto = {$aj['id_ponto']}
");

$conn->query("
    UPDATE ajustes_ponto 
    SET status='Aprovado', data_resposta=NOW(), id_rh={$_SESSION['id_usuario']}
    WHERE id_ajuste=$id
");

$conn->query("
    INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem)
    VALUES ({$aj['id_usuario']}, {$aj['id_ponto']}, 'Seu ajuste de ponto foi aprovado.')
");

header("Location: ajustes.php");
exit;
