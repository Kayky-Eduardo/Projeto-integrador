<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

if ($nivel < 2) {
    header("Location: ../rh/ajustes_pendentes.php");
    exit;
}

/* MARCAR TODAS COMO LIDAS  */
if (isset($_POST['marcar_lidas'])) {
    $up = $conn->prepare("UPDATE notificacoes_ponto SET lida = 1 WHERE id_usuario = ?");
    $up->bind_param("i", $id_usuario);
    $up->execute();
    header("Location: notificacoes.php");
    exit;
}

/* FILTROS */
$f_from   = $_GET['from'] ?? '';
$f_to     = $_GET['to'] ?? '';
$somente_nao_lidas = $_GET['nao_lidas'] ?? '';

$where = ["n.id_usuario = ?"];
$params = [$id_usuario];
$types = "i";

if (!empty($f_from)) {
    $where[] = "DATE(n.data_notificacao) >= ?";
    $params[] = $f_from;
    $types .= "s";
}

if (!empty($f_to)) {
    $where[] = "DATE(n.data_notificacao) <= ?";
    $params[] = $f_to;
    $types .= "s";
}

if ($somente_nao_lidas) {
    $where[] = "n.lida = 0";
}

$sql = "
    SELECT 
        n.id_notificacao,     -- ID da notificação
        n.mensagem,           -- Texto da mensagem
        n.data_notificacao,   -- Data e hora
        n.lida,               -- Status (0 = não lida, 1 = lida)
        p.data_ponto,           -- Data do ponto relacionado
        p.data_ponto,           -- Data do ponto relacionado
        p.id_ponto            -- ID do ponto
    FROM notificacoes_ponto n
    INNER JOIN ponto_dia p ON p.id_ponto = n.id_ponto
    WHERE " . implode(" AND ", $where) . "
    ORDER BY n.lida ASC, n.data_notificacao DESC
    -- Mostra primeiro as não lidas, depois por data
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$notificacoes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Notificações</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Notificações</h1>

            <section class="container filtro-padrao">
                <form method="get" class="form-linha">
                    <article>
                        <label class="label">De:</label>
                        <input class="input" type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">
                    </article>

                    <article>
                        <label class="label">Até:</label>
                        <input class="input" type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">
                    </article>

                    <article>
                        <label class="label">
                            <input type="checkbox" name="nao_lidas" value="1" <?= $somente_nao_lidas ? 'checked' : '' ?>>
                            Somente não lidas
                        </label>
                    </article>

                    <button class="btn btn-padrao" type="submit">Filtrar</button>
                </form>

                <form method="post" class="form-linha">
                    <button class="btn btn-padrao" type="submit" name="marcar_lidas">
                        Marcar todas como lidas
                    </button>
                </form>
            </section>

            <section class="tabela-padrao">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Mensagem</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $notificacoes->fetch_assoc()): ?>
                            <tr class="<?= $row['lida'] == 0 ? 'nao-lida' : '' ?>">
                                <td><?= date("d/m/Y H:i", strtotime($row['data_notificacao'])) ?></td>
                                <td><?= htmlspecialchars($row['mensagem']) ?></td>
                                <td><?= $row['lida'] == 1 ? 'Lida' : 'Não lida' ?></td>

                                <td>
                                    <a class="btn-link btn-padrao" href="../ponto/historico.php?from=<?= $row['data_ponto'] ?>&to=<?= $row['data_ponto'] ?>">
                                        Ver dia
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>
</body>

</html>