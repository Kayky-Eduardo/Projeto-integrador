<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// Somente RH/Admin
if ($_SESSION['nivel'] < 3) {
    die("Acesso restrito.");
}

date_default_timezone_set('America/Sao_Paulo');

/* ---------------------------------------------------------
        FUNÇÃO PARA GERAR NOTIFICAÇÃO
--------------------------------------------------------- */
function criar_notificacao($conn, $id_usuario, $id_ponto, $mensagem)
{
    $sql = "INSERT INTO notificacoes_ponto (id_usuario, id_ponto, mensagem, data_notificacao)
            VALUES (?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $id_usuario, $id_ponto, $mensagem);
    $stmt->execute();
}

/* ---------------------------------------------------------
         AÇÕES DO RH
--------------------------------------------------------- */

// APROVAR
if (isset($_GET['aprovar'])) {
    $id = intval($_GET['aprovar']);

    // Atualiza status
    $up = $conn->prepare("UPDATE ponto_dia SET status = 'Aprovado' WHERE id_ponto = ?");
    $up->bind_param("i", $id);
    $up->execute();

    // Descobre o dono do ponto
    $q = $conn->prepare("SELECT id_usuario FROM ponto_dia WHERE id_ponto = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();

    // Cria notificação
    criar_notificacao($conn, $res["id_usuario"], $id, "Seu ponto foi aprovado.");

    header("Location: gerenciar.php");
    exit;
}

// SOLICITAR AJUSTE
if (isset($_GET['ajuste'])) {
    $id = intval($_GET['ajuste']);

    // Atualiza status
    $up = $conn->prepare("UPDATE ponto_dia SET status = 'Revisar' WHERE id_ponto = ?");
    $up->bind_param("i", $id);
    $up->execute();

    // Descobre o dono do ponto
    $q = $conn->prepare("SELECT id_usuario FROM ponto_dia WHERE id_ponto = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();

    // Cria notificação
    criar_notificacao($conn, $res["id_usuario"], $id, "Seu ponto precisa ser revisado.");

    header("Location: gerenciar.php");
    exit;
}

/* ---------------------------------------------------------
                       FILTROS
--------------------------------------------------------- */

$data_from = $_GET['from'] ?? '';
$data_to = $_GET['to'] ?? '';
$nome = $_GET['nome'] ?? '';

$where = [];
$params = [];
$types = '';

$sql = "SELECT p.*, u.nome_usuario 
        FROM ponto_dia p 
        INNER JOIN usuario u ON u.id_usuario = p.id_usuario";

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

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.data_reg DESC, u.nome_usuario ASC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gerenciar Ponto (RH)</title>
</head>
<body>

    <p><a href="../index.php">Voltar ao início</a></p>

    <h1>Gerenciar Ponto (RH)</h1>

    <form method="get">
        <label>De:</label>
        <input type="date" name="from" value="<?php echo htmlspecialchars($data_from); ?>">

        <label>Até:</label>
        <input type="date" name="to" value="<?php echo htmlspecialchars($data_to); ?>">

        <label>Nome:</label>
        <input type="text" name="nome" value="<?php echo htmlspecialchars($nome); ?>">

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
            <th>Ações (RH)</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['data_reg']; ?></td>
            <td><?php echo htmlspecialchars($row['nome_usuario']); ?></td>
            <td><?php echo $row['inicio_ponto'] ?? '-'; ?></td>
            <td><?php echo $row['inicio_almoco'] ?? '-'; ?></td>
            <td><?php echo $row['fim_almoco'] ?? '-'; ?></td>
            <td><?php echo $row['fim_ponto'] ?? '-'; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td>
                <?php if ($row['status'] !== 'Aprovado'): ?>
                    <a href="gerenciar.php?aprovar=<?php echo $row['id_ponto']; ?>">Aprovar</a> |
                <?php endif; ?>

                <?php if ($row['status'] !== 'Revisar'): ?>
                    <a href="gerenciar.php?ajuste=<?php echo $row['id_ponto']; ?>">Solicitar Ajuste</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

</body>
</html>
