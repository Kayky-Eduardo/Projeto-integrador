<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');

/* ==========
VALIDAR LOGIN
========== */
// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    die("Acesso negado.");
}

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

/* =====================
DEFINIR PARA ONDE VOLTAR
===================== */
// Define a página de retorno com base no nível do usuário
$pagina_voltar = ($nivel >= 2) ? "../ponto/gerenciar.php" : "../ponto/historico.php";

/* ==============
CAMPOS PERMITIDOS
============== */
// Define quais campos podem ser alterados
$camposPermitidos = [
    'inicio_ponto',
    'inicio_almoco',
    'fim_almoco',
    'fim_ponto'
];

/* =========
VALIDAR FORM
========= */
// Captura os dados enviados pelo formulário
$id_ponto   = intval($_POST['id_ponto'] ?? 0);          // ID do ponto
$campo      = $_POST['campo'] ?? '';                    // Campo que será alterado
$valor_time = $_POST['valor_novo'] ?? '';               // Novo horário informado
$motivo     = trim($_POST['justificativa'] ?? '');      // Justificativa da alteração

// Valida se os dados estão corretos
if (
    $id_ponto <= 0 ||
    !in_array($campo, $camposPermitidos) ||
    !$valor_time ||
    !$motivo
) {
    die("Dados inválidos.");
}

/* =======================
BUSCAR VALOR ANTIGO E DATA
======================= */
// Busca a data do registro e o valor antigo do campo
$busca = $conn->prepare("SELECT data_reg, `$campo` FROM ponto_dia WHERE id_ponto = ?");
$busca->bind_param("i", $id_ponto);
$busca->execute();

// Retorna o resultado da busca
$res = $busca->get_result()->fetch_assoc();

// Verifica se encontrou o ponto
if (!$res) {
    die("Ponto não encontrado.");
}

// Data do ponto (YYYY-MM-DD)
$data = $res['data_reg'];

// Verifica se o campo antigo possuía valor
$valor_antigo = $res[$campo]
    ? date('Y-m-d H:i:s', strtotime($res[$campo]))
    : null;

// Monta DateTime completo com a nova hora
$valor_novo = $data . ' ' . $valor_time . ':00';

/* ===========
INSERIR AJUSTE
=========== */
// Insere a solicitação de ajuste no banco de dados
$stmt = $conn->prepare("
    INSERT INTO ajustes_ponto 
        (id_ponto, id_usuario, campo, valor_antigo, valor_novo, motivo, status, data_solicitacao)
    VALUES 
        (?, ?, ?, ?, ?, ?, 'Pendente', NOW())
");

// Vincula os valores à query
$stmt->bind_param(
    "iissss",
    $id_ponto,
    $id_usuario,
    $campo,
    $valor_antigo,
    $valor_novo,
    $motivo
);

/* =================
EXECUTAR E FINALIZAR
================= */
if ($stmt->execute()) {

    // MARCA O PONTO COMO "REVISAR"
    // Evita que o sistema sobreponha o valor antes da análise do RH
    $up = $conn->prepare("UPDATE ponto_dia SET status = 'Revisar' WHERE id_ponto = ?");
    $up->bind_param("i", $id_ponto);
    $up->execute();

    // Mensagem de sucesso e redirecionamento
    echo "
        <script>
            alert('Solicitação enviada com sucesso! O ponto está em revisão.');
            window.location.href = '$pagina_voltar';
        </script>
    ";
} else {

    // Caso ocorra erro ao salvar o ajuste
    echo "
        <script>
            alert('Erro ao salvar ajuste.');
            window.history.back();
        </script>
    ";
}
