<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

/* ==========================
   FUNÇÃO STATUS AUTOMÁTICO
========================== */
function atualizarStatusPonto($conn, $id_ponto)
{
    $sql = "SELECT inicio_ponto, inicio_almoco, fim_almoco, fim_ponto 
            FROM ponto_dia WHERE id_ponto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_ponto);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (
        $res['inicio_ponto'] &&
        $res['inicio_almoco'] &&
        $res['fim_almoco'] &&
        $res['fim_ponto']
    ) {
        $status = 'Finalizado';
    } else {
        $status = 'Em Andamento';
    }

    $up = $conn->prepare("UPDATE ponto_dia SET status = ? WHERE id_ponto = ?");
    $up->bind_param("si", $status, $id_ponto);
    $up->execute();
}

/* ==========================
   BUSCAR AJUSTE
========================== */
$id = intval($_GET['id'] ?? 0);

$busca = $conn->query("SELECT * FROM ajustes_ponto WHERE id_ajuste = $id");

if ($busca && $busca->num_rows > 0) {

    $aj = $busca->fetch_assoc();

    // Atualiza status do ponto corretamente
    atualizarStatusPonto($conn, $aj['id_ponto']);

    // Apaga solicitação
    $conn->query("DELETE FROM ajustes_ponto WHERE id_ajuste = $id");

    // Notificação
    $conn->query("
        INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem)
        VALUES ({$aj['id_usuario']}, {$aj['id_ponto']}, 'Seu ajuste foi recusado.')
    ");
}

header("Location: ajustes_pendentes.php");
exit;
