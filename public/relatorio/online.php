<?php
session_start();
require_once __DIR__ . "/../../BD/conexao.php";
require_once "../../include/verificacao.php";
verificar_login($conn);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório ponto</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav><?php include("../../include/navbar.php"); ?></nav>

    <main class="main-center">
        <section class="controle-usuarios">
            <section class="container tabela-padrao">
                <h2>Usuários Logados</h2>
                <input class="input filtro-texto" type="text" id="filtro-online" placeholder="Digite o nome do usuário..." autocomplete="off">
            </section>

            <section class="container tabela-padrao">
                <table>
                    <thead>
                        <tr>
                            <th>Ação</th>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Entrada</th>
                            <th>Tempo logado</th>
                        </tr>
                    </thead>

                    <tbody id="tabela-online">
                        <tr>
                            <td colspan="5">-</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>