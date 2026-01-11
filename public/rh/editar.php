<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// =====================
// CONTROLE DE PERMISSÃO
// =====================
// Apenas usuários nível 2 ou maior (RH / Admin)
if ($_SESSION['nivel'] >= 2) {
    // Permissão concedida
} else{
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
    a.motivo,               -- motivo do pedido
    a.id_ajuste,
    a.id_ponto,
    ps.inicio as inicio_pausa,
    ps.fim as fim_pausa,
    ps.id_pausa,
    p.inicio_ponto,
    p.fim_ponto,
    p.data_ponto,
    u.nome_usuario          -- Funcionário
FROM ajustes_ponto a
INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
INNER JOIN usuario u ON u.id_usuario = a.id_usuario
INNER JOIN pausa ps ON ps.id_pausa = a.id_pausa
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
        <input type="hidden" name="id_pausa" value="<?= $ajuste['id_pausa'] ?>">
        <input type="hidden" name="id_ponto" value="<?= $ajuste['id_ponto'] ?>">
        <input type="hidden" name="campo" value="<?= $campo_editavel ?>">

        <!-- Informações do ajuste -->
        <p><strong>Funcionário:</strong> <?= htmlspecialchars($ajuste['nome_usuario']) ?></p>
        <p><strong>Data:</strong> <?= htmlspecialchars($ajuste['data_ponto']) ?></p>

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

        <!-- INÍCIO Pausa -->
        <label>Início Pausa:</label>
        <input type="time"
            name="inicio_pausa"
            value="<?= date('H:i', strtotime($ajuste['inicio_pausa'])) ?>"
            <?= desabilitar('inicio_pausa', $campo_editavel) ?>>
        <br><br>

        <!-- FIM Pausa -->
        <label>Fim Pausa:</label>
        <input type="time"
            name="fim_pausa"
            value="<?= date('H:i', strtotime($ajuste['fim_pausa'])) ?>"
            <?= desabilitar('fim_pausa', $campo_editavel) ?>>
        <br><br>

        <!-- SAÍDA -->
        <label>Saída:</label>
        <input type="time"
            name="fim_ponto"
            value="<?= date('H:i', strtotime($ajuste['fim_ponto'])) ?>"
            <?= desabilitar('fim_ponto', $campo_editavel) ?>>
        <br><br>

        <!-- motivo -->
        <label>motivo:</label><br>
        <textarea name="motivo" rows="4" cols="50"><?= htmlspecialchars($ajuste['motivo']) ?></textarea>
        <br><br>

        <!-- AÇÃO FINAL -->
        <button type="submit">Salvar Ajuste</button>
    </form>
</body>

</html>