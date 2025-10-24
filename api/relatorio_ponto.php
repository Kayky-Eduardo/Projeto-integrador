<?php
include(__DIR__ . "/../BD/conexao.php");

$pesquisa_trabalhando = $conn->execute("
    SELECT * FROM ponto WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL OR data_fim = '');
");
$numero_presente->get_result();
$numero_presente = $pesquisa_trabalhando->num_rows;
$pesquisa_pausa = $conn->excute("
    SELECT * FROM ponto WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL OR data_fim = '');
");
?>