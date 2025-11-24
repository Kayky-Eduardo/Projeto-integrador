<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$id = $_GET['id'] ?? 0;

$conn->query("
    UPDATE ajustes_ponto 
    SET status='Recusado', data_resposta=NOW(), id_rh={$_SESSION['id_usuario']}
    WHERE id_ajuste=$id
");

$aj = $conn->query("SELECT * FROM ajustes_ponto WHERE id_ajuste=$id")->fetch_assoc();

$conn->query("
    INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem)
    VALUES ({$aj['id_usuario']}, {$aj['id_ponto']}, 'Seu ajuste de ponto foi recusado.')
");

header("Location: ajustes_pendentes.php");
exit;
?>
