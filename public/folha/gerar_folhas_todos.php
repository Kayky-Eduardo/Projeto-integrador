<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/funcoes/funcoes_calculo.php";

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
// 3. Adicionar evento (form separado)
// --------------------------
$mensagem_evento = '';
if (isset($_POST['add_evento'])) {
    $id_usuario_evento = $_POST['id_usuario_evento'] ?? null;
    $tipo = $_POST['tipo'] ?? '';
    $descricao = $_POST['descricao'] ?? 'Não Informado';
    $valor = floatval($_POST['valor'] ?? 0);

    if ($id_usuario_evento && $tipo && $valor > 0) {
        $stmt = $conn->prepare("
            INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issds", $id_usuario_evento, $tipo, $descricao, $valor, $mes_padrao);
        $stmt->execute();
        $mensagem_evento = "Evento adicionado com sucesso!";
    } else {
        $mensagem_evento = "Preencha todos os campos corretamente.";
    }
}

// --------------------------
// 4. Função para gerar folha de um usuário
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
// 5. Gerar folhas se botão foi clicado
// --------------------------
$folhas_geradas = [];
if (isset($_POST['gerar_folhas'])) {
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

</head>
<body>

<h1>Gerar Folhas de Pagamento</h1>

<!-- Form para adicionar evento -->
<form method="POST">
    <fieldset>
        <legend>Adicionar Provento/Desconto</legend>

        <label>Mês:</label>
        <input type="month" name="mes" value="<?= $mes ?>"><br>

        <label>Usuário:</label>
        <select name="id_usuario_evento" required>
            <option value="">-- Selecione --</option>
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id_usuario'] ?>"><?= $u['nome_usuario'] ?></option>
            <?php endforeach; ?>
        </select><br>

        <label>Tipo:</label>
        <select name="tipo" required>
            <option value="provento">Provento</option>
            <option value="desconto">Desconto</option>
        </select><br>

        <label>Descrição:</label>
        <input type="text" name="descricao" placeholder="Descrição do evento"><br>

        <label>Valor:</label>
        <input type="number" step="0.01" name="valor" placeholder="0.00"><br>

        <button type="submit" name="add_evento">Adicionar Evento</button>
    </fieldset>
</form>

<?php if ($mensagem_evento): ?>
    <p style="color:green;"><?= $mensagem_evento ?></p>
<?php endif; ?>

<!-- Form para gerar folhas -->
<form method="POST">
    <label>Mês:</label>
    <input type="month" name="mes" value="<?= $mes ?>">
    <button type="submit" name="gerar_folhas">Gerar Todas as Folhas</button>
</form>

<?php if (!empty($folhas_geradas)): ?>
    <h2>Folhas Geradas <?= $mes ?></h2>
    <ul>
    <?php foreach ($folhas_geradas as $f): ?>
        <li>
            <?= $f['nome_usuario'] ?> – R$ <?= number_format($f['salario_liquido'], 2, ',', '.') ?>
            <a href="../../api/api_gerar_pdf.php?mes=<?= $mes ?>&id_usuario=<?= $f['id_usuario'] ?>" target="_blank">
                Abrir PDF
            </a>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
