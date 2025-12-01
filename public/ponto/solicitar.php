<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

// ===============
// VALIDAR id_ponto
// ===============
$id_ponto = isset($_GET['id_ponto']) ? intval($_GET['id_ponto']) : 0;

if ($id_ponto <= 0) {
    echo "<p>Registro inválido. <a href=\"../ponto/gerenciar.php\">Voltar</a></p>";
    exit;
}

// ===============
// BUSCAR O REGISTRO DO PONTO
// Funcionário só pode ver o seu próprio ponto
// RH pode ver de qualquer usuário
// ===============

if ($nivel >= 2) {
    // RH vê qualquer ponto
    $stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ?");
    $stmt->bind_param("i", $id_ponto);
} else {
    // Funcionário vê somente o próprio
    $stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_ponto, $id_usuario);
}

$stmt->execute();
$res = $stmt->get_result();
$reg = $res->fetch_assoc();

if (!$reg) {
    echo "<p>Ponto não encontrado ou você não tem permissão. 
          <a href=\"../ponto/gerenciar.php\">Voltar</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Solicitar Ajuste</title>
</head>

<body>

    <h1>Solicitar ajuste do dia <?= htmlspecialchars($reg['data_reg']) ?></h1>

    <form method="POST" action="solicitar_enviar.php">

        <input type="hidden" name="id_ponto" value="<?= $reg['id_ponto'] ?>">

        <label for="campo">Campo a ajustar:</label>
        <select name="campo" id="campo" required>
            <option value="inicio_ponto">Entrada</option>
            <option value="inicio_almoco">Início Almoço</option>
            <option value="fim_almoco">Fim Almoço</option>
            <option value="fim_ponto">Saída</option>
        </select>
        <br><br>

        <label for="valor_novo">Novo horário:</label>
        <input type="time" name="valor_novo" id="valor_novo" required>
        <br><br>

        <label for="justificativa">Justificativa:</label><br>
        <textarea name="justificativa" id="justificativa" required></textarea>
        <br><br>

        <button type="submit">Enviar Solicitação</button>
    </form>

    <br>
    <a href="../ponto/gerenciar.php">Voltar</a>

</body>

</html>