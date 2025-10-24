<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");

$pesquisa_trabalhando = $conn->execute("
    SELECT COUNT(*) AS total
    FROM ponto
    WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL OR fim_ponto = '')");
$pesquisa_trabalhando->get_result();
$numero_presente = $pesquisa_trabalhando->num_rows;

// $pesquisa_pausa = $conn->excute("
//     SELECT * FROM ponto WHERE inicio_almoco IS NOT NULL AND (fim_almoco IS NULL OR fim_almoco = '');
// ");
// $pesquisa_pausa->get_result();
// $numero_em_pausa =  $pesquisa_pausa->num_rows; 

$valores = [$numero_presente]; // $numero_em_pausa];
echo json_encode($valores);
?>