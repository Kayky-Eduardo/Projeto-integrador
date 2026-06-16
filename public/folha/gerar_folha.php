<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
$nome_usuario = $_SESSION['nome_usuario'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerar Folha de Pagamento</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Visualizar Folha de Pagamento</h1>

            <section class="container ver-folha">
                <h2>Olá, <?php echo $nome_usuario; ?></h2>

                <form class="form" id="formFolha">
                    <article>
                        <label class="label" for="mes">Selecione o mês de referência:</label>
                        <input class="input" type="month" id="mes" min="2024-01" max="<?= date('Y-m'); ?>" required>
                    </article>

                    <button type="button" class="btn btn-padrao" id="btnGerarPDF">
                        Ver Folha
                    </button>
                </form>
            </section>
        </section>

        <script src="../../assets/js/script.js"></script>
    </main>
</body>

</html>