<?php
/*
 * =============================================================
 * ARQUIVO: logados.php
 * MÓDULO: Monitoramento de Atividade em Tempo Real (Dashboard)
 * =============================================================
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Interface operacional utilizada para o monitoramento de usuários 
 * que possuem sessões ativas no sistema SGRH. 
 * 
 * Funcionalidades:
 * - Visualização em tempo real de colaboradores autenticados.
 * - Filtro dinâmico por nome de usuário (via Client-side).
 * - Exibição de métricas de tempo de sessão e horário de entrada.
 * - Integração com scripts de polling para atualização assíncrona.
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Inicializa a sessão PHP e define fuso horário operacional.
 * 2. Carrega as dependências de conexão e segurança.
 * 3. Executa a função 'verificar_login()' para garantir que o 
 * acesso seja restrito a usuários autorizados.
 * 4. Renderiza a estrutura básica da tabela (HTML/CSS).
 * 5. Delega a carga de dados para o arquivo 'script.js', que realiza
 * requisições assíncronas ao backend para popular o corpo da tabela 
 * sem a necessidade de recarregar a página.
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Proteção de rota via verificação de sessão ativa.
 * - Estrutura modular que separa a visualização da lógica de dados.
 * - Uso de placeholders na tabela para prevenir quebras de layout 
 * durante a latência de carregamento.
 *
 * TABELAS UTILIZADAS (Via API/AJAX)
 * -------------------------------------------------------------
 * 1. usuario: Identificação de nomes e e-mails.
 * 2. ponto_dia: Verificação de status de jornada atual.
 * 3. sessoes_ativas (ou lógica de tempo de sessão configurada).
 *
 * -------------------------------------------------------------
 * Data: 09/03/2026
 * Versão: 1.0
 * =============================================================
 */

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
        <section class="pagina-padrao">
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