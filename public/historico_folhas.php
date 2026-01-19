<?php
require_once "../BD/conexao.php";

// Recebe filtros do formulário (caso existam)
// Se não houver filtro, usa string vazia
$filtroInicio = $_GET["inicio"] ?? "";    // Data de início no formato YYYY-MM
$filtroFim    = $_GET["fim"] ?? "";       // Data de fim no formato YYYY-MM
$filtroUser   = $_GET["usuario"] ?? "";   // ID do usuário selecionado

// Paginação
$paginaAtual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1; // Página atual (padrão = 1)
$registrosPorPagina = 16; // Quantos registros mostrar por página
$offset = ($paginaAtual - 1) * $registrosPorPagina; // Calcula o deslocamento (OFFSET) para o SQL

// Busca todos os usuários para preencher o <select> no formulário
$users = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");

// Contagem total de registros (para calcular o total de páginas)
$sqlCount = "
    SELECT COUNT(*) as total
    FROM folhas f
    LEFT JOIN usuario u ON u.id_usuario = f.id_usuario
    WHERE 1
";

// Aplica filtros se existirem
if (!empty($filtroInicio)) {
    // Filtra pelo mês de competência maior ou igual à data inicial
    $sqlCount .= " AND f.mes_competencia >= '" . $conn->real_escape_string($filtroInicio . "-01") . "'";
}
if (!empty($filtroFim)) {
    // Filtra pelo mês de competência menor ou igual à data final
    $sqlCount .= " AND f.mes_competencia <= '" . $conn->real_escape_string($filtroFim . "-01") . "'";
}
if (!empty($filtroUser)) {
    // Filtra pelo usuário selecionado
    $sqlCount .= " AND f.id_usuario = " . intval($filtroUser);
}

// Executa a query de contagem
$resultCount = $conn->query($sqlCount);
$totalRegistros = $resultCount->fetch_assoc()['total']; // Total de registros que atendem aos filtros
$totalPaginas = ceil($totalRegistros / $registrosPorPagina); // Calcula o total de páginas

// SQL principal para exibir os registros com filtros e paginação
$sql = "
    SELECT f.id_folha, f.mes_competencia, f.salario_liquido,
           u.nome_usuario, u.id_usuario
    FROM folhas f
    LEFT JOIN usuario u ON u.id_usuario = f.id_usuario
    WHERE 1
";

// Aplica os mesmos filtros da contagem
if (!empty($filtroInicio)) {
    $sql .= " AND f.mes_competencia >= '" . $conn->real_escape_string($filtroInicio . "-01") . "'";
}
if (!empty($filtroFim)) {
    $sql .= " AND f.mes_competencia <= '" . $conn->real_escape_string($filtroFim . "-01") . "'";
}
if (!empty($filtroUser)) {
    $sql .= " AND f.id_usuario = " . intval($filtroUser);
}

// Ordena os resultados do mais recente para o mais antigo
$sql .= " ORDER BY f.mes_competencia DESC";

// Aplica limite e offset para paginação
$sql .= " LIMIT $registrosPorPagina OFFSET $offset";

// Executa a query final para exibir os registros
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
                <a href="../api/api_gerar_pdf.php?mes=<?= substr($f["mes_competencia"], 0, 7) ?>&id_usuario=<?= $f['id_usuario'] ?>" target="_blank">
                Abrir PDF
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php endif; ?>
    </tbody>
</table>

<!-- Links de paginação -->
<div style="margin-top: 20px;">
<?php if ($totalPaginas > 1): // Só mostra a paginação se houver mais de 1 página ?>

    <?php for ($i = 1; $i <= $totalPaginas; $i++): // Loop para gerar links de cada página ?>
        
        <?php if ($i == $paginaAtual): // Se for a página atual, não cria link, apenas destaca ?>
            <strong><?= $i ?></strong> <!-- Página atual em negrito -->
        <?php else: // Se não for a página atual, cria um link clicável ?>
            <a href="?inicio=<?= $filtroInicio ?>&fim=<?= $filtroFim ?>&usuario=<?= $filtroUser ?>&pagina=<?= $i ?>">
                <?= $i ?>
            </a>
        <?php endif; ?> 
        &nbsp; <!-- Pequeno espaço entre os números -->
    <?php endfor; ?>
<?php endif; ?>
</div>
</body>
</html>