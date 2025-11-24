<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

// Se for RH/Admin, redireciona para a página correta
if ($nivel >= 3) {
    header("Location: ../rh/ajustes_pendentes.php");
    exit;
}

// Buscar notificações do usuário
$stmt = $conn->prepare("
    SELECT 
        n.id_notificacao,
        n.mensagem,
        n.data_notificacao,
        p.data_reg,
        p.id_ponto
    FROM notificacoes_ponto n
    INNER JOIN ponto_dia p ON p.id_ponto = n.id_ponto
    WHERE n.id_usuario = ?
    ORDER BY n.data_notificacao DESC
");

$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$notificacoes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Notificações</title>
</head>

<body>

    <h1>Notificações</h1>
    <a href="../index.php">Voltar</a>
    <br><br>

    <table border="1" cellpadding="8">
        <tr>
            <th>Data</th>
            <th>Mensagem</th>
            <th>Ação</th>
        </tr>

        <?php while ($row = $notificacoes->fetch_assoc()): ?>
            <tr>
                <td><?= date("d/m/Y H:i", strtotime($row['data_notificacao'])) ?></td>
                <td><?= htmlspecialchars($row['mensagem']) ?></td>
                <td>
                    <a href="../ponto/historico.php?from=<?= $row['data_reg'] ?>&to=<?= $row['data_reg'] ?>">
                        Ver dia
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</body>

</html>