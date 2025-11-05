<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");


// exemplo do que pensei(para lembrar mas tarde),
// basicamente, estou pegando estes dados da tabela com um usuario em mente.
// vou pensar o que fazer com o hora almoço, é possível fazer também o filtro no where
// de: AND status = "aprovado"; por exemplo.
// muitas possibilidades. 
// select 
// id_ponto,
// id_usuario,
// data_ponto,
// hora_entrada,
// hora_saida,
// hora_almoco_saida,
// hora_almoco_retorno
// from ponto
// where id_usuario = 3 AND hora_entrada is not null AND hora_saida is not null;

$dados_inteiros = $conn->prepare("
SELECT id_ponto, id_usuario, data_ponto, hora_entrada, hora_saida,
hora_almoco_saida, hora_almoco_retorno
FROM ponto
WHERE id_usuario = 3 AND hora_entrada is not null AND hora_saida is not null
");
$dados_inteiros->execute();
$result = $dados_inteiros->get_result();
if ($linha = $result->fetch_assoc()) {
    $entrada = strtotime($linha['hora_entrada']);
    $saida = strtotime($linha['hora_saida']);
    $noite = strtotime("18:00:00");
}
$diferença = $saida - $entrada;

$minutos = $diferença / 60;
$jornada = 8 * 60;
$horas = ($diferença / 3600);

if($minutos > $jornada) {
    if ($saida > $noite) {
        $hora_extra_noite = $minutos - $jornada;
        
    }
    $horaExtra = $minutos - $jornada;
    echo "tempo extra: " . number_format($horaExtra, 0, '.', '') . "\n";
}

// para exibição
// se não tiver 2 números, adiciona um 0 no pad selecionado
$horas = str_pad($horas, 2, "0", STR_PAD_LEFT);
$minutos = str_pad($minutos, 2, "0", STR_PAD_LEFT);
?>