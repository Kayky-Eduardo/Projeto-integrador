<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
date_default_timezone_set('America/Sao_Paulo');


$id_usuario = $_SESSION['id_usuario'];
$hoje = date("Y-m-d");


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
    <title>Status do Ponto</title>
</head>

<body>
    <a href="../index.php">Voltar</a>
    <h1>Controle de Ponto - <?= $hoje ?></h1>


    <?php if (!$reg): ?>
        <p>Você ainda não bateu ponto hoje.</p>
        <form action="bater.php" method="post" id="form-bater"><button>Bater Entrada</button></form>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Evento</th>
                <th>Horário</th>
            </tr>
            <tr>
                <td>Entrada</td>
                <td><?= $reg['inicio_ponto'] ?? 'Pendente' ?></td>
            </tr>
            <tr>
                <td>Início Almoço</td>
                <td><?= $reg['inicio_almoco'] ?? 'Pendente' ?></td>
            </tr>
            <tr>
                <td>Fim Almoço</td>
                <td><?= $reg['fim_almoco'] ?? 'Pendente' ?></td>
            </tr>
            <tr>
                <td>Saída</td>
                <td><?= $reg['fim_ponto'] ?? 'Pendente' ?></td>
            </tr>
            <tr>
                <td>Status</td>
                <td><?= $reg['status'] ?></td>
            </tr>
        </table>


        <?php if ($reg['status'] !== 'Aprovado'): ?>
            <form action="bater.php" method="post" id="form-bater"><button>Bater Próxima Ação</button></form>
        <?php endif; ?>
    <?php endif; ?>


    <script>
        document.querySelectorAll('#form-bater').forEach(form => {
            form.addEventListener('submit', e => {
                e.preventDefault();
                fetch('bater.php', {
                        method: 'POST'
                    })
                    .then(r => r.json())
                    .then(d => {
                        alert(d.mensagem);
                        location.reload();
                    })
                    .catch(() => alert('Erro.'));
            });
        });
    </script>
</body>

</html>