<?php
/*
 * =============================================================
 * ARQUIVO: /usuario/lista.php
 * MÓDULO: Gestão de Usuários - Listagem de Usuários
 * =============================================================
 * 
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Arquivo responsável pela exibição da lista de usuários
 * cadastrados no Sistema de Gerenciamento de Recursos Humanos
 * (SGRH).
 *
 * Este módulo apresenta os usuários em formato de cartões
 * (cards), exibindo informações básicas como nome, cargo,
 * status da conta e foto do usuário.
 *
 * Além da listagem, a interface permite:
 * - Busca dinâmica de usuários pelo nome
 * - Filtragem por status da conta (ativo/inativo)
 * - Acesso ao perfil individual de cada usuário
 * - Redirecionamento para cadastro de novos usuários
 *
 * O objetivo principal desta página é permitir a visualização
 * rápida e organizada dos colaboradores cadastrados no sistema.
 *
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Inicia a sessão PHP
 * 2. Inclui o arquivo de conexão com o banco de dados
 * 3. Inclui o arquivo de verificação de autenticação
 * 4. Executa a função verificar_login() para garantir acesso
 *    apenas a usuários autenticados
 * 5. Define funções auxiliares:
 *    - e(): proteção contra XSS
 *    - fotoUsuario(): resolução da imagem do usuário
 * 6. Executa consulta SQL para recuperar os usuários cadastrados
 * 7. Renderiza a interface HTML contendo:
 *    - Menu de navegação principal
 *    - Botão de cadastro de usuário
 *    - Campo de busca
 *    - Filtro de status
 *    - Lista de usuários em formato de cards
 * 8. Inicializa scripts JavaScript responsáveis por:
 *    - Busca dinâmica
 *    - Filtragem de usuários
 *
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Acesso protegido por verificação de sessão ativa
 * - Proteção contra XSS utilizando htmlspecialchars()
 * - Consulta ao banco realizada com prepared statement
 * - Escapamento de saída antes da renderização na interface
 * - Conversão explícita de IDs para inteiro em links dinâmicos
 *
 *
 * ACESSIBILIDADE
 * -------------------------------------------------------------
 * - Uso de landmarks semânticos (<nav>, <main>, <section>)
 * - Menu de navegação com aria-label
 * - Lista de usuários com aria-label descritivo
 * - Campos de busca com aria-label
 * - Imagens com atributo alt descritivo
 *
 *
 * DEPENDÊNCIAS
 * -------------------------------------------------------------
 * 1. "../../BD/conexao.php"
 *    - Responsável pela conexão com o banco de dados MySQL
 *
 * 2. "../../include/verificacao.php"
 *    - Responsável pela validação da sessão do usuário
 *
 * 3. "../../include/navbar.php"
 *    - Renderização do menu de navegação principal
 *
 * 4. "../../assets/css/estilo.css"
 *    - Estilização da interface do sistema
 *
 * 5. "../../assets/js/script.js"
 *    - Controle de busca dinâmica e filtragem de usuários
 *
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * 1. usuario
 *    - id_usuario
 *    - nome_usuario
 *    - cpf_usuario
 *    - rg_usuario
 *    - genero
 *    - email_usuario
 *    - telefone
 *    - cep
 *    - assiduidade
 *    - data_admissao
 *    - conta_ativa
 *    - foto_usuario
 *    - id_cargo
 *
 * 2. cargo
 *    - id_cargo
 *    - nome_cargo
 *
 *
 * COMPONENTES PRESENTES
 * -------------------------------------------------------------
 * 1. Navbar
 *    - Menu de navegação principal do sistema
 *
 * 2. Painel de Usuários
 *    - Botão para cadastro de novos usuários
 *    - Campo de busca por nome
 *    - Filtro de status (ativo/inativo/todos)
 *
 * 3. Lista de Usuários
 *    - Cards contendo:
 *      - Foto do usuário
 *      - Nome
 *      - Status da conta
 *      - Cargo
 *      - Botão de acesso ao perfil
 *
 *
 * FUNÇÕES AUXILIARES
 * -------------------------------------------------------------
 * 1. e($valor)
 *    - Escapa caracteres especiais para evitar ataques XSS
 *
 * 2. fotoUsuario($foto)
 *    - Retorna o caminho da foto do usuário
 *    - Caso não exista imagem cadastrada, retorna imagem padrão
 *
 *
 * BOAS PRÁTICAS APLICADAS
 * -------------------------------------------------------------
 * - Uso de prepared statements para consulta ao banco
 * - Escape de dados antes da renderização
 * - Separação entre lógica PHP e estrutura HTML
 * - Estrutura HTML semântica
 * - Componentização do menu via include
 * - Organização visual com cards reutilizáveis
 *
 *
 * OBSERVAÇÕES
 * -------------------------------------------------------------
 * - A busca e filtragem de usuários são realizadas no lado do
 *   cliente via JavaScript.
 *
 * - A página não executa alterações no banco de dados, sendo
 *   utilizada apenas para consulta e navegação.
 *
 * - A função fotoUsuario() garante fallback para imagem padrão
 *   quando o usuário não possui foto cadastrada.
 *
 * - A conexão ($conn) não é encerrada manualmente, pois o PHP
 *   realiza o encerramento automaticamente ao final do script.
 *
 *
 * -------------------------------------------------------------
 * Data: 06/03/2026
 * Versão: 1.0
 * =============================================================
 */

session_start();

include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";

verificar_login($conn);

/* FUNÇÕES AUXILIARES */

/* Escapa valores para evitar XSS */
function e($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/* Retorna o caminho da foto do usuário ou a imagem padrão */
function fotoUsuario($foto)
{
    $padrao = '../../assets/img/user_padrao.png';
    $caminho = "../../assets/img/usuarios/$foto";

    return (!empty($foto) && file_exists($caminho)) ? $caminho : $padrao;
}

/* CONSULTA AO BANCO */
$sql = "
    SELECT 
        u.id_usuario, 
        u.nome_usuario, 
        u.cpf_usuario, 
        u.rg_usuario, 
        u.genero,
        u.email_usuario, 
        u.telefone, 
        u.cep, 
        c.nome_cargo, 
        u.assiduidade,
        u.data_admissao, 
        u.conta_ativa,
        u.foto_usuario
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    ORDER BY u.id_usuario ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Usuários | Sistema RH</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav aria-label="Menu principal">
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="usuarios-painel" aria-label="Painel de usuários">
            <section class="usuarios-acoes">
                <a href="cadastro.php" class="btn-link btn-padrao">Cadastrar Usuário</a>

                <form class="form" onsubmit="return false;">
                    <input type="search" id="busca" class="input" placeholder="Buscar usuário..." aria-label="Buscar usuário">
                </form>

                <select id="filtro-status" class="select-padrao" aria-label="Filtrar usuários">
                    <option value="ativos" selected>Ativos</option>
                    <option value="inativos">Inativos</option>
                    <option value="todos">Todos</option>
                </select>
            </section>

            <section class="cards-container" id="tabelaUsuarios" aria-label="Lista de usuários">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <article
                        class="card card-hover card-usuario status"
                        aria-label="Usuário <?= e($row['nome_usuario']) ?>"
                        data-status="<?= $row['conta_ativa'] ? 'ativo' : 'inativo' ?>"
                        data-nome="<?= strtolower(e($row['nome_usuario'])) ?>">

                        <figure class="usuario-foto">
                            <img src="<?= fotoUsuario($row['foto_usuario']) ?>" alt="Foto de <?= e($row['nome_usuario']) ?>">
                        </figure>

                        <h3><?= e($row['nome_usuario']) ?></h3>

                        <span class="<?= $row['conta_ativa'] ? 'ativo' : 'inativo' ?>">
                            <?= $row['conta_ativa'] ? 'Ativo' : 'Inativo' ?>
                        </span>

                        <p class="cargo">
                            <?= e($row['nome_cargo'] ?? 'Cargo não definido') ?>
                        </p>

                        <a href="editar.php?id=<?= (int) $row['id_usuario'] ?>" class="btn-link btn-padrao">Perfil</a>
                    </article>
                <?php endwhile; ?>
            </section>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>