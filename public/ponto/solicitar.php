<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// pega id do usuário logado
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    die("Usuário não autenticado.");
}

// --- valida e pega id_ponto seguro ---
$id_ponto = isset($_GET['id_ponto']) ? intval($_GET['id_ponto']) : 0;
if ($id_ponto <= 0) {
    // pode redirecionar pra histórico ou mostrar mensagem
    echo "<p>ID do registro inválido. <a href=\"../ponto/historico.php\">Voltar</a></p>";
    exit;
}

// --- busca o registro garantindo que pertence ao usuário ---
$stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_ponto, $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$reg = $res->fetch_assoc();

if (!$reg) {
    // registro não encontrado OU não pertence ao usuário
    // Se quiser permitir RH ver de qualquer usuário, checar nível antes
    if (isset($_SESSION['nivel']) && $_SESSION['nivel'] >= 3) {
        // RH pode ver qualquer registro — buscar apenas por id_ponto
        $stmt2 = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ?");
        $stmt2->bind_param("i", $id_ponto);
        $stmt2->execute();
        $reg = $stmt2->get_result()->fetch_assoc();
    }
}

if (!$reg) {
    echo "<p>Registro não encontrado ou você não tem permissão. <a href=\"../ponto/historico.php\">Voltar</a></p>";
    exit;
}

// agora $reg existe e é seguro acessar seus campos
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Solicitar Ajuste</title>
</head>
<body>

<h1>Ajustar dia <?php echo htmlspecialchars($reg['data_reg']); ?></h1>

<form method="POST" action="solicitar_enviar.php">
    <input type="hidden" name="id_ponto" value="<?php echo $reg['id_ponto']; ?>">

    <label>Qual campo deseja ajustar?</label>
    <select name="campo" required>
        <option value="inicio_ponto">Entrada</option>
        <option value="inicio_almoco">Início Almoço</option>
        <option value="fim_almoco">Fim Almoço</option>
        <option value="fim_ponto">Saída</option>
    </select><br><br>

    <label>Novo horário:</label>
    <input type="datetime-local" name="valor_novo" required><br><br>

    <label>Motivo:</label><br>
    <textarea name="motivo" required></textarea><br><br>

    <button type="submit">Enviar solicitação</button>
</form>

<p><a href="../ponto/historico.php">Voltar</a></p>

</body>
</html>
