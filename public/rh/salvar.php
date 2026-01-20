<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// ==================
// CONTROLE DE ACESSO
// ==================
if ($_SESSION['nivel'] < 3) {
    die("Acesso restrito.");
}

// ===================
// DADOS DO FORMULÁRIO
// ===================
$id_ajuste = intval($_POST['id_ajuste'] ?? 0);
$id_ponto  = intval($_POST['id_ponto'] ?? 0);

$entrada_time       = $_POST['inicio_ponto']   ?? null;
$inicio_almoco_time = $_POST['inicio_almoco']  ?? null;
$fim_almoco_time    = $_POST['fim_almoco']     ?? null;
$saida_time         = $_POST['fim_ponto']      ?? null;
$motivo             = trim($_POST['motivo']    ?? '');

// ====================
// BUSCAR DATA DO PONTO
// ====================
$q = $conn->prepare("SELECT id_usuario, data_reg FROM ponto_dia WHERE id_ponto = ?");
$q->bind_param("i", $id_ponto);
$q->execute();
$ponto = $q->get_result()->fetch_assoc();

if (!$ponto) {
    die("Ponto não encontrado.");
}

$data = $ponto['data_reg'];
$id_usuario = $ponto['id_usuario'];

// ===========================
// FUNÇÃO PARA MONTAR DATETIME
// ===========================
function montarDateTime($data, $hora)
{
    if (empty($hora)) return null;
    return $data . ' ' . $hora . ':00';
}

$entrada       = montarDateTime($data, $entrada_time);
$inicio_almoco = montarDateTime($data, $inicio_almoco_time);
$fim_almoco    = montarDateTime($data, $fim_almoco_time);
$saida         = montarDateTime($data, $saida_time);

// ========================
// ATUALIZA SOMENTE O PONTO
// ========================
// Não apaga registros
// Atualiza APENAS esse ponto
$stmt = $conn->prepare("
    UPDATE ponto_dia SET 
        inicio_ponto  = ?,
        inicio_almoco = ?,
        fim_almoco    = ?,
        fim_ponto     = ?,
        status        = 'Finalizado'
    WHERE id_ponto = ?
");

$stmt->bind_param("ssssi", 
    $entrada, 
    $inicio_almoco, 
    $fim_almoco, 
    $saida,
    $id_ponto
);
$stmt->execute();

// ===========================
// MARCAR AJUSTE COMO APROVADO
// ===========================
$stmt = $conn->prepare("
    UPDATE ajustes_ponto 
    SET status='Aprovado', motivo=?, data_resposta=NOW(), id_rh=?
    WHERE id_ajuste=?
");

$stmt->bind_param("sii", $motivo, $_SESSION['id_usuario'], $id_ajuste);
$stmt->execute();

// ==========================
// NOTIFICAÇÃO AO FUNCIONÁRIO
// ==========================
$stmt = $conn->prepare("
    INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem, data_notificacao) 
    VALUES (?, ?, 'Seu ajuste foi aprovado e seu ponto foi atualizado.', NOW())
");

$stmt->bind_param("ii", $id_usuario, $id_ponto);
$stmt->execute();

// ============
// REDIRECIONAR
// ============
header("Location: ajustes_pendentes.php");
exit;
