<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// ==================
// CONTROLE DE ACESSO
// ==================
if ($_SESSION['nivel'] >= 2) {
    // Permissão concedida
} else{
    die("Acesso restrito.");
}

// ===================
// DADOS DO FORMULÁRIO
// ===================
$id_ajuste = intval($_POST['id_ajuste'] ?? 0);
$id_ponto  = intval($_POST['id_ponto'] ?? 0);
$id_pausa  = intval($_POST['id_pausa'] ?? 0);
$campo_editavel = $_POST['campo'] ?? '';

$entrada_time       = $_POST['inicio_ponto']   ?? null;
$inicio_pausa_time = $_POST['inicio_pausa']  ?? null;
$fim_pausa_time    = $_POST['fim_pausa']     ?? null;
$saida_time         = $_POST['fim_ponto']      ?? null;
$motivo             = trim($_POST['motivo']    ?? '');

// ====================
// BUSCAR DATA DO PONTO
// ====================
$q = $conn->prepare("SELECT id_usuario, data_ponto FROM ponto_dia WHERE id_ponto = ?");
$q->bind_param("i", $id_ponto);
$q->execute();
$ponto = $q->get_result()->fetch_assoc();

if (!$ponto) {
    die("Ponto não encontrado.");
}

$data = $ponto['data_ponto'];
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
$inicio_pausa = montarDateTime($data, $inicio_pausa_time);
$fim_pausa    = montarDateTime($data, $fim_pausa_time);
$saida         = montarDateTime($data, $saida_time);

// Início do código do Davi

// 1. Atualiza os horários principais do ponto
$stmt1 = $conn->prepare("UPDATE ponto_dia SET inicio_ponto = ?, fim_ponto = ?, status = 'Finalizado' WHERE id_ponto = ?");
$stmt1->bind_param("ssi", $entrada, $saida, $id_ponto);
$stmt1->execute();

// 2. Se houver ID de pausa, atualiza a pausa também (Sem exclusividade lógica)
if ($id_pausa > 0) {
    $stmt2 = $conn->prepare("UPDATE pausa SET inicio = ?, fim = ? WHERE id_pausa = ?");
    $stmt2->bind_param("ssi", $inicio_pausa, $fim_pausa, $id_pausa);
    $stmt2->execute();
}

// 3. Marca o ajuste como APROVADO
$stmt3 = $conn->prepare("UPDATE ajustes_ponto SET status='Aprovado', motivo=?, data_resposta=NOW(), id_rh=? WHERE id_ajuste=?");
$stmt3->bind_param("sii", $motivo, $_SESSION['id_usuario'], $id_ajuste);
$stmt3->execute();

// 4. Notificação
$stmt4 = $conn->prepare("INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem, data_notificacao) VALUES (?, ?, 'Seu ajuste foi aprovado.', NOW())");
$stmt4->bind_param("ii", $id_usuario, $id_ponto);
$stmt4->execute();

// ============
// REDIRECIONAR
// ============
header("Location: ajustes_pendentes.php");
exit;
