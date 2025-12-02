<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// =====================
// CONTROLE DE PERMISSÃO
// =====================
// Apenas usuários nível 2 ou superior (RH / Admin)
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

// ===================
// PEGA O ID DO AJUSTE
// ===================
$id = intval($_GET['id'] ?? 0);

// =======================
// BUSCA O AJUSTE NO BANCO
// =======================
$stmt = $conn->prepare("SELECT * FROM ajustes_ponto WHERE id_ajuste = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$aj = $stmt->get_result()->fetch_assoc();

// Se não existir esse ajuste, cancela operação
if (!$aj) {
    die("Ajuste não encontrado.");
}

// ===============
// DADOS DO AJUSTE
// ===============
$campo = $aj['campo'];      // campo que será alterado
$valor = $aj['valor_novo']; // novo valor solicitado

// =========
// SEGURANÇA
// =========
// Impede atualização de campos não permitidos via URL ou manipulação externa
$permitidos = ['inicio_ponto', 'inicio_almoco', 'fim_almoco', 'fim_ponto'];

if (!in_array($campo, $permitidos)) {
    die("Campo inválido.");
}

// ================
// ATUALIZA O PONTO
// ================
// Atualiza SOMENTE o campo solicitado no registro original do ponto
$stmt = $conn->prepare("UPDATE ponto_dia SET $campo = ? WHERE id_ponto = ?");
$stmt->bind_param("si", $valor, $aj['id_ponto']);
$stmt->execute();

// ==========================
// MARCA AJUSTE COMO APROVADO
// ==========================
$stmt = $conn->prepare("
    UPDATE ajustes_ponto
    SET status='Aprovado', data_resposta=NOW(), id_rh=?
    WHERE id_ajuste=?
");
$stmt->bind_param("ii", $_SESSION['id_usuario'], $id);
$stmt->execute();

// =================
// ENVIA NOTIFICAÇÃO
// =================
// Notifica o usuário que solicitou o ajuste
$stmt = $conn->prepare("
    INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem)
    VALUES (?, ?, 'Seu ajuste foi aprovado.')
");
$stmt->bind_param("ii", $aj['id_usuario'], $aj['id_ponto']);
$stmt->execute();

// ========================
// FUNÇÃO STATUS AUTOMÁTICO
// ========================
// Recalcula o status do ponto depois da atualização
function statusAuto($conn, $id_ponto)
{
    $q = $conn->prepare("
        SELECT inicio_ponto, inicio_almoco, fim_almoco, fim_ponto
        FROM ponto_dia
        WHERE id_ponto = ?
    ");
    $q->bind_param("i", $id_ponto);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();

    // Se todos os horários existem -> Finalizado
    // Se faltar algum -> Em Andamento
    return (
        $r['inicio_ponto'] &&
        $r['inicio_almoco'] &&
        $r['fim_almoco'] &&
        $r['fim_ponto']
    ) ? 'Finalizado' : 'Em Andamento';
}

// ========================
// ATUALIZA STATUS DO PONTO
// ========================
$novo = statusAuto($conn, $aj['id_ponto']);

$up = $conn->prepare("UPDATE ponto_dia SET status = ? WHERE id_ponto = ?");
$up->bind_param("si", $novo, $aj['id_ponto']);
$up->execute();

// ======================
// REDIRECIONAMENTO FINAL
// ======================
header("Location: ajustes_pendentes.php");
exit;
