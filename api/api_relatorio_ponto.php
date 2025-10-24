<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");

$pesquisa_trabalhando = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM ponto
    WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL OR fim_ponto = '' or fim_ponto = '00000-00-00 00:00:00')"
);
$pesquisa_trabalhando->execute();
$result = $pesquisa_trabalhando->get_result();
if ($linha = $result->fetch_assoc()) {
    $numero_presente = $linha['total'];
} else {
    $numero_presente = 0;
}

$pesquisa_ausentes = $conn->prepare("
    SELECT COUNT(*) AS total_usuarios
    FROM usuario
");
$pesquisa_ausentes->execute();
$result = $pesquisa_ausentes->get_result();
if ($linha = $result->fetch_assoc()) {
    $numero_ausentes = (int)$linha['total_usuarios'] - (int)$numero_presente;
} else {
    $numero_ausentes = 0;
}

// $pesquisa_pausa = $conn->excute("
//     SELECT * FROM ponto WHERE inicio_almoco IS NOT NULL AND (fim_almoco IS NULL OR fim_almoco = '');
// ");
// $pesquisa_pausa->get_result();
// $numero_em_pausa =  $pesquisa_pausa->num_rows; 

$valores = [$numero_presente, $numero_ausentes]; // $numero_em_pausa];
echo json_encode($valores);
?>