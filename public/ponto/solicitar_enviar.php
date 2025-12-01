<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');

// ==========================
// VALIDAR LOGIN
// ==========================
if (!isset($_SESSION['id_usuario'])) {
    die("Acesso negado.");
}

// ==========================
// VALIDAR DADOS DO FORM
// ==========================
$id_ponto   = intval($_POST['id_ponto'] ?? 0);
$campo      = $_POST['campo'] ?? '';
$valor_time = $_POST['valor_novo'] ?? '';
$motivo     = trim($_POST['justificativa'] ?? '');

if ($id_ponto <= 0 || !$campo || !$valor_time || !$motivo) {
    die("Dados inválidos.");
}

// Usuario solicitante
$id_usuario = $_SESSION['id_usuario'];

// ==========================
// BUSCAR REGISTRO DO PONTO
// ==========================
$busca = $conn->prepare("SELECT data_reg, `$campo` FROM ponto_dia WHERE id_ponto = ?");
$busca->bind_param("i", $id_ponto);
$busca->execute();
$res = $busca->get_result()->fetch_assoc();

if (!$res) {
    die("Ponto não encontrado.");
}

// ==========================
// MONTAR DATETIME CORRETO
// ==========================

// data do ponto
$data = $res['data_reg'];

// valor antigo (se existir)
$valor_antigo = $res[$campo]
    ? date('Y-m-d H:i:s', strtotime($res[$campo]))
    : null;

// novo valor usando a mesma data do ponto
$valor_novo = $data . ' ' . $valor_time . ':00';


// ==========================
// INSERIR AJUSTE
// ==========================
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

// ==========================
// EXECUTAR E FINALIZAR
// ==========================
if ($stmt->execute()) {

    // Atualiza status do ponto
    $up = $conn->prepare("UPDATE ponto_dia SET status = 'Revisar' WHERE id_ponto = ?");
    $up->bind_param("i", $id_ponto);
    $up->execute();

    echo "
        <script>
            alert('Solicitação enviada com sucesso! Horário salvo corretamente.');
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
