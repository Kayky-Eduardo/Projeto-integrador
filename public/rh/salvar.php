<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// Somente RH / Admin
if ($_SESSION['nivel'] < 2) die("Acesso restrito.");

$id_ajuste = intval($_POST['id_ajuste']);
$id_ponto  = intval($_POST['id_ponto']);

$entrada_time       = $_POST['inicio_ponto'];
$inicio_almoco_time = $_POST['inicio_almoco'];
$fim_almoco_time    = $_POST['fim_almoco'];
$saida_time         = $_POST['fim_ponto'];
$motivo             = $_POST['motivo'];

// ---------------------------------------------
// 1. Buscar a data do ponto (obrigatório p/ DATETIME)
// ---------------------------------------------
$q = $conn->prepare("SELECT id_usuario, data_reg FROM ponto_dia WHERE id_ponto = ?");
$q->bind_param("i", $id_ponto);
$q->execute();
$ponto = $q->get_result()->fetch_assoc();

if (!$ponto) {
    die("Ponto não encontrado.");
}

$data = $ponto['data_reg'];     // YYYY-MM-DD
$id_usuario = $ponto['id_usuario'];

// ---------------------------------------------
// 2. Montar horários completos no formato DATETIME
// ---------------------------------------------
function montarDateTime($data, $hora)
{
    if (empty($hora)) return null;
    return $data . ' ' . $hora . ':00';
}

$entrada       = montarDateTime($data, $entrada_time);
$inicio_almoco = montarDateTime($data, $inicio_almoco_time);
$fim_almoco    = montarDateTime($data, $fim_almoco_time);
$saida         = montarDateTime($data, $saida_time);

// ---------------------------------------------
// 3. Atualizar tabela ponto_dia
// ---------------------------------------------
$stmt = $conn->prepare("
    UPDATE ponto_dia SET 
        inicio_ponto   = ?,
        inicio_almoco  = ?,
        fim_almoco     = ?,
        fim_ponto      = ?,
        status         = 'Finalizado'
    WHERE id_ponto = ?
");

$stmt->bind_param("ssssi", $entrada, $inicio_almoco, $fim_almoco, $saida, $id_ponto);
$stmt->execute();

// ---------------------------------------------
// 4. Atualizar status do ajuste
// ---------------------------------------------
$stmt = $conn->prepare("
    UPDATE ajustes_ponto 
    SET status='Aprovado', motivo=?, data_resposta=NOW(), id_rh=?
    WHERE id_ajuste=?
");

$stmt->bind_param("sii", $motivo, $_SESSION['id_usuario'], $id_ajuste);
$stmt->execute();

// ---------------------------------------------
// 5. Criar notificação para o funcionário
// ---------------------------------------------
$stmt = $conn->prepare("
    INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem, data_notificacao) 
    VALUES (?, ?, 'Seu ponto foi ajustado e aprovado pelo RH.', NOW())
");
$stmt->bind_param("ii", $id_usuario, $id_ponto);
$stmt->execute();

// ---------------------------------------------
// 6. Redirecionar
// ---------------------------------------------
header("Location: ajustes_pendentes.php");
exit;
