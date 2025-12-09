<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// =====================
// CONTROLE DE PERMISSÃO
// =====================
// Apenas usuários nível 2 ou maior (RH / Admin)
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

// =====================
// VALIDA O ID DO AJUSTE
// =====================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID inválido.");
}

$id = intval($_GET['id']);

// ======================
// BUSCAR DADOS DO AJUSTE
// ======================

/*
Recupera o ajuste e o ponto relacionado

Tabelas:
ajustes_ponto -> dados da solicitação
ponto_dia     -> horários reais do ponto
usuario       -> funcionário solicitante
*/

$sql = "
SELECT 
    a.campo,                -- Campo que pode ser alterado
    a.justificativa,        -- Justificativa do pedido
    a.id_ajuste,
    a.id_ponto,
    p.inicio_ponto,
    p.inicio_almoco,
    p.fim_almoco,
    p.fim_ponto,
    p.data_reg,
    u.nome_usuario          -- Funcionário
FROM ajustes_ponto a
INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
INNER JOIN usuario u ON u.id_usuario = a.id_usuario
WHERE a.id_ajuste = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$ajuste = $stmt->get_result()->fetch_assoc();

if (!$ajuste) {
    die("Ajuste não encontrado.");
}

// ==============
// CAMPO EDITÁVEL
// ==============
// Somente um campo é alterável por vez
$campo_editavel = $ajuste['campo'];

// ============================
// FUNÇÃO DE BLOQUEIO DE INPUTS
// ============================
// Todos os campos são travados, exceto o que foi solicitado
function desabilitar($nomeCampo, $campoEditavel)
{
    return ($nomeCampo !== $campoEditavel)
        ? 'readonly disabled'
        : '';
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Editar Ajuste</title>
</head>

<body>
    <h1>Editar horário solicitado</h1>
    <a href="ajustes_pendentes.php">Voltar</a>
    <br><br>

    <form method="post" action="salvar.php">

        <!-- IDs usados no salvamento -->
        <input type="hidden" name="id_ajuste" value="<?= $ajuste['id_ajuste'] ?>">
        <input type="hidden" name="id_ponto" value="<?= $ajuste['id_ponto'] ?>">
        <input type="hidden" name="campo" value="<?= $campo_editavel ?>">

        <!-- Informações do ajuste -->
        <p><strong>Funcionário:</strong> <?= htmlspecialchars($ajuste['nome_usuario']) ?></p>
        <p><strong>Data:</strong> <?= htmlspecialchars($ajuste['data_reg']) ?></p>

        <!-- ===================== -->
        <!-- CAMPOS DE HORÁRIO     -->
        <!-- ===================== -->

        <!-- ENTRADA -->
        <label>Entrada:</label>
        <input type="time"
            name="inicio_ponto"
            value="<?= date('H:i', strtotime($ajuste['inicio_ponto'])) ?>"
            <?= desabilitar('inicio_ponto', $campo_editavel) ?>>
        <br><br>

        <!-- INÍCIO ALMOÇO -->
        <label>Início Almoço:</label>
        <input type="time"
            name="inicio_almoco"
            value="<?= date('H:i', strtotime($ajuste['inicio_almoco'])) ?>"
            <?= desabilitar('inicio_almoco', $campo_editavel) ?>>
        <br><br>

        <!-- FIM ALMOÇO -->
        <label>Fim Almoço:</label>
        <input type="time"
            name="fim_almoco"
            value="<?= date('H:i', strtotime($ajuste['fim_almoco'])) ?>"
            <?= desabilitar('fim_almoco', $campo_editavel) ?>>
        <br><br>

        <!-- SAÍDA -->
        <label>Saída:</label>
        <input type="time"
            name="fim_ponto"
            value="<?= date('H:i', strtotime($ajuste['fim_ponto'])) ?>"
            <?= desabilitar('fim_ponto', $campo_editavel) ?>>
        <br><br>

        <!-- Justificativa -->
        <label>Justificativa:</label><br>
        <textarea name="justificativa" rows="4" cols="50"><?= htmlspecialchars($ajuste['justificativa']) ?></textarea>
        <br><br>

        <!-- AÇÃO FINAL -->
        <button type="submit">Salvar Ajuste</button>
    </form>
</body>

</html>