<?php
session_start(); // TEM QUE SER O PRIMEIRO

include '../../BD/conexao.php';
include '../../include/verificacao.php';

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    die("Usuário não autenticado.");
}

// Mostrar mensagem única
if (isset($_SESSION['msg'])) {
    echo "<script>alert('" . $_SESSION['msg'] . "');</script>";
    unset($_SESSION['msg']);
}


// ----------------------------------------
// 1) SALVAR CONFIGURAÇÃO
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_config'])) {

    $tempo_min = intval($_POST['tempo_min']);
    $tempo_max = intval($_POST['tempo_max']);
    $saida_automatica = isset($_POST['saida_automatica']) ? 1 : 0;

    // Verifica se já existe
    $sqlCheck = "SELECT id_config FROM pausa_config WHERE id_usuario = ?";
    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Atualiza
        $sql = "UPDATE pausa_config SET tempo_min=?, tempo_max=?, saida_automatica=? 
                WHERE id_usuario=?";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("iiii", $tempo_min, $tempo_max, $saida_automatica, $id_usuario);
        $stmt2->execute();
    } else {
        // Insere
        $sql = "INSERT INTO pausa_config (id_usuario, tempo_min, tempo_max, saida_automatica)
                VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("iiii", $id_usuario, $tempo_min, $tempo_max, $saida_automatica);
        $stmt2->execute();
    }

    $_SESSION['msg'] = "Configurações salvas com sucesso!";
    header("Location: pausa.php");
    exit;
}


// ----------------------------------------
// 2) BUSCAR CONFIG DO USUÁRIO
// ----------------------------------------
$sql = "SELECT * FROM pausa_config WHERE id_usuario = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();

$tempo_min = $config['tempo_min'] ?? 5;
$tempo_max = $config['tempo_max'] ?? 15;
$saida_automatica = $config['saida_automatica'] ?? 0;


// ----------------------------------------
// 3) VERIFICAR PAUSA ATUAL
// ----------------------------------------
$sql = "SELECT * FROM pausa WHERE id_usuario = ? AND status='ativa' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$pausa = $stmt->get_result()->fetch_assoc();

$tem_pausa_ativa = $pausa ? true : false;


// ----------------------------------------
// 4) TEMPO DECORRIDO
// ----------------------------------------
$minutos_decorridos = 0;

if ($tem_pausa_ativa) {
    $sql = "SELECT TIMESTAMPDIFF(MINUTE, ?, NOW()) AS min";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $pausa['inicio']);
    $stmt->execute();
    $minutos_decorridos = $stmt->get_result()->fetch_assoc()['min'] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pausa</title>
    <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>

<h2>Configuração e Controle de Pausas</h2>

<!-- CONFIG -->
<h3>Configuração</h3>
<form method="POST">
    <label>Tempo Mínimo:</label>
    <input type="number" name="tempo_min" value="<?= $tempo_min ?>" min="1" required>

    <label>Tempo Máximo:</label>
    <input type="number" name="tempo_max" value="<?= $tempo_max ?>" min="1" required>

    <label>
        <input type="checkbox" name="saida_automatica" <?= $saida_automatica ? "checked" : "" ?>>
        Saída Automática
    </label>

    <button type="submit" name="salvar_config">Salvar Configuração</button>
</form>


<!-- STATUS -->
<h3>Status Atual</h3>

<?php if ($tem_pausa_ativa): ?>

    <p><b>Pausa ativa!</b></p>
    <p>Iniciada há: <b><?= $minutos_decorridos ?></b> minutos</p>

    <button onclick="encerrarPausa()">Encerrar Pausa</button>

<?php else: ?>

    <p>Nenhuma pausa ativa.</p>

    <!-- MOTIVO DA PAUSA (só mostra quando NÃO tem pausa ativa) -->
    <form id="formPausa">
        <label for="motivo">Motivo da pausa:</label>
        <select name="motivo" id="motivo" required>
            <option value="">Selecione um motivo</option>
            <option value="Banheiro">Banheiro</option>
            <option value="Água">Água</option>
            <option value="Café">Café</option>
            <option value="Descanso rápido">Descanso rápido</option>
            <option value="Problema técnico">Problema técnico</option>
            <option value="Conversa com gestor">Conversa com gestor</option>
        </select>

        <br><br>
        <button type="button" onclick="iniciarPausa()">Iniciar Pausa</button>
    </form>

<?php endif; ?>



<!-- LOGS -->
<h3>Logs do Encerramento Automático</h3>
<div>
<?php
ob_start();
include "pausa_auto.php";
$logs = ob_get_clean();
echo nl2br($logs);
?>
</div>

<script>
function iniciarPausa() {

    let motivo = document.getElementById("motivo").value;

    if (!motivo) {
        alert("Selecione um motivo!");
        return;
    }

    fetch("pausa_iniciar.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "motivo=" + encodeURIComponent(motivo)
    })
    .then(r => r.json())
    .then(d => alert(d.mensagem))
    .then(() => location.reload());
}

function encerrarPausa() {
    fetch("pausa_encerrar.php", { method: "POST" })
        .then(r => r.json())
        .then(d => alert(d.mensagem))
        .then(() => location.reload());
}


</script>

</body>
</html>
