<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');

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

    return $status;
}

/* ==========================
   VALIDAR LOGIN
========================== */
if (!isset($_SESSION['id_usuario'])) {
    die("Acesso negado.");
}

/* ==========================
   VALIDAR FORM
========================== */
$id_ponto   = intval($_POST['id_ponto'] ?? 0);
$campo      = $_POST['campo'] ?? '';
$valor_time = $_POST['valor_novo'] ?? '';
$motivo     = trim($_POST['justificativa'] ?? '');

if ($id_ponto <= 0 || !$campo || !$valor_time || !$motivo) {
    die("Dados inválidos.");
}

$id_usuario = $_SESSION['id_usuario'];

/* ==========================
   BUSCAR VALOR ANTIGO
========================== */
$busca = $conn->prepare("SELECT data_reg, `$campo` FROM ponto_dia WHERE id_ponto = ?");
$busca->bind_param("i", $id_ponto);
$busca->execute();
$res = $busca->get_result()->fetch_assoc();

if (!$res) {
    die("Ponto não encontrado.");
}

$data = $res['data_reg'];
$valor_antigo = $res[$campo]
    ? date('Y-m-d H:i:s', strtotime($res[$campo]))
    : null;

$valor_novo = $data . ' ' . $valor_time . ':00';

/* ==========================
   INSERIR AJUSTE
========================== */
$stmt = $conn->prepare("
    INSERT INTO ajustes_ponto 
        (id_ponto, id_usuario, campo, valor_antigo, valor_novo, motivo, status, data_solicitacao)
    VALUES 
        (?, ?, ?, ?, ?, ?, 'Pendente', NOW())
");

$stmt->bind_param(
    "iissss",
    $id_ponto,
    $id_usuario,
    $campo,
    $valor_antigo,
    $valor_novo,
    $motivo
);

/* ==========================
   FINAL
========================== */
if ($stmt->execute()) {

    // ✅ FORÇA REVISÃO
    $up = $conn->prepare("UPDATE ponto_dia SET status='Revisar' WHERE id_ponto=?");
    $up->bind_param("i", $id_ponto);
    $up->execute();

    echo "
        <script>
            alert('Solicitação enviada! O ponto está em revisão.');
            window.location.href = '../ponto/gerenciar.php';
        </script>
    ";
} else {
    echo "
        <script>
            alert('Erro ao salvar ajuste.');
            window.history.back();
        </script>
    ";
}
