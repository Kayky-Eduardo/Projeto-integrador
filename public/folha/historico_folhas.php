<?php
include(__DIR__ . "/../../BD/conexao.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario = $_SESSION['id_usuario'] ?? 0;
$nivel = $_SESSION['nivel'] ?? 0;
$filtroInicio = $_GET["inicio"] ?? "";
$filtroFim    = $_GET["fim"] ?? "";
$filtroUser   = $_GET["usuario"] ?? "";
$paginaAtual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$registrosPorPagina = 16;
$offset = ($paginaAtual - 1) * $registrosPorPagina;
$users = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");

$sqlCount = "
    SELECT COUNT(*) as total
    FROM folhas f
    LEFT JOIN usuario u ON u.id_usuario = f.id_usuario
    WHERE 1
";

if (!empty($filtroInicio)) {
    $sqlCount .= " AND f.mes_competencia >= '" . $conn->real_escape_string($filtroInicio . "-01") . "'";
}
if (!empty($filtroFim)) {
    $sqlCount .= " AND f.mes_competencia <= '" . $conn->real_escape_string($filtroFim . "-01") . "'";
}
if (!empty($filtroUser)) {
    $sqlCount .= " AND f.id_usuario = " . intval($filtroUser);
}

$resultCount = $conn->query($sqlCount);
$totalRegistros = $resultCount->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

$sql = "
    SELECT f.*,
           u.nome_usuario, u.id_usuario, u.id_cargo
    FROM folhas f
    LEFT JOIN usuario u ON u.id_usuario = f.id_usuario
    WHERE 1
";

if (!empty($filtroInicio)) {
    $sql .= " AND f.mes_competencia >= '" . $conn->real_escape_string($filtroInicio . "-01") . "'";
}

if (!empty($filtroFim)) {
    $sql .= " AND f.mes_competencia <= '" . $conn->real_escape_string($filtroFim . "-01") . "'";
}

if (!empty($filtroUser)) {
    $sql .= " AND f.id_usuario = " . intval($filtroUser);
}

if ($nivel > 3) {
    $sql .= " AND f.id_usuario = " . intval($id_usuario);
}

$sql .= " AND f.revisado = 1";
$sql .= " ORDER BY f.mes_competencia DESC";
$sql .= " LIMIT $registrosPorPagina OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Histórico de Folhas</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Histórico de Folhas de Pagamento</h1>

            <section class="filtro-padrao">
                <form class="form-linha" method="GET">
                    <article>
                        <label class="label">Início:</label>
                        <input class="input" type="month" name="inicio" value="<?= $filtroInicio ?>">
                    </article>

                    <article>
                        <label class="label">Fim:</label>
                        <input class="input" type="month" name="fim" value="<?= $filtroFim ?>">
                    </article>

                    <article>
                        <label class="label">Funcionário:</label>
                        <select class="select-padrao" name="usuario">
                            <option value="">Todos</option>
                            <?php if ($nivel == 2): ?>
                                <?php while ($u = $users->fetch_assoc()): ?>
                                    <option value="<?= $u['id_usuario'] ?>"
                                        <?= ($u['id_usuario'] == $filtroUser) ? 'selected' : '' ?>>
                                        <?= $u['nome_usuario'] ?> (ID: <?= $u['id_usuario'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </article>

                    <button class="btn btn-padrao" type="submit">Filtrar</button>
                </form>
            </section>

            <section class="tabela-padrao">
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
                            <tr>
                                <td colspan="4">Nenhuma folha encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($f = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= substr($f["mes_competencia"], 0, 7) ?></td>
                                    <td><?= $f["nome_usuario"] ?></td>
                                    <td>R$ <?= number_format($f["salario_liquido"], 2, ',', '.') ?></td>
                                    <td>
                                        <a class="btn-link btn-padrao" href="../../api/api_gerar_pdf.php?mes=<?= substr($f["mes_competencia"], 0, 7) ?>&id_usuario=<?= $f['id_usuario'] ?>" target="_blank">
                                            Abrir PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section style="margin-top: 20px;">
                <?php if ($totalPaginas > 1): ?>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <?php if ($i == $paginaAtual): ?>
                            <strong><?= $i ?></strong>
                        <?php else: ?>
                            <a href="?inicio=<?= $filtroInicio ?>&fim=<?= $filtroFim ?>&usuario=<?= $filtroUser ?>&pagina=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                        &nbsp;
                    <?php endfor; ?>
                <?php endif; ?>
            </section>
        </section>
    </main>
</body>

</html>