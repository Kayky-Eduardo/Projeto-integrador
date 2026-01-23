<?php
/*
 * =============================================================
 * ARQUIVO: index.php
 * MÓDULO: Página Inicial do Sistema (Dashboard)
 * =============================================================
 * 
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Arquivo responsável pela exibição da página inicial do
 * sistema após a autenticação do usuário.
 *
 * Atua como ponto de entrada principal do sistema, exibindo:
 * - Banner institucional
 * - Menu de navegação principal
 * - Conteúdo informativo em formato de carrossel
 * - Rodapé institucional
 *
 * Não executa regras de negócio complexas nem operações diretas
 * de banco de dados além da verificação de sessão.
 *
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Inicia a sessão PHP
 * 2. Inclui o arquivo de conexão com o banco de dados
 * 3. Inclui o arquivo de verificação de autenticação
 * 4. Valida se o usuário está logado
 *    - Redireciona para login caso não esteja autenticado
 * 5. Renderiza a estrutura HTML da página inicial
 * 6. Carrega:
 *    - Banner do sistema
 *    - Navbar dinâmica via include
 *    - Carrossel de notícias/informações
 *    - Rodapé institucional
 * 7. Inicializa os scripts JavaScript do carrossel
 *
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Acesso protegido por verificação de sessão ativa
 * - Usuários não autenticados não acessam esta página
 * - Includes controlados via caminhos absolutos (__DIR__)
 * - Nenhum dado sensível é exibido diretamente na tela
 *
 *
 * ACESSIBILIDADE
 * -------------------------------------------------------------
 * - Uso de landmarks semânticos (<header>, <nav>, <main>,
 *   <footer>)
 * - Menu de navegação com aria-label
 * - Carrossel com aria-label nos controles
 * - Botões de navegação com rótulos acessíveis
 * - Estrutura de lista (<ul>, <li>) para melhor leitura por
 *   leitores de tela
 *
 *
 * DEPENDÊNCIAS
 * -------------------------------------------------------------
 * 1. "../BD/conexao.php"
 *    - Responsável pela conexão com o banco de dados MySQL
 *
 * 2. "../include/verificacao.php"
 *    - Responsável pela validação da sessão do usuário
 *
 * 3. "../include/navbar.php"
 *    - Renderização do menu de navegação principal
 *
 * 4. "../assets/css/estilo_R01.css"
 *    - Estilos globais e específicos da página inicial
 *
 * 5. "../assets/js/script.js"
 *    - Controle do carrossel (slides, botões e indicadores)
 *
 *
 * COMPONENTES PRESENTES
 * -------------------------------------------------------------
 * 1. Header
 *    - Banner institucional do sistema
 *
 * 2. Navbar
 *    - Menu principal dinâmico
 *
 * 3. Main
 *    - Carrossel de notícias/informações internas
 *
 * 4. Footer
 *    - Informações institucionais
 *    - Links legais
 *    - Dados de contato
 * 
 *
 * BOAS PRÁTICAS APLICADAS
 * -------------------------------------------------------------
 * - Separação clara entre lógica PHP e marcação HTML
 * - Controle de acesso centralizado
 * - Reutilização de componentes via include
 * - CSS organizado com variáveis globais
 * - Estrutura HTML semântica
 *
 *
 * OBSERVAÇÕES
 * -------------------------------------------------------------
 * - Nenhuma consulta direta é realizada neste arquivo, a 
 *   conexão com o banco é utilizada apenas para validação de 
 *   sessão via arquivo de verificação
 * - Este arquivo não deve conter regras de negócio
 * - Alterações de conteúdo dinâmico devem ser feitas via
 *   componentes ou APIs futuras
 * - A conexão ($conn) não é fechada manualmente, pois o PHP
 *   encerra automaticamente ao final do script
 *
 * 
 * -------------------------------------------------------------
 * Data: 23/01/2026
 * Versão: 1.0
 * =============================================================
*/

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
    <link rel="stylesheet" href="../assets/css/estilo_R01.css">
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