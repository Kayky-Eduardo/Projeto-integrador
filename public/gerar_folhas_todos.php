<?php
session_start();
require_once "../BD/conexao.php";
require_once "../services/funcoes_calculo.php";

// --------------------------
// 1. Recebe mês do formulário
// --------------------------
$mes = $_POST['mes'] ?? date('Y-m'); // default: mês atual
$mes_padrao = $mes . "-01";

// --------------------------
// 2. Buscar todos os usuários ativos
// --------------------------
$sql = "SELECT id_usuario, nome_usuario 
        FROM usuario 
        WHERE conta_ativa = 1";

$result = $conn->query($sql);

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

// --------------------------
// 3. Função para gerar folha de um usuário
// --------------------------
function gerarFolhaUsuario(array $usuario, string $mes_padrao, $conn) {

    $id_usuario = $usuario['id_usuario'];

    // --- Buscar salário ---
    $stmt = $conn->prepare("
        SELECT u.nome_usuario, c.salario_bruto 
        FROM usuario u
        JOIN cargo c ON c.id_cargo = u.id_cargo
        WHERE u.id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    if (!$userData) return null;

    $salario_bruto = floatval($userData['salario_bruto']);

    // --- Buscar eventos ---
    $stmt2 = $conn->prepare("
        SELECT tipo, valor 
        FROM eventos 
        WHERE id_usuario = ? AND mes_competencia = ?
    ");
    $stmt2->bind_param("is", $id_usuario, $mes_padrao);
    $stmt2->execute();
    $resEventos = $stmt2->get_result();

    $total_proventos = 0;
    $total_descontos = 0;
    while ($evt = $resEventos->fetch_assoc()) {
        if ($evt["tipo"] === "provento") $total_proventos += floatval($evt["valor"]);
        else $total_descontos += floatval($evt["valor"]);
    }

    // --- Cálculos legais ---
    $fgts = calcularFGTS($salario_bruto);
    $inss = calcularINSS($salario_bruto);
    $irrf = calcularIRRF($salario_bruto, $inss, 0);
    $vt = calcularVT($salario_bruto, 300);
    $total_descontos += $inss + $irrf + $vt;

    $salario_liquido = calcularSalarioLiquido($salario_bruto, $total_proventos, $total_descontos);

    // --- Salvar no banco ---
    $stmt3 = $conn->prepare("
        INSERT INTO folhas 
        (id_usuario, mes_competencia, salario_bruto, total_proventos, total_descontos, fgts, inss, irrf, vt, salario_liquido)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            total_proventos = VALUES(total_proventos),
            total_descontos = VALUES(total_descontos),
            fgts = VALUES(fgts),
            inss = VALUES(inss),
            irrf = VALUES(irrf),
            vt = VALUES(vt),
            salario_liquido = VALUES(salario_liquido)
    ");
    $stmt3->bind_param(
        "issddddddd",
        $id_usuario,
        $mes_padrao,
        $salario_bruto,
        $total_proventos,
        $total_descontos,
        $fgts,
        $inss,
        $irrf,
        $vt,
        $salario_liquido
    );
    $stmt3->execute();

    return [
        'id_usuario' => $id_usuario,
        'nome_usuario' => $usuario['nome_usuario'],
        'salario_liquido' => $salario_liquido
    ];
}

// --------------------------
// 4. Gerar folhas se botão foi clicado
// --------------------------
$folhas_geradas = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($usuarios as $usuario) {
        $folha = gerarFolhaUsuario($usuario, $mes_padrao, $conn);
        if ($folha) $folhas_geradas[] = $folha;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerar Folhas de Pagamento</title>
<style>
body { font-family: Arial; padding: 20px; }
button { padding: 8px 16px; margin-top: 10px; }
ul { margin-top: 20px; }
li { margin-bottom: 8px; }
</style>
</head>
<body>

<h1>Gerar Folhas de Pagamento</h1>

<form method="POST">
    <label>Mês:</label>
    <input type="month" name="mes" value="<?= $mes ?>">
    <button type="submit">Gerar Todas as Folhas</button>
</form>

<?php if (!empty($folhas_geradas)): ?>
    <h2>Folhas Geradas – <?= $mes ?></h2>
    <ul>
    <?php foreach ($folhas_geradas as $f): ?>
        <li>
            <?= $f['nome_usuario'] ?> – R$ <?= number_format($f['salario_liquido'], 2, ',', '.') ?>
            <a href="../api/api_gerar_pdf.php?mes=<?= $mes ?>&id_usuario=<?= $f['id_usuario'] ?>" target="_blank">
                Abrir PDF
            </a>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
