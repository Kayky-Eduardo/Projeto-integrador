<?php
session_start();
include("../../BD/conexao.php");
include("../../include/funcoes/funcoes_banco_horas.php");
include("../../include/verificacao.php");

verificar_login($conn);

$resumo = get_banco_horas($conn, $_SESSION['id_usuario']);
$historico = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inicio = $_POST['inicio'] ?? null;
    $fim = $_POST['fim'] ?? null;

    if ($inicio && $fim) {
        $historico = get_banco_data($conn, $_SESSION['id_usuario'], $inicio, $fim);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Meu Banco de Horas</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <header role="banner">
        <?php include("../../include/navbar.php"); ?>
        <h1>Sistema de RH</h1>
    </header>

    <main role="main" aria-label="Banco de horas do usuário">

        <!-- RESUMO -->
        <section aria-label="Resumo do banco de horas">
            <article>
                <h2><?= $resumo['saldo_antigo'] ?? '00:00' ?></h2>
                <p>Saldo anterior</p>
            </article>

            <article>
                <h2><?= $resumo['saldo_formatado'] ?? '00:00' ?></h2>
                <p>Saldo atual</p>
            </article>

            <article>
                <h2><?= $resumo['ultima_atualizacao'] ?? 'Sem registros' ?></h2>
                <p>Última atualização</p>
            </article>
        </section>

        <!-- FILTRO -->
        <section aria-label="Filtro por período">
            <form method="POST">
                <article>
                    <label for="inicio">Início</label>
                    <input type="date" name="inicio" id="inicio" required>
                </article>

                <article>
                    <label for="fim">Fim</label>
                    <input type="date" name="fim" id="fim" required>
                </article>

                <button type="submit">Aplicar</button>
            </form>
        </section>

        <!-- HISTÓRICO -->
        <section aria-label="Histórico de banco de horas">
            <?php if ($historico && is_array($historico)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Saldo anterior</th>
                            <th>Saldo atual</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td><?= $item['id_historico'] ?></td>
                                <td><?= $item['data'] ?></td>
                                <td><?= formatar_minutos($item['saldo_anterior']) ?></td>
                                <td><?= formatar_minutos($item['saldo_novo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <p>Nenhum registro encontrado para o período informado.</p>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>