<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);
date_default_timezone_set('America/Sao_Paulo');

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'];

// Data atual no formato YYYY-MM-DD
$hoje = date("Y-m-d");

// Busca o ponto do usuário no dia de hoje
$q = $conn->prepare("SELECT * FROM ponto_dia WHERE id_usuario = ? AND data_ponto = ?");
$q->bind_param("is", $id_usuario, $hoje);
$q->execute();

// Obtém o resultado da consulta
$res = $q->get_result();

// Recupera os dados do ponto (ou null se não existir)
$reg = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Status do Ponto</title>
</head>

<body>

    <!-- Link de navegação -->
    <a href="../index.php">Voltar</a>

    <!-- Título da página -->
    <h1>Controle de Ponto - <?= $hoje ?></h1>

    <?php if (!$reg): ?>
        <!-- Caso o usuário ainda não tenha batido ponto hoje -->
        <p>Você ainda não bateu ponto hoje.</p>

        <!-- Botão para registrar entrada -->
        <form action="bater.php" method="post" id="form-bater">
            <button>Bater Entrada</button>
        </form>

    <?php else: ?>
        <!-- Tabela com status de cada evento do dia -->
        <table border="1" cellpadding="8">
            <tr>
                <th>Evento</th>
                <th>Horário</th>
            </tr>

            <!-- Coluna de entrada -->
            <tr>
                <td>Entrada</td>
                <td><?= $reg['inicio_ponto'] ?? 'Pendente' ?></td>
            </tr>

            <!-- Início de almoço -->
            <tr>
                <td>Início Almoço</td>
                <td><?= $reg['inicio_almoco'] ?? 'Pendente' ?></td>
            </tr>

            <!-- Fim de almoço -->
            <tr>
                <td>Fim Almoço</td>
                <td><?= $reg['fim_almoco'] ?? 'Pendente' ?></td>
            </tr>

            <!-- Saída -->
            <tr>
                <td>Saída</td>
                <td><?= $reg['fim_ponto'] ?? 'Pendente' ?></td>
            </tr>

            <!-- Status atual do ponto -->
            <tr>
                <td>Status</td>
                <td><?= $reg['status'] ?></td>
            </tr>
        </table>

        <!-- Só mostra botão se ainda não estiver aprovado -->
        <?php if ($reg['status'] !== 'Aprovado'): ?>
            <form action="bater.php" method="post" id="form-bater">
                <button>Bater Próxima Ação</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        // Intercepta o envio do formulário para evitar reload padrão
        document.querySelectorAll('#form-bater').forEach(form => {
            form.addEventListener('submit', e => {
                e.preventDefault();

                // Envia a batida via fetch
                fetch('bater.php', {
                        method: 'POST'
                    })
                    .then(r => r.json())
                    // Mostra a mensagem vinda do PHP
                    .then(d => {
                        alert(d.mensagem);
                        location.reload(); // Atualiza tela
                    })
                    .catch(() => alert('Erro.'));
            });
        });
    </script>
</body>

</html>