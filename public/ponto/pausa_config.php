<?php
include '../../BD/conexao.php';
include '../../include/verificacao.php';

// --- Lógica para salvar configurações ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tempo_min = intval($_POST['tempo_min']);
    $tempo_max = intval($_POST['tempo_max']);
    $saida_automatica = isset($_POST['saida_automatica']) ? 1 : 0;

    // Verifica se já existe configuração
    $sqlCheck = "SELECT COUNT(*) AS total FROM pausa_config";
    $res = $conn->query($sqlCheck);
    $row = $res->fetch_assoc();

    if ($row['total'] > 0) {
        // Atualiza a configuração existente
        $sql = "UPDATE pausa_config 
                SET tempo_min = ?, tempo_max = ?, saida_automatica = ? 
                WHERE id_config = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $tempo_min, $tempo_max, $saida_automatica);
        $stmt->execute();
    } else {
        // Cria uma nova configuração
        $sql = "INSERT INTO pausa_config (tempo_min, tempo_max, saida_automatica)
                VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $tempo_min, $tempo_max, $saida_automatica);
        $stmt->execute();
    }

    echo "<script>alert('Configurações salvas com sucesso!');</script>";
}

// --- Busca configuração atual ---
$sql = "SELECT * FROM pausa_config LIMIT 1";
$res = $conn->query($sql);
$config = $res->fetch_assoc();

$tempo_min = $config['tempo_min'] ?? 5;
$tempo_max = $config['tempo_max'] ?? 15;
$saida_automatica = $config['saida_automatica'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configuração de Pausas</title>
    <link rel="stylesheet" href="../../assets/estilo.css">

</head>
<body>
    <h2>Configuração de Pausas</h2>
    <form method="POST">
        <label for="tempo_min">Tempo mínimo (minutos):</label>
        <input type="number" id="tempo_min" name="tempo_min" min="1" value="<?= htmlspecialchars($tempo_min) ?>" required>

        <label for="tempo_max">Tempo máximo (minutos):</label>
        <input type="number" id="tempo_max" name="tempo_max" min="1" value="<?= htmlspecialchars($tempo_max) ?>" required>

        <input type="checkbox" id="saida_automatica" name="saida_automatica" <?= $saida_automatica ? 'checked' : '' ?>>
        <label for="saida_automatica">Ativar saída automática</label>
        
        <button type="submit">Salvar Configuração</button>
    </form>

    <?php
    // Executa pausa_auto.php e captura os logs
    ob_start();
    include 'pausa_auto.php';
    $logs = ob_get_clean();

    if ($logs) {
        echo "<div class='logs'>
                <h3>Logs do Encerramento Automático:</h3>
                <pre>$logs</pre>
              </div>";
    }
    ?>
</body>
</html>
