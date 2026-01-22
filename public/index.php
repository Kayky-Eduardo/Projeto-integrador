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

    <style>
        /* 0 - VARIÁVEIS DO SISTEMA */
        :root {
            /* Cores principais */
            --cor-principal: #020617;
            --cor-principal-hover: #020617e8;
            --cor-destaque: #38bdf8;

            /* Texto */
            --cor-texto-padrao: #0D0D0D;
            --cor-texto-branco: #F2F2F2;
            --cor-texto-suave: #64748b;

            /* Fundos */
            --cor-fundo-pagina: #F2F2F2;
            --cor-fundo-card: #ffffff;
            --cor-fundo-nav-hover: #334155;

            /* Bordas */
            --raio-borda-padrao: 4px;
            --raio-borda-grande: 8px;

            /* Tipografia */
            --tamanho-texto-padrao: 0.9rem;
            --tamanho-titulo-slide: 1.5rem;
        }

        /* 1 - RESET GLOBAL */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background-color: var(--cor-fundo-pagina);
            color: var(--cor-texto-padrao);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 2 - LAYOUT GLOBAL */
        main {
            flex: 1;
            padding: 2rem;
        }

        header img {
            display: block;
            width: 100%;
        }

        /* 3 - NAVEGAÇÃO */
        nav {
            background-color: var(--cor-principal);
            display: flex;
            gap: 8px;
            padding: 0.6rem 1rem;
            justify-content: center;
        }

        nav a {
            text-decoration: none;
            color: var(--cor-texto-branco);
            padding: 0.4rem 0.8rem;
            border-radius: var(--raio-borda-padrao);
            font-size: var(--tamanho-texto-padrao);
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        nav a:hover {
            background-color: var(--cor-fundo-nav-hover);
            transform: translateY(-2px);
        }

        nav a:focus {
            outline: 2px solid var(--cor-destaque);
        }

        /* Dropdown Navbar */
        .nav-dropdown {
            list-style: none;
            padding: 0;
        }

        .nav-dropdown.usuario {
            margin-left: auto;
        }

        .dropdown {
            position: relative;
            display: flex;
            align-items: center;
        }

        .dropdown-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .dropdown-menu {
            list-style: none;
            position: absolute;
            top: 100%;
            background-color: var(--cor-principal);
            border: 1px solid #1e293b;
            border-radius: var(--raio-borda-grande);
            min-width: 190px;
            display: none;
            padding: 4px 0;
            z-index: 1000;
        }

        .dropdown-menu.left {
            left: 0;
        }

        .dropdown-menu.right {
            right: 0;
        }

        .dropdown-menu a {
            display: block;
            padding: 0.5rem 0.8rem;
            color: var(--cor-texto-branco);
            font-size: 0.85rem;
            text-decoration: none;
        }

        .dropdown-menu a:hover {
            background-color: var(--cor-fundo-nav-hover);
        }

        .dropdown-menu .separador {
            height: 1px;
            background-color: var(--cor-fundo-nav-hover);
            margin: 4px 0;
        }

        .dropdown-menu .sair {
            color: #f87171;
            font-weight: 600;
        }

        .dropdown:hover .dropdown-menu,
        .dropdown:focus-within .dropdown-menu {
            display: block;
        }

        /* 5 - ESTADOS E UTILS */
        /* ESTADOS E UTILITÁRIOS */
        .hidden {
            display: none !important;
        }

        /* 4 - CARROSSEL */
        .carrossel {
            max-width: 900px;
            width: 100%;
            margin: auto;
            overflow: hidden;
            position: relative;
        }

        .slides {
            list-style: none;
            position: relative;
            height: 500px;
        }

        .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }

        .slide.ativo {
            opacity: 1;
            z-index: 1;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--raio-borda-grande);
        }

        .slide article {
            position: relative;
            height: 100%;
        }

        .slide article::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 90px;
            background: rgba(15, 23, 42, 0.65);
            border-radius: 0 0 var(--raio-borda-grande) var(--raio-borda-grande);
            transition: background 0.4s ease;
        }

        .carrossel:hover .slide article::after {
            background: rgba(15, 23, 42, 0.85);
        }

        .slide h2 {
            position: absolute;
            bottom: 44px;
            left: 16px;
            color: var(--cor-texto-branco);
            font-size: var(--tamanho-titulo-slide);
            z-index: 2;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
        }

        .slide p {
            position: absolute;
            bottom: 14px;
            left: 16px;
            color: var(--cor-texto-branco);
            font-size: var(--tamanho-texto-padrao);
            z-index: 2;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
        }

        /* Botões do carrossel */
        .btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(15, 23, 42, 0.75);
            color: var(--cor-texto-branco);
            border: none;
            font-size: 1.8rem;
            padding: 0.3rem 0.8rem;
            cursor: pointer;
            border-radius: var(--raio-borda-padrao);
            z-index: 5;
        }

        .prev {
            left: 10px;
        }

        .next {
            right: 10px;
        }

        .btn:hover {
            background-color: rgba(15, 23, 42, 1);
        }

        /* Indicadores */
        .indicadores {
            position: absolute;
            top: 18px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 6;
        }

        .dot {
            width: 12px;
            height: 12px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .dot:hover {
            transform: scale(1.2);
        }

        .dot.ativo {
            background-color: #141640;
            transform: scale(1.3);
        }

        /* 5 - FOOTER */
        footer {
            background-color: var(--cor-principal);
            color: var(--cor-texto-branco);
            text-align: center;
            padding: 1.2rem;
            font-size: 0.85rem;
        }

        footer ul {
            list-style: none;
        }

        footer a {
            text-decoration: none;
            color: var(--cor-destaque);
        }

        /* 6 - RESPONSIVIDADE */
        @media (max-width: 1000px) {
            main {
                padding: 1.2rem;
            }

            nav {
                flex-direction: column;
            }

            nav a {
                text-align: center;
                border: 1px solid #1e293b;
            }

            /* Dropdown Navbar - Mobile */
            .nav-dropdown {
                width: 100%;
            }

            .dropdown {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .dropdown-toggle {
                width: 100%;
                justify-content: center;
            }

            .dropdown-menu {
                position: static;
                min-width: 100%;
                border-radius: 0 0 var(--raio-borda-grande) var(--raio-borda-grande);
                margin-top: 4px;
            }

            .dropdown-menu.right {
                left: auto;
                right: auto;
            }
        }
    </style>

    <!-- <link rel="stylesheet" href="../assets/css/estilo.css"> -->
</head>

<body>
    <header>
        <img src="../assets/img/banner_rh_pi.png" alt="">
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

                <li class="slide ativo">
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

            <button class="btn prev" aria-label="Notícia anterior">&#10094;</button>
            <button class="btn next" aria-label="Próxima notícia">&#10095;</button>
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