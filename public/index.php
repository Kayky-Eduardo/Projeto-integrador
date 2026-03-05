<?php
session_start();
include(__DIR__ . "/../BD/conexao.php");
require "../include/verificacao.php";
verificar_login($conn);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Sistema de RH</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
</head>

<body>
    <header>
        <img class="logo" src="../assets/img/banner_rh_pi.png" alt="">
    </header>

    <nav aria-label="Menu principal">
        <?php include("../include/navbar.php"); ?>
    </nav>

    <main>
        <section class="carrossel" aria-label="Notícias da empresa">
            <ul class="slides">
                <li class="indicadores" aria-label="Indicador de slides">
                    <button class="dot ativo" data-slide="0" aria-label="Slide 1"></button>
                    <button class="dot" data-slide="1" aria-label="Slide 2"></button>
                    <button class="dot" data-slide="2" aria-label="Slide 3"></button>
                </li>

                <li class="slide slide-ativo">
                    <article>
                        <img src="../assets/img/ambiente_coorporativo.jpg" alt="Ambiente corporativo">
                        <h2>Bem-vindo ao Sistema</h2>
                        <p>Fique por dentro das novidades internas.</p>
                    </article>
                </li>

                <li class="slide">
                    <article>
                        <img src="../assets/img/reuniao.webp" alt="Reunião da equipe">
                        <h2>Nova política interna</h2>
                        <p>Consulte as atualizações no RH.</p>
                    </article>
                </li>

                <li class="slide">
                    <article>
                        <img src="../assets/img/treinamento.jpg" alt="Treinamento de funcionários">
                        <h2>Treinamentos disponíveis</h2>
                        <p>Veja os cursos liberados para você.</p>
                    </article>
                </li>
            </ul>

            <button class="btn-carrossel prev" aria-label="Notícia anterior">&#10094;</button>
            <button class="btn-carrossel next" aria-label="Próxima notícia">&#10095;</button>
        </section>
    </main>

    <footer>
        <h3>Sistema de RH</h3>
        <p>&copy; 2025 Todos os direitos reservados.</p>

        <ul>
            <li><a href="#">Política de Privacidade</a></li>
            <li><a href="#">Termos de Uso</a></li>
            <li><a href="#">Contato</a></li>
        </ul>

        <address>
            <p><strong>Suporte:</strong> suporte@rhempresa.com</p>
            <p><strong>Telefone:</strong> (11) 99999-9999</p>
        </address>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>

</html>