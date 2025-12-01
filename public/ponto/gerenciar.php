<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// Somente RH
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

date_default_timezone_set('America/Sao_Paulo');

/* =======================
   FUNÇÃO NOTIFICAÇÃO
======================= */
function criar_notificacao($conn, $id_usuario, $id_ponto, $msg)
{
    $stmt = $conn->prepare("
        INSERT INTO notificacoes_ponto
        (id_usuario, id_ponto, mensagem, data_notificacao)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iis", $id_usuario, $id_ponto, $msg);
    $stmt->execute();
}

/* =======================
        APROVAR
======================= */
if (isset($_GET['aprovar'])) {

    $id = intval($_GET['aprovar']);

    // Verifica se todos os horários estão preenchidos
    $check = $conn->query("
        SELECT inicio_ponto, inicio_almoco, fim_almoco, fim_ponto
        FROM ponto_dia
        WHERE id_ponto = $id
    ")->fetch_assoc();

    if (!$check) {
        die("Registro não encontrado.");
    }

    if (
        !$check['inicio_ponto'] ||
        !$check['inicio_almoco'] ||
        !$check['fim_almoco']  ||
        !$check['fim_ponto']
    ) {
        die("Não é possível aprovar: ponto incompleto.");
    }

    // Atualiza Status
    $stmt = $conn->prepare("UPDATE ponto_dia SET status = 'Aprovado' WHERE id_ponto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Notifica usuário
    $user = $conn->query("SELECT id_usuario FROM ponto_dia WHERE id_ponto = $id")->fetch_assoc();
    criar_notificacao($conn, $user['id_usuario'], $id, "Seu ponto foi aprovado.");

    header("Location: gerenciar.php");
    exit;
}

/* =======================
          FILTROS
======================= */
$data_from = $_GET['from'] ?? '';
$data_to   = $_GET['to'] ?? '';
$nome      = $_GET['nome'] ?? '';
$status    = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

$sql = "
    SELECT p.*, u.nome_usuario
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
";

/* --- filtros --- */
if ($data_from) {
    $where[] = "p.data_reg >= ?";
    $params[] = $data_from;
    $types .= 's';
}

if ($data_to) {
    $where[] = "p.data_reg <= ?";
    $params[] = $data_to;
    $types .= 's';
}

if ($nome) {
    $where[] = "u.nome_usuario LIKE ?";
    $params[] = "%$nome%";
    $types .= 's';
}

if ($status) {
    $where[] = "p.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY 
    CASE p.status
        WHEN 'Revisar' THEN 1
        WHEN 'Finalizado' THEN 2
        WHEN 'Em Andamento' THEN 3
        WHEN 'Aprovado' THEN 4
        ELSE 5
    END,
    p.data_reg DESC,
    p.inicio_ponto ASC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Ponto</title>
</head>

<body>

    <a href="../index.php">Voltar</a>
    <h1>Gerenciar Ponto</h1>

    <form method="get">
        <label>De:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($data_from) ?>">

        <label>Até:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($data_to) ?>">

        <label>Nome:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>">

        <label>Status:</label>
        <select name="status">
            <option value="">Todos</option>
            <option value="Em Andamento" <?= $status == 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
            <option value="Finalizado" <?= $status == 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
            <option value="Revisar" <?= $status == 'Revisar' ? 'selected' : '' ?>>Revisar</option>
            <option value="Aprovado" <?= $status == 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
        </select>

        <button type="submit">Filtrar</button>
    </form>

    <br>

    <table border="1" cellpadding="6">
        <tr>
            <th>Data</th>
            <th>Funcionário</th>
            <th>Entrada</th>
            <th>Início Almoço</th>
            <th>Fim Almoço</th>
            <th>Saída</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>

                <td><?= date("d/m/Y", strtotime($row['data_reg'])) ?></td>
                <td><?= htmlspecialchars($row['nome_usuario']) ?></td>

                <td><?= $row['inicio_ponto'] ? date("H:i", strtotime($row['inicio_ponto'])) : '-' ?></td>
                <td><?= $row['inicio_almoco'] ? date("H:i", strtotime($row['inicio_almoco'])) : '-' ?></td>
                <td><?= $row['fim_almoco'] ? date("H:i", strtotime($row['fim_almoco'])) : '-' ?></td>
                <td><?= $row['fim_ponto'] ? date("H:i", strtotime($row['fim_ponto'])) : '-' ?></td>

                <td><?= $row['status'] ?></td>

                <td>

                    <?php if ($row['status'] !== 'Aprovado'): ?>
                        <a href="gerenciar.php?aprovar=<?= $row['id_ponto'] ?>">Aprovar</a>
                    <?php endif; ?>

                    <?php if ($row['status'] === 'Finalizado' || $row['status'] === 'Em Andamento'): ?>
                        |
                        <a href="../ponto/solicitar.php?id_ponto=<?= $row['id_ponto'] ?>">
                            Solicitar Ajuste
                        </a>
                    <?php endif; ?>

                </td>

            </tr>
        <?php endwhile; ?>
    </table>

</body>

</html>