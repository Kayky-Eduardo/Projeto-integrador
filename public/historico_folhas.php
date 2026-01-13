<?php
// Importa a conexão com o banco de dados
require_once "../BD/conexao.php";

// Recebe filtros do formulário (caso existam)
$filtroMes = $_GET["mes"] ?? "";      // Mês no formato YYYY-MM
$filtroUser = $_GET["usuario"] ?? ""; // ID do usuário selecionado

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
// WHERE 1 facilita adicionar filtros condicionais depois

// ----------------------
// Filtro por mês
// ----------------------
if (!empty($filtroMes)) {
    // Usa real_escape_string para evitar SQL Injection
    $sql .= " AND f.mes_competencia = '" . $conn->real_escape_string($filtroMes . "-01") . "'";
}

// ----------------------
// Filtro por funcionário
// ----------------------
if (!empty($filtroUser)) {
    // intval() garante que só números sejam usados
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

<!-- fiz o arroz com feijão pq sem isso fica muito feioKKKKKK -->
<style> 
table {border-collapse: collapse; width: 100%; margin-top: 20px;}
td, th {border: 1px solid #444; padding: 8px;}
</style>

</head>
<body>

<h2>Histórico de Folhas de Pagamento</h2>

<!-- Formulário de filtros -->
<form method="GET">

    <label>Mês:</label>
    <input type="month" name="mes" value="<?= $filtroMes ?>">

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
        <!-- Caso nenhum dado seja encontrado -->
        <tr><td colspan="4">Nenhuma folha encontrada.</td></tr>

<?php else: ?>
    <!-- Loop pelos registros encontrados -->
    <?php while ($f = $result->fetch_assoc()): ?>
        <tr>
            <!-- Exibe o mês sem o dia -->
            <td><?= substr($f["mes_competencia"], 0, 7) ?></td>

            <!-- Nome do funcionário -->
            <td><?= $f["nome_usuario"] ?></td>

            <!-- Salário líquido formatado -->
            <td>R$ <?= number_format($f["salario_liquido"], 2, ',', '.') ?></td>

            <!-- Leva pra api gerar o 'PDF' -->
            <td>
                <a href="../api/api_gerar_pdf.php?mes=<?= substr($f["mes_competencia"], 0, 7) ?>"
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
