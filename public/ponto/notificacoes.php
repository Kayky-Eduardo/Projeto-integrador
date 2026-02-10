<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

// ======================
// REDIRECIONAMENTO DO RH
// ======================
// Se for RH/Admin, redireciona para tela de ajustes pendentes
if ($nivel < 2) {
    header("Location: ../rh/ajustes_pendentes.php");
    exit;
}

/* ====================
MARCAR TODAS COMO LIDAS
==================== */
// Se o botão "marcar_lidas" foi enviado
if (isset($_POST['marcar_lidas'])) {

    // Atualiza todas as notificações do usuário como lidas
    $up = $conn->prepare("UPDATE notificacoes_ponto SET lida = 1 WHERE id_usuario = ?");
    $up->bind_param("i", $id_usuario);
    $up->execute();

    // Recarrega a página após atualizar
    header("Location: notificacoes.php");
    exit;
}

/* ====
FILTROS
==== */
// Captura os filtros da URL
$f_from   = $_GET['from'] ?? '';                // Data inicial
$f_to     = $_GET['to'] ?? '';                  // Data final
$somente_nao_lidas = $_GET['nao_lidas'] ?? '';  // Filtro de não lidas

// Filtro base: só buscar notificações do usuário logado
$where = ["n.id_usuario = ?"];
$params = [$id_usuario];
$types = "i";

// Filtro por data inicial
if (!empty($f_from)) {
    $where[] = "DATE(n.data_notificacao) >= ?";
    $params[] = $f_from;
    $types .= "s";
}

// Filtro por data final
if (!empty($f_to)) {
    $where[] = "DATE(n.data_notificacao) <= ?";
    $params[] = $f_to;
    $types .= "s";
}

// Filtro para mostrar apenas não lidas
if ($somente_nao_lidas) {
    $where[] = "n.lida = 0";
}

// ============================
// BUSCAR NOTIFICAÇÕES NO BANCO
// ============================
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

// Prepara consulta
$stmt = $conn->prepare($sql);

// Aplica os parâmetros
$stmt->bind_param($types, ...$params);

// Executa a consulta
$stmt->execute();

// Resultado
$notificacoes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Notificações</title>
    <style>
        /* Estilo especial para notificações não lidas */
        .nao-lida {
            background: #fff3cd;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Notificações</h1>
    <a href="../index.php">Voltar</a>
    <br><br>

    <!-- FORMULÁRIO DE FILTROS -->
    <form method="get" style="margin-bottom: 10px;">

        <!-- Filtro por data inicial -->
        <label>De:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">

        <!-- Filtro por data final -->
        <label>Até:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">

        <!-- Checkbox para mostrar só as não lidas -->
        <label>
            <input type="checkbox" name="nao_lidas" value="1" <?= $somente_nao_lidas ? 'checked' : '' ?>>
            Somente não lidas
        </label>

        <button type="submit">Filtrar</button>
    </form>

    <!-- BOTÃO MARCAR TODAS COMO LIDAS -->
    <form method="post">
        <button type="submit" name="marcar_lidas">
            Marcar todas como lidas
        </button>
    </form>
    <br>

    <!-- TABELA DE NOTIFICAÇÕES -->
    <table border="1" cellpadding="8">
        <tr>
            <th>Data</th>
            <th>Mensagem</th>
            <th>Status</th>
            <th>Ação</th>
        </tr>

        <!-- LOOP DAS NOTIFICAÇÕES -->
        <?php while ($row = $notificacoes->fetch_assoc()): ?>
            <tr class="<?= $row['lida'] == 0 ? 'nao-lida' : '' ?>">

                <!-- Data formatada -->
                <td><?= date("d/m/Y H:i", strtotime($row['data_notificacao'])) ?></td>

                <!-- Mensagem segura contra XSS -->
                <td><?= htmlspecialchars($row['mensagem']) ?></td>

                <!-- Status -->
                <td><?= $row['lida'] == 1 ? 'Lida' : 'Não lida' ?></td>

                <!-- Link para o ponto daquele dia -->
                <td>
                    <a href="../ponto/historico.php?from=<?= $row['data_ponto'] ?>&to=<?= $row['data_ponto'] ?>">
                    <a href="../ponto/historico.php?from=<?= $row['data_ponto'] ?>&to=<?= $row['data_ponto'] ?>">
                        Ver dia
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>