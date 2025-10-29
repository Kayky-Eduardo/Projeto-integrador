<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");


// realizando as pesquisas do status do funcionário
// no banco de dados
$pesquisa_trabalhando = $conn->prepare("
    SELECT COUNT(*) AS total_trabalhando
    FROM ponto
    WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL OR fim_ponto = '' or fim_ponto = '00000-00-00 00:00:00')"
);

$pesquisa_trabalhando->execute();
$result = $pesquisa_trabalhando->get_result();
if ($linha = $result->fetch_assoc()) {
    $numero_presente = $linha['total_trabalhando'];
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

$pesquisa_pausa = $conn->prepare("
    SELECT COUNT(*) AS total_pausa
    FROM ponto
    WHERE inicio_almoco IS NOT NULL AND (fim_almoco IS NULL OR fim_almoco = '');
");
$pesquisa_pausa->execute();
$result = $pesquisa_pausa->get_result();
if ($linha = $result->fetch_assoc()) {
    $numero_pausa = (int)$linha['total_pausa'] - (int)$numero_presente;
} else {
    $numero_pausa = 0;
}

// entregando uma array com os valores da pesquisas
$valores = [$numero_presente, $numero_ausentes, $numero_pausa];
echo json_encode($valores);
?>