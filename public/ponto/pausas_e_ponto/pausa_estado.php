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
    'almoco_usado' => false,
    'tipos_comuns' => []
];

// 1) verificar se existe pausa aberta
$sql = "SELECT p.id_pausa, p.id_config, p.inicio, pc.descricao_pausa, pc.is_almoco 
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
    $response['tipo'] = $row['is_almoco'] ? 'almoco' : 'pausa';
    $response['descricao_aberta'] = $row['descricao_pausa'];
    $response['inicio'] = date('H:i:s', strtotime($row['inicio']));
}

// 2) verificar se almoço já foi usado hoje
$hoje = date('Y-m-d');
$sql = "SELECT COUNT(*) as cnt FROM pausa p JOIN pausa_config pc ON pc.id_config = p.id_config
        WHERE p.id_usuario = ? AND p.data = ? AND pc.is_almoco = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_usuario, $hoje);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$response['almoco_usado'] = ($row['cnt'] > 0);

// 3) carregar tipos comuns e marcar se já usados hoje
$sql = "SELECT id_config, descricao_pausa FROM pausa_config WHERE is_almoco = 0 ORDER BY id_config";
$res = $conn->query($sql);
while ($t = $res->fetch_assoc()) {
    $idc = $t['id_config'];
    // Removido bloqueio por uso diário para pausas comuns: sempre permitir.
    $used = false; // Manter false para indicar que a pausa pode ser usada

    $response['tipos_comuns'][] = [
        'id_config' => $idc,
        'descricao_pausa' => $t['descricao_pausa'],
        'usado' => $used
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;