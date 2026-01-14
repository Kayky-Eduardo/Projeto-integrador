<?php
// Importa a conexão com o banco de dados
require_once "../BD/conexao.php";

// Recebe filtros do formulário (caso existam)
$filtroInicio = $_GET["inicio"] ?? "";    // Data de início no formato YYYY-MM
$filtroFim    = $_GET["fim"] ?? "";       // Data de fim no formato YYYY-MM
$filtroUser   = $_GET["usuario"] ?? "";   // ID do usuário selecionado

// Busca todos os usuários para preencher o <select>
$users = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");

// Monta a SQL principal para consultar folhas de pagamento
$sql = "
    SELECT f.id_folha, f.mes_competencia, f.salario_liquido,
           u.nome_usuario, u.id_usuario
    FROM folhas f
    LEFT JOIN usuario u ON u.id_usuario = f.id_usuario
    WHERE 1
";

// ----------------------
// Filtro por período
// ----------------------
if (!empty($filtroInicio)) {
    $sql .= " AND f.mes_competencia >= '" . $conn->real_escape_string($filtroInicio . "-01") . "'";
}

if (!empty($filtroFim)) {
    $sql .= " AND f.mes_competencia <= '" . $conn->real_escape_string($filtroFim . "-01") . "'";
}

// ----------------------
// Filtro por funcionário
// ----------------------
if (!empty($filtroUser)) {
    $sql .= " AND f.id_usuario = " . intval($filtroUser);
}

// Ordenação: folhas mais recentes primeiro
$sql .= " ORDER BY f.mes_competencia DESC";

// Executa a consulta
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Histórico de Folhas</title>

<style> 
table {border-collapse: collapse; width: 100%; margin-top: 20px;}
td, th {border: 1px solid #444; padding: 8px;}
</style>

</head>
<body>

<h2>Histórico de Folhas de Pagamento</h2>

<!-- Formulário de filtros -->
<form method="GET">

    <label>Início:</label>
    <input type="month" name="inicio" value="<?= $filtroInicio ?>">

    <label>Fim:</label>
    <input type="month" name="fim" value="<?= $filtroFim ?>">

    <label>Funcionário:</label>
    <select name="usuario">
        <option value="">-- Todos --</option>

        <!-- Preenche o SELECT com os usuários -->
        <?php while ($u = $users->fetch_assoc()): ?>
            <option value="<?= $u['id_usuario'] ?>"
                <?= ($u['id_usuario'] == $filtroUser) ? 'selected' : '' ?>>
                <?= $u['nome_usuario'] ?> (ID: <?= $u['id_usuario'] ?>)
            </option>
        <?php endwhile; ?>

    </select>

    <button type="submit">Filtrar</button>
</form>

<!-- Tabela de resultados -->
<table>
    <thead>
        <tr>
            <th>Mês</th>
            <th>Funcionário</th>
            <th>Salário Líquido</th>
            <th>Ação</th>
        </tr>
    </thead>

    <tbody>

<?php if ($result->num_rows == 0): ?>
        <tr><td colspan="4">Nenhuma folha encontrada.</td></tr>
<?php else: ?>
    <?php while ($f = $result->fetch_assoc()): ?>
        <tr>
            <td><?= substr($f["mes_competencia"], 0, 7) ?></td>
            <td><?= $f["nome_usuario"] ?></td>
            <td>R$ <?= number_format($f["salario_liquido"], 2, ',', '.') ?></td>
            <td>
                <a href="../api/api_gerar_pdf.php?mes=<?= substr($f["mes_competencia"], 0, 7) ?>&id_usuario=<?= $f['id_usuario'] ?>" 
                target="_blank">
                Abrir PDF
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php endif; ?>

    </tbody>
</table>

</body>
</html>
