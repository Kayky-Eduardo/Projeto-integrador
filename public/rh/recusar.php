<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// =====================
// CONTROLE DE PERMISSÃO
// =====================
// Apenas usuários nível 2 ou maior (RH / Admin)
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

// =================================
// FUNÇÃO: ATUALIZAR STATUS DO PONTO
// =================================
// Essa função garante que o ponto não fique com status errado após a recusa da solicitação
function atualizarStatusPonto($conn, $id_ponto)
{
    // Busca os horários do ponto
    $sql = "SELECT inicio_ponto, fim_ponto 
            FROM ponto_dia WHERE id_ponto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_ponto);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    // Verifica se todos os campos existem
    if (
        $res['inicio_ponto'] &&
        $res['fim_ponto']
    ) {
        $status = 'Finalizado';
    } else {
        $status = 'Em Andamento';
    }

    // Atualiza o status no banco
    $up = $conn->prepare("UPDATE ponto_dia SET status = ? WHERE id_ponto = ?");
    $up->bind_param("si", $status, $id_ponto);
    $up->execute();
}

// =============
// BUSCAR AJUSTE
// =============
// Pega o ID do ajuste via URL
$id = intval($_GET['id'] ?? 0);

// Busca o ajuste no banco
$busca = $conn->query("SELECT * FROM ajustes_ponto WHERE id_ajuste = $id");

// Verifica se encontrou o ajuste
if ($busca && $busca->num_rows > 0) {

    // Dados da solicitação
    $aj = $busca->fetch_assoc();

    // ========================
    // ATUALIZA STATUS DO PONTO
    // ========================
    // Recalcula o status antes de apagar o ajuste
    atualizarStatusPonto($conn, $aj['id_ponto']);

    // ====================
    // REMOVE A SOLICITAÇÃO
    // ====================
    // Exclui o pedido do banco
    $conn->query("DELETE FROM ajustes_ponto WHERE id_ajuste = $id");

    // =================
    // ENVIA NOTIFICAÇÃO
    // =================
    // Notifica o usuário solicitante
    $conn->query("
        INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem)
        VALUES ({$aj['id_usuario']}, {$aj['id_ponto']}, 'Seu ajuste foi recusado.')
    ");
}

// ======================
// REDIRECIONAMENTO FINAL
// ======================
header("Location: ajustes_pendentes.php");
exit;
