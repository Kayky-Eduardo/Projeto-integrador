<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

if ($_SESSION['nivel'] < 2) die("Acesso restrito.");

$id = intval($_GET['id'] ?? 0);

// Busca ajuste
$stmt = $conn->prepare("SELECT * FROM ajustes_ponto WHERE id_ajuste = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$aj = $stmt->get_result()->fetch_assoc();

if (!$aj) die("Ajuste não encontrado.");

$campo = $aj['campo'];
$valor = $aj['valor_novo'];

// 🔒 segurança: só permite campos válidos
$permitidos = ['inicio_ponto', 'inicio_almoco', 'fim_almoco', 'fim_ponto'];
if (!in_array($campo, $permitidos)) {
    die("Campo inválido.");
}

// ✅ Atualiza SOMENTE o campo solicitado
$stmt = $conn->prepare("UPDATE ponto_dia SET $campo = ? WHERE id_ponto = ?");
$stmt->bind_param("si", $valor, $aj['id_ponto']);
$stmt->execute();

// Marca ajuste como aprovado
$stmt = $conn->prepare("
    UPDATE ajustes_ponto
    SET status='Aprovado', data_resposta=NOW(), id_rh=?
    WHERE id_ajuste=?
");
$stmt->bind_param("ii", $_SESSION['id_usuario'], $id);
$stmt->execute();

// Notificação
$stmt = $conn->prepare("
    INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem)
    VALUES (?, ?, 'Seu ajuste foi aprovado.')
");
$stmt->bind_param("ii", $aj['id_usuario'], $aj['id_ponto']);
$stmt->execute();

// ✅ Recalcula status após ajuste
function statusAuto($conn, $id_ponto)
{
    $q = $conn->prepare("SELECT inicio_ponto, inicio_almoco, fim_almoco, fim_ponto FROM ponto_dia WHERE id_ponto=?");
    $q->bind_param("i", $id_ponto);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();

    return (
        $r['inicio_ponto'] && $r['inicio_almoco'] &&
        $r['fim_almoco'] && $r['fim_ponto']
    ) ? 'Finalizado' : 'Em Andamento';
}

$novo = statusAuto($conn, $aj['id_ponto']);
$up = $conn->prepare("UPDATE ponto_dia SET status=? WHERE id_ponto=?");
$up->bind_param("si", $novo, $aj['id_ponto']);
$up->execute();

header("Location: ajustes_pendentes.php");
exit;
