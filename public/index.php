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
    <link rel="stylesheet" href="../assets/estilo.css">
</head>

<body>

    <!-- Banner / Topo -->
    <header role="banner">
        <h1>Sistema de RH</h1>
    </header>

    <!-- Navbar -->
    <nav role="navigation" aria-label="Menu principal">
        <?php include("../include/navbar.php"); ?>
    </nav>

    <!-- Conteúdo Principal -->
    <main role="main" aria-label="Conteúdo principal">
        <!-- 
            INSERIR AQUI CONTEÚDO DA PÁGINA PRINCIPAL 
        -->
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
</body>

</html>