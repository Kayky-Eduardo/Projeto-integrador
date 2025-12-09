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
        SELECT inicio_ponto, fim_ponto
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

/* =======
QUERY BASE
======= */
$sql = "
    SELECT 
        p.*, 
        u.nome_usuario AS nome,
        ps.inicio AS inicio_pausa,       -- Novo
        ps.fim AS fim_pausa,             -- Novo
        pc.descricao_pausa               -- Novo
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
    -- O JOIN CRÍTICO: LIGAÇÃO POR CHAVE COMPOSTA (ID do Usuário e Data)
    LEFT JOIN pausa ps ON ps.id_usuario = p.id_usuario AND ps.data = p.data_ponto
    LEFT JOIN pausa_config pc ON pc.id_config = ps.id_config
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

// NOVO PROCESSAMENTO: Agrupar resultados por ID do Ponto
$pontos_agrupados = [];

while ($row = $result->fetch_assoc()) {
    $id_ponto = $row['id_ponto'];

    // Se é a primeira vez que vemos este ponto, inicialize o registro principal
    if (!isset($pontos_agrupados[$id_ponto])) {
        // Armazena todos os dados do dia, menos os específicos de pausa
        $pontos_agrupados[$id_ponto] = [
            'data_ponto'   => $row['data_ponto'],
            'nome'         => $row['nome'],
            'inicio_ponto' => $row['inicio_ponto'],
            'fim_ponto'    => $row['fim_ponto'],
            'status'       => $row['status'],
            'id_ponto'     => $row['id_ponto'],
            'pausas'       => [], // Array para armazenar todas as pausas
        ];
    }

    // Se houver dados de pausa na linha (devido ao JOIN)
    if (!empty($row['inicio_pausa'])) {
        $pontos_agrupados[$id_ponto]['pausas'][] = [
            'descricao_pausa' => $row['descricao_pausa'],
            'inicio'          => $row['inicio_pausa'],
            'fim'             => $row['fim_pausa'],
        ];
    }
}
// Agora, $pontos_agrupados é o array que você irá iterar no HTML.
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

    <table border="1" cellpadding="6">
        <tr>
            <th>Data</th>
            <th>Funcionário</th>
            <th>Entrada</th>
            <th>Saída</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>

        <?php foreach($pontos_agrupados as $r): ?>
            <tr>

                <!-- Data formatada -->
                <td><?= date("d/m/Y", strtotime($r['data_ponto'])) ?></td>

                <!-- Nome do funcionário -->
                <td><?= htmlspecialchars($r['nome']) ?></td>

                <!-- Horários -->
                <td><?= $r['inicio_ponto'] ? date("H:i", strtotime($r['inicio_ponto'])) : '-' ?></td>
                <td><?= $r['fim_ponto'] ? date("H:i", strtotime($r['fim_ponto'])) : '-' ?></td>

                <!-- Pausas -->
                <td>
                    <?php foreach($r['pausas'] as $pausa): ?>
                        <?php 
                            $pausa_inicio = ($pausa['inicio'] ? date("H:i", strtotime($pausa['inicio'] )) : '--:--');
                            $pausa_fim    = ($pausa['fim']    ? date("H:i", strtotime($pausa['fim']    )) : '--:--');
                            echo htmlspecialchars($pausa['descricao_pausa']) . ": " . $pausa_inicio . ":" . $pausa_fim . "<br>";
                        ?>
                    <?php endforeach; ?>
                </td>

                <!-- Status -->
                <td><?= $r['status'] ?></td>

                <!-- AÇÕES -->
                <td>

                    <!-- Aprovar somente se ainda não estiver aprovado -->
                    <?php if ($r['status'] !== 'Aprovado'): ?>
                        <a href="gerenciar.php?aprovar=<?= $r['id_ponto'] ?>">Aprovar</a>
                    <?php endif; ?>

                    <!-- Permitir ajuste se não estiver aprovado -->
                    <?php if ($r['status'] === 'Finalizado' || $r['status'] === 'Em Andamento'): ?>
                        |
                        <a href="../ponto/solicitar.php?id_ponto=<?= $r['id_ponto'] ?>">
                            Solicitar Ajuste
                        </a>
                    <?php endif; ?>

                </td>

            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>