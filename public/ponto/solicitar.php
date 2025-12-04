<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

// =======================
// DEFINIR PÁGINA DE VOLTA
// =======================
// Usuário comum volta para histórico
// RH volta para gerenciar
$pagina_voltar = ($nivel >= 2) ? "../ponto/gerenciar.php" : "../ponto/historico.php";

// ================
// VALIDAR id_ponto
// ================
// Captura o ID do ponto passado pela URL
$id_ponto = isset($_GET['id_ponto']) ? intval($_GET['id_ponto']) : 0;

// Se o ID for inválido, bloqueia o acesso
if ($id_ponto <= 0) {
    echo "<p>Registro inválido. <a href=\"$pagina_voltar\">Voltar</a></p>";
    exit;
}

// ==========================
// BUSCAR O REGISTRO DO PONTO
// ==========================
// Funcionário só pode ver o seu próprio ponto
// RH pode ver de qualquer usuário

// Caso seja RH, busca pelo id_ponto apenas
if ($nivel >= 2) {
    $stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ?");
    $stmt->bind_param("i", $id_ponto);
} else {
    // Funcionário só acessa pontos que pertencem a ele
    $stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_ponto, $id_usuario);
}

// Executa a consulta
$stmt->execute();

// Obtém o resultado
$res = $stmt->get_result();

// Recupera os dados do ponto
$reg = $res->fetch_assoc();

// Se não encontrar, mostra erro de permissão ou inexistência
if (!$reg) {
    echo "<p>Ponto não encontrado ou você não tem permissão. 
          <a href=\"$pagina_voltar\">Voltar</a></p>";
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

    <!-- Título com a data do ponto -->
    <h1>Solicitar ajuste do dia <?= htmlspecialchars($reg['data_reg']) ?></h1>

    <!-- Formulário de envio do ajuste -->
    <form method="POST" action="solicitar_enviar.php">

        <!-- ID do ponto enviado escondido -->
        <input type="hidden" name="id_ponto" value="<?= $reg['id_ponto'] ?>">

        <!-- Campo a ser alterado -->
        <label for="campo">Campo a ajustar:</label>
        <select name="campo" id="campo" required>
            <option value="inicio_ponto">Entrada</option>
            <option value="inicio_almoco">Início Almoço</option>
            <option value="fim_almoco">Fim Almoço</option>
            <option value="fim_ponto">Saída</option>
        </select>
        <br><br>

        <!-- Novo horário -->
        <label for="valor_novo">Novo horário:</label>
        <input type="time" name="valor_novo" id="valor_novo" required>
        <br><br>

        <!-- Justificativa -->
        <label for="justificativa">Justificativa:</label><br>
        <textarea name="justificativa" id="justificativa" required></textarea>
        <br><br>

        <!-- Botão para enviar -->
        <button type="submit">Enviar Solicitação</button>
    </form>
    <br>

    <!-- Link para voltar -->
    <a href="<?= $pagina_voltar ?>">Voltar</a>
</body>

</html>