<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");

$idUsuario = 0;
$jornadaMinutos = 8 * 60;
$inicioNoturno = strtotime("22:00:00");
$fimNoturno = strtotime("05:00:00");

function verificar_feriado($conn, $data) {
    $stmt = $conn->prepare("SELECT 1 FROM feriados WHERE data = ?");
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows > 0;
}

$stmt = $conn->prepare("
SELECT id_ponto, id_usuario, data_ponto, hora_entrada, hora_saida
FROM ponto
WHERE id_usuario = ? 
AND hora_entrada IS NOT NULL 
AND hora_saida IS NOT NULL
");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

while ($linha = $result->fetch_assoc()) {
    $entrada = strtotime($linha['hora_entrada']);
    $saida = strtotime($linha['hora_saida']);
    $dataPonto = $linha['data_ponto'];

    if ($saida <= $entrada) {
        continue;
    }

    // duração total
    $duracaoTotal = ($saida - $entrada) / 60;

    if ($duracaoTotal <= $jornadaMinutos) {
        continue;
    }

    // minutos de hora extra
    $minutosExtra = $duracaoTotal - $jornadaMinutos;

    // Definir feriado
    $feriado = verificar_feriado($conn, $dataPonto);
    $tipoBase = $feriado ? 'hf' : 'he';

    // Calcular frações diurna e noturna
    // 86400 é a mesma coisa que um dia
    $noiteIni = strtotime("22:00:00");
    $noiteFim = strtotime("05:00:00") + 86400; // 5h do dia seguinte
    $entradaAbsoluta = $entrada;
    $saidaAbsoluta = $saida < $entrada ? $saida + 86400 : $saida;

    // Interseções com o período noturno
    $inicio = max($entradaAbsoluta, $noiteIni);
    $fim = min($saidaAbsoluta, $noiteFim);
    $duracaoNoturna = max(0, ($fim - $inicio) / 60);

    if ($duracaoNoturna > $minutosExtra) {
        $duracaoNoturna = $minutosExtra; // segurança: não exceder total
    }

    $duracaoDiurna = $minutosExtra - $duracaoNoturna;

    // Inserções: separa hora extra diurna e noturna
    if ($duracaoDiurna > 0) {
        $tipo = "dia_$tipoBase";
        $ins = $conn->prepare("
            INSERT INTO horas_extras (id_usuario, data, tipo, minutos)
            VALUES (?, ?, ?, ?)
        ");
        $ins->bind_param("issi", $linha['id_usuario'], $dataPonto, $tipo, $duracaoDiurna);
        $ins->execute();
    }

    if ($duracaoNoturna > 0) {
        $tipo = "noite_$tipoBase";
        $ins = $conn->prepare("
            INSERT INTO horas_extras (id_usuario, data, tipo, minutos)
            VALUES (?, ?, ?, ?)
        ");
        $ins->bind_param("issi", $linha['id_usuario'], $dataPonto, $tipo, $duracaoNoturna);
        $ins->execute();
    }
}