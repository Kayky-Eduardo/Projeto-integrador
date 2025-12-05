<!-- 
    MUDANÇAS
        1. Refatoração da semântica HTML
        2. Implementação de tags para melhor organização do layout
        3. Estrutura preparada para crescimento do sistema

    ESTRUTURA SEMÂNTICA
        header    - Topo da página (identidade do sistema)
        nav       - Navegação principal e rotação de páginas
        main      - Conteúdo principal
        footer    - Rodapé institucional
        address   - Informações de contato

    ACESSIBILIDADE (ARIA & BOAS PRÁTICAS)
        role="banner"       - Indica cabeçalho principal para leitores de tela
        role="navigation"   -   ""   área de navegação
        role="main"         -   ""   região principal do sistema
        role="contentinfo"  -   ""   rodapé com informações institucionais
        aria-label          - Fornece descrição textual contextual para usuários de leitores de tela

    BENEFÍCIOS
        1. Melhor compreensão por tecnologias assistivas
        2. Melhor indexação em mecanismos de busca
        3. Facilidade de manutenção e expansão

    OBSERVAÇÕES TÉCNICAS
        1. Utilização de arquivo separado para o nav: navbar.php
        2. Estilos visuais centralizados em: ../assets/estilo.css
        3. Nada mudou da lógica PHP
-->

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
    <link rel="stylesheet" href="../assets/CSS/estilo.css">
</head>

<body>

    <!-- Banner / Topo -->
    <header role="banner">
        <img src="../assets/IMG/banner_rh_pi.png" alt="">
    </header>

    <!-- Navbar -->
    <nav role="navigation" aria-label="Menu principal">
        <?php include("../include/navbar.php"); ?>
    </nav>

    <!-- Conteúdo Principal -->
    <main role="main" aria-label="Conteúdo principal">

        <!-- Carrossel de Notícias -->
        <section class="carrossel" aria-label="Notícias da empresa">
            <ul class="slides">
                <nav class="indicadores" aria-label="Indicador de slides">
                    <button class="dot ativo" data-slide="0" aria-label="Slide 1"></button>
                    <button class="dot" data-slide="1" aria-label="Slide 2"></button>
                    <button class="dot" data-slide="2" aria-label="Slide 3"></button>
                </nav>

                <li class="slide ativo">
                    <article>
                        <img src="../assets/IMG/ambiente_coorporativo.jpg" alt="Ambiente corporativo">
                        <h2>Bem-vindo ao Sistema</h2>
                        <p>Fique por dentro das novidades internas.</p>
                    </article>
                </li>

                <li class="slide">
                    <article>
                        <img src="../assets/IMG/reuniao.webp" alt="Reunião da equipe">
                        <h2>Nova política interna</h2>
                        <p>Consulte as atualizações no RH.</p>
                    </article>
                </li>

                <li class="slide">
                    <article>
                        <img src="../assets/IMG/treinamento.jpg" alt="Treinamento de funcionários">
                        <h2>Treinamentos disponíveis</h2>
                        <p>Veja os cursos liberados para você.</p>
                    </article>
                </li>
            </ul>

            <button class="btn prev" aria-label="Notícia anterior">&#10094;</button>
            <button class="btn next" aria-label="Próxima notícia">&#10095;</button>
        </section>
    </main>

    <!-- Rodapé -->
    <footer role="contentinfo">
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

    <script src="../assets/JS/script.js"></script>
</body>

</html>