<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'];

// Resultado padrão
$response = [
    'aberta' => false,
    'tipo' => null,
    'descricao_aberta' => null,
    'inicio' => null,
    'tipos_comuns' => []
];

// 1) verificar se existe pausa aberta
$sql = "SELECT p.id_pausa, p.id_config, p.inicio, pc.descricao_pausa 
        FROM pausa p
        JOIN pausa_config pc ON pc.id_config = p.id_config
        WHERE p.id_usuario = ? AND p.fim IS NULL
        ORDER BY p.id_pausa DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $response['aberta'] = true;
    $response['tipo'] = 'pausa';
    $response['descricao_aberta'] = $row['descricao_pausa'];
    $response['inicio'] = date('H:i:s', strtotime($row['inicio']));
}
// 2) carregar todos os tipos de pausa (inclui almoço como pausa comum)
// 3) carregar todos os tipos de pausa (inclui almoço como pausa comum)
$sql = "SELECT id_config, descricao_pausa, limite_pausa_diario FROM pausa_config ORDER BY id_config";
$res = $conn->query($sql);
$hoje = date('Y-m-d');
while ($t = $res->fetch_assoc()) {
    $idc = $t['id_config'];
    $limite = intval($t['limite_pausa_diario']);

    // calcula quantas pausas desse tipo o usuário já registrou hoje
    $used = false;
    if ($limite > 0) {
        $stmt2 = $conn->prepare("SELECT COUNT(*) as cnt FROM pausa WHERE id_usuario = ? AND data = ? AND id_config = ?");
        $stmt2->bind_param("isi", $id_usuario, $hoje, $idc);
        $stmt2->execute();
        $cnt = $stmt2->get_result()->fetch_assoc()['cnt'] ?? 0;
        if ($cnt >= $limite) $used = true;
    }

    $response['tipos_comuns'][] = [
        'id_config' => $idc,
        'descricao_pausa' => $t['descricao_pausa'],
        'usado' => $used,
        'limite' => $limite
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;