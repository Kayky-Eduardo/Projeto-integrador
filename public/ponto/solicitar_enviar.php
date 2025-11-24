<?php
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');
session_start();

// ==========================
// VALIDAR LOGIN
// ==========================
if (!isset($_SESSION['id_usuario'])) {
    die("Acesso negado.");
}

// ==========================
// VALIDAR CAMPOS DO FORM
// ==========================
if (!isset($_POST['id_ponto'])) {
    die("ID do ponto não recebido.");
}

$id_ponto = intval($_POST['id_ponto']);
$campo = $_POST['campo'] ?? null;
$valor_novo = $_POST['valor_novo'] ?? null;
$motivo = trim($_POST['motivo'] ?? "");

// pegar id do usuário
$id_usuario = $_SESSION['id_usuario'];

// ==========================
// PEGAR VALOR ANTIGO DO CAMPO
// ==========================
$q = $conn->prepare("SELECT $campo FROM ponto_dia WHERE id_ponto = ?");
$q->bind_param("i", $id_ponto);
$q->execute();
$old = $q->get_result()->fetch_assoc();
$valor_antigo = $old[$campo] ?? null;

// ==========================
// INSERIR AJUSTE
// ==========================
$stmt = $conn->prepare("
    INSERT INTO ajustes_ponto 
        (id_ponto, id_usuario, campo, valor_antigo, valor_novo, motivo)
    VALUES 
        (?, ?, ?, ?, ?, ?)
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
// EXECUTAR
// ==========================
if ($stmt->execute()) {
    echo "
        <script>
            alert('Solicitação enviada com sucesso!');
            window.location.href = 'historico.php';
        </script>
    ";
} else {
    echo "
        <script>
            alert('Erro ao enviar solicitação: " . $conn->error . "');
            window.history.back();
        </script>
    ";
}
