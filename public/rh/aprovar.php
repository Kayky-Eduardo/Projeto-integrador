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
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
}

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
$permitidos = ['inicio_ponto', 'inicio_pausa', 'fim_pausa', 'fim_ponto'];

if (!in_array($campo, $permitidos)) {
    die("Campo inválido.");
}

// ================
// ATUALIZA O PONTO
// ================
// Atualiza SOMENTE o campo solicitado no registro original do ponto

// agora faz verificação se é campo de pausa ou ponto normal ↓
if ($campo == 'inicio_pausa' || $campo == 'fim_pausa'){
    $campoPausa = ($campo == 'inicio_pausa') ? 'inicio' : 'fim';
    $stmt = $conn->prepare("UPDATE pausa SET $campoPausa = ? WHERE id_pausa = ?");
    $stmt->bind_param("si", $valor, $aj['id_pausa']);
    $stmt->execute();
} else{
    $stmt = $conn->prepare("UPDATE ponto_dia SET $campo = ? WHERE id_ponto = ?");
    $stmt->bind_param("si", $valor, $aj['id_ponto']);
    $stmt->execute();
}

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
function statusAuto($conn, $id_ponto)
{
    // Query corrigida: Sem ambiguidade
    $q = $conn->prepare("
        SELECT 
            p.inicio_ponto, 
            p.fim_ponto,
            (SELECT COUNT(*) FROM pausa WHERE id_usuario = p.id_usuario AND data = p.data_ponto AND fim IS NULL) as pausas_abertas
        FROM ponto_dia p
        WHERE p.id_ponto = ?
    ");
    $q->bind_param("i", $id_ponto);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();

    // Somente retorna Finalizado se tiver entrada, saída e NENHUMA pausa aberta
    if ($r['inicio_ponto'] && $r['fim_ponto'] && $r['pausas_abertas'] == 0) {
        return 'Finalizado';
    }
    return 'Em Andamento';
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
