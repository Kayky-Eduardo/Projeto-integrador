<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
date_default_timezone_set('America/Sao_Paulo');

$id_usuario = $_SESSION['id_usuario'];
$hoje = date("Y-m-d");

// busca registro do dia
$q = $conn->prepare("SELECT * FROM ponto_dia WHERE id_usuario = ? AND data_reg = ?");
$q->bind_param("is", $id_usuario, $hoje);
$q->execute();
$res = $q->get_result();
$reg = $res->fetch_assoc();

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Status do Ponto - Hoje</title>
</head>

<body>
    <p>
        <a href="../index.php">Voltar ao início</a>
    </p>

    <h1>Status do Ponto — <?php echo $hoje; ?></h1>

    <?php if (!$reg): ?>
        <p>Você ainda não bateu ponto hoje.</p>
        <form action="bater.php" method="post" id="form-bater">
            <button type="submit">Bater Ponto (Entrada)</button>
        </form>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Evento</th>
                <th>Horário</th>
            </tr>
            <tr>
                <td>Entrada</td>
                <td><?php echo $reg['inicio_ponto'] ?? 'Pendente'; ?></td>
            </tr>
            <tr>
                <td>Início do Almoço</td>
                <td><?php echo $reg['inicio_almoco'] ?? 'Pendente'; ?></td>
            </tr>
            <tr>
                <td>Fim do Almoço</td>
                <td><?php echo $reg['fim_almoco'] ?? 'Pendente'; ?></td>
            </tr>
            <tr>
                <td>Saída</td>
                <td><?php echo $reg['fim_ponto'] ?? 'Pendente'; ?></td>
            </tr>
            <tr>
                <td>Status</td>
                <td><?php echo $reg['status']; ?></td>
            </tr>
        </table>

        <br>
        <!-- botão pra bater próxima ação -->
        <form action="bater.php" method="post" id="form-bater">
            <button type="submit">Bater Próxima Ação</button>
        </form>
    <?php endif; ?>

    <script>
        // opcional: transformar os formulários em fetch (AJAX) para não recarregar a página
        document.querySelectorAll('form#form-bater').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('bater.php', {
                        method: 'POST'
                    })
                    .then(r => r.json())
                    .then(data => {
                        alert(data.mensagem);
                        location.reload();
                    })
                    .catch(err => {
                        alert('Erro na requisição.');
                        console.error(err);
                    });
            });
        });
    </script>
</body>

</html>