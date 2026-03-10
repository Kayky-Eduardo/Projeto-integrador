<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
date_default_timezone_set('America/Sao_Paulo');

if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

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

if (isset($_GET['aprovar'])) {
    $id = intval($_GET['aprovar']);

    $check = $conn->query("
        SELECT inicio_ponto, fim_ponto
        FROM ponto_dia
        WHERE id_ponto = $id
    ")->fetch_assoc();

    if (!$check) {
        die("Registro não encontrado.");
    }

    if (
        !$check['inicio_ponto'] ||
        !$check['fim_ponto']
    ) {
        die("Não é possível aprovar: ponto incompleto.");
    }

    $stmt = $conn->prepare("UPDATE ponto_dia SET status = 'Aprovado' WHERE id_ponto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $coleta_tempo = $conn->prepare("
        SELECT
        TIMESTAMPDIFF(MINUTE, inicio_ponto, fim_ponto) as duracao
        FROM ponto_dia
        WHERE id_ponto = ?;
    ");

    $coleta_tempo->bind_param("i", $id);
    $coleta_tempo->execute();
    $resultado = $coleta_tempo->get_result();
    $resultado = $resultado->fetch_assoc()['duracao'];

    $user = $conn->query("
        SELECT id_usuario 
        FROM ponto_dia 
        WHERE id_ponto = $id
    ")->fetch_assoc();

    $dados = [
        "resultado" => $resultado,
        "mensagem" => "Ponto aprovado"
    ];

    criar_notificacao($conn, $user['id_usuario'], $id, "Seu ponto foi aprovado.");
    header("Location: gerenciar.php");
    exit;
}

$data_from = $_GET['from'] ?? '';
$data_to   = $_GET['to'] ?? '';
$nome      = $_GET['nome'] ?? '';
$status    = $_GET['status'] ?? '';
$where  = [];
$params = [];
$types  = '';

$sql = "
    SELECT 
        p.*, 
        u.nome_usuario AS nome,
        ps.inicio AS inicio_pausa,
        ps.fim AS fim_pausa,
        pc.descricao_pausa
    FROM ponto_dia p
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
    LEFT JOIN pausa ps ON ps.id_usuario = p.id_usuario AND ps.data = p.data_ponto
    LEFT JOIN pausa_config pc ON pc.id_config = ps.id_config
";

if ($data_from) {
    $where[]  = "p.data_ponto >= ?";
    $params[] = $data_from;
    $types   .= 's';
}

if ($data_to) {
    $where[]  = "p.data_ponto <= ?";
    $params[] = $data_to;
    $types   .= 's';
}

if ($nome) {
    $where[]  = "u.nome_usuario LIKE ?";
    $params[] = "%$nome%";
    $types   .= 's';
}

if ($status) {
    $where[]  = "p.status = ?";
    $params[] = $status;
    $types   .= 's';
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    ORDER BY CASE p.status
        WHEN 'Revisar' THEN 1
        WHEN 'Finalizado' THEN 2
        WHEN 'Em Andamento' THEN 3
        WHEN 'Aprovado' THEN 4
        ELSE 5
    END,
    p.data_ponto DESC,
    p.inicio_ponto ASC
";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$pontos_agrupados = [];

while ($row = $result->fetch_assoc()) {
    $id_ponto = $row['id_ponto'];

    if (!isset($pontos_agrupados[$id_ponto])) {
        $pontos_agrupados[$id_ponto] = [
            'data_ponto'   => $row['data_ponto'],
            'nome'         => $row['nome'],
            'inicio_ponto' => $row['inicio_ponto'],
            'fim_ponto'    => $row['fim_ponto'],
            'status'       => $row['status'],
            'id_ponto'     => $row['id_ponto'],
            'pausas'       => [],
        ];
    }

    if (!empty($row['inicio_pausa'])) {
        $pontos_agrupados[$id_ponto]['pausas'][] = [
            'descricao_pausa' => $row['descricao_pausa'],
            'inicio'          => $row['inicio_pausa'],
            'fim'             => $row['fim_pausa'],
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Ponto</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Gerenciar Ponto</h1>

            <section class="container filtro-padrao">

                <form method="get" class="form-linha">
                    <article>
                        <label class="label">De:</label>
                        <input type="date" name="from" class="input" value="<?= htmlspecialchars($data_from) ?>">
                    </article>

                    <article>
                        <label class="label">Até:</label>
                        <input type="date" name="to" class="input" value="<?= htmlspecialchars($data_to) ?>">
                    </article>

                    <article>
                        <label class="label">Nome:</label>
                        <input type="text" name="nome" placeholder="Digite o nome do usuário" class="input" value="<?= htmlspecialchars($nome) ?>">
                    </article>

                    <article>
                        <label class="label">Status:</label>
                        <select name="status" class="select-padrao">
                            <option value="">Todos</option>
                            <option value="Em Andamento" <?= $status == 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="Finalizado" <?= $status == 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            <option value="Revisar" <?= $status == 'Revisar' ? 'selected' : '' ?>>Revisar</option>
                            <option value="Aprovado" <?= $status == 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
                        </select>
                    </article>

                    <button type="submit" class="btn btn-padrao">Filtrar</button>
                </form>
            </section>

            <section class="tabela-padrao">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Funcionário</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                            <th>Pausas</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($pontos_agrupados as $r): ?>
                            <tr>
                                <td><?= date("d/m/Y", strtotime($r['data_ponto'])) ?></td>
                                <td><?= htmlspecialchars($r['nome']) ?></td>
                                <td><?= $r['inicio_ponto'] ? date("H:i", strtotime($r['inicio_ponto'])) : '-' ?></td>
                                <td><?= $r['fim_ponto'] ? date("H:i", strtotime($r['fim_ponto'])) : '-' ?></td>

                                <td>
                                    <?php foreach ($r['pausas'] as $pausa): ?>
                                        <?php
                                        $pausa_inicio = ($pausa['inicio'] ? date("H:i", strtotime($pausa['inicio'])) : '--:--');
                                        $pausa_fim    = ($pausa['fim']    ? date("H:i", strtotime($pausa['fim'])) : '--:--');
                                        echo htmlspecialchars($pausa['descricao_pausa']) . " - " . $pausa_inicio . " - " . $pausa_fim . "<br>";
                                        ?>
                                    <?php endforeach; ?>
                                </td>

                                <td><?= $r['status'] ?></td>

                                <td>
                                    <?php if ($r['status'] !== 'Aprovado' && $r['status'] !== 'Em Andamento' && $r['status'] !== 'Revisar'): ?>
                                        <a class="btn-link btn-padrao" href="gerenciar.php?aprovar=<?= $r['id_ponto'] ?>">Aprovar</a>
                                    <?php endif; ?>


                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>
</body>

</html>