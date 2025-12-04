<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// Restringe acesso apenas para RH / nível 2+
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

// Define fuso horário padrão
date_default_timezone_set('America/Sao_Paulo');

// =====================
// FUNÇÃO DE NOTIFICAÇÃO
// =====================
// Cria avisos para o funcionário
function criar_notificacao($conn, $id_usuario, $id_ponto, $msg)
{
    // Insere notificação na tabela
    $stmt = $conn->prepare("
        INSERT INTO notificacoes_ponto
        (id_usuario, id_ponto, mensagem, data_notificacao)
        VALUES (?, ?, ?, NOW())
    ");

    // Associa parâmetros
    $stmt->bind_param("iis", $id_usuario, $id_ponto, $msg);

    // Executa insert
    $stmt->execute();
}

// =========
// APROVAÇÃO
// =========
// Acionado se existir ?aprovar=ID
if (isset($_GET['aprovar'])) {

    // ID do ponto a aprovar
    $id = intval($_GET['aprovar']);

    // Busca se o ponto está completo
    $check = $conn->query("
        SELECT inicio_ponto, inicio_almoco, fim_almoco, fim_ponto
        FROM ponto_dia
        WHERE id_ponto = $id
    ")->fetch_assoc();

    // Se não existir registro
    if (!$check) {
        die("Registro não encontrado.");
    }

    // Impede aprovação de ponto incompleto
    if (
        !$check['inicio_ponto'] ||
        !$check['inicio_almoco'] ||
        !$check['fim_almoco']  ||
        !$check['fim_ponto']
    ) {
        die("Não é possível aprovar: ponto incompleto.");
    }

    // Atualiza status para Aprovado
    $stmt = $conn->prepare("UPDATE ponto_dia SET status = 'Aprovado' WHERE id_ponto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Busca usuário dono do ponto
    $user = $conn->query("
        SELECT id_usuario 
        FROM ponto_dia 
        WHERE id_ponto = $id
    ")->fetch_assoc();

    // Envia notificação para o funcionário
    criar_notificacao($conn, $user['id_usuario'], $id, "Seu ponto foi aprovado.");

    // Redireciona após aprovação
    header("Location: gerenciar.php");
    exit;
}

//*=======
// FILTROS
// =======
// Recebidos por GET
$data_from = $_GET['from'] ?? '';
$data_to   = $_GET['to'] ?? '';
$nome      = $_GET['nome'] ?? '';
$status    = $_GET['status'] ?? '';

// Arrays para montar SQL dinâmico
$where  = [];
$params = [];
$types  = '';

// Query base
$sql = "
    SELECT p.*, u.nome_usuario
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
";

// ==============
// APLICA FILTROS
// ==============

// Filtro por data inicial
if ($data_from) {
    $where[]  = "p.data_ponto >= ?";
    $params[] = $data_from;
    $types   .= 's';
}

// Filtro por data final
if ($data_to) {
    $where[]  = "p.data_ponto <= ?";
    $params[] = $data_to;
    $types   .= 's';
}

// Filtro por nome do funcionário
if ($nome) {
    $where[]  = "u.nome_usuario LIKE ?";
    $params[] = "%$nome%";
    $types   .= 's';
}

// Filtro por status
if ($status) {
    $where[]  = "p.status = ?";
    $params[] = $status;
    $types   .= 's';
}

// Se existir ao menos 1 filtro, cria o WHERE
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// =========
// ORDENAÇÃO
// =========
$sql .= " ORDER BY 
    CASE p.status
        WHEN 'Revisar' THEN 1
        WHEN 'Finalizado' THEN 2
        WHEN 'Em Andamento' THEN 3
        WHEN 'Aprovado' THEN 4
        ELSE 5
    END,
    p.data_ponto DESC,
    p.inicio_ponto ASC
";

// Prepara SQL
$stmt = $conn->prepare($sql);

// Aplica parâmetros se houver filtros
if ($params) {
    $stmt->bind_param($types, ...$params);
}

// Executa busca
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

    <!-- Navegação -->
    <a href="../index.php">Voltar</a>
    <h1>Gerenciar Ponto</h1>

    <!-- FORMULÁRIO DE FILTROS -->
    <form method="get">

        <label>De:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($data_from) ?>">

        <label>Até:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($data_to) ?>">

        <label>Nome:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>">

        <!-- Filtro por Status -->
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

    <!-- TABELA DE RESULTADOS -->
     <?php
        $listSql = "SELECT * FROM pausa_config ORDER BY id_config ASC";
        $listRes = $conn->query($listSql);
     ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Data</th>
            <th>Funcionário</th>
            <th>Entrada</th>
            <th>Saída</th>
            <?php while ($rowP = $listRes->fetch_assoc()): ?>
                <td><?= htmlspecialchars($rowP['descricao_pausa']) ?></td>
            <?php endwhile; ?>
            <th>Status</th>
            <th>Ações</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>

                <!-- Data formatada -->
                <td><?= date("d/m/Y", strtotime($row['data_ponto'])) ?></td>

                <!-- Nome do funcionário -->
                <td><?= htmlspecialchars($row['nome_usuario']) ?></td>

                <!-- Horários -->
                <td><?= $row['inicio_ponto'] ? date("H:i", strtotime($row['inicio_ponto'])) : '-' ?></td>
                <td><?= $row['fim_ponto'] ? date("H:i", strtotime($row['fim_ponto'])) : '-' ?></td>

                <!-- Pausas -->
                <?php
                    $timeSql = "SELECT * FROM pausa ORDER BY id_config ASC";
                    $timeRes = $conn->query($timeSql);
                 while ($rowT = $timeRes->fetch_assoc()): ?>
                    <td>
                        <?= htmlspecialchars(
                            (!empty($rowT['inicio']) ? date('H:i', strtotime($rowT['inicio'])) : '00:00')
                            . " - " .
                            (!empty($rowT['fim']) ? date('H:i', strtotime($rowT['fim'])) : '00:00')
                        ) ?>
                    </td>
                <?php endwhile; ?>
                <!-- Status -->
                <td><?= $row['status'] ?></td>

                <!-- AÇÕES -->
                <td>

                    <!-- Aprovar somente se ainda não estiver aprovado -->
                    <?php if ($row['status'] !== 'Aprovado'): ?>
                        <a href="gerenciar.php?aprovar=<?= $row['id_ponto'] ?>">Aprovar</a>
                    <?php endif; ?>

                    <!-- Permitir ajuste se não estiver aprovado -->
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