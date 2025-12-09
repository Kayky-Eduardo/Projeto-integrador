<!--
    MÓDULO: LISTAGEM DE USUÁRIOS

    OBJETIVO
        Exibir todos os usuários cadastrados com filtros e ações administrativas

    ESTRUTURA SEMÂNTICA
        nav       - Menu de navegação
        main      - Conteúdo principal do sistema
        section   - Agrupamento funcional
        header    - Área de ações e busca
        table     - Exibição de dados em forma tabular

    FUNCIONALIDADES
        1. Listagem dinâmica via PHP/MySQL
        2. Campo de busca em tempo real (JavaScript)
        3. Ações de editar e excluir usuário
        4. Exibição de status (ativo/inativo)
        5. Responsividade sem uso de scroll horizontal

    ACESSIBILIDADE
        - Uso de data-label para leitura mobile
        - Marcação semântica adequada
        - Botões com ações claras

    RESPONSIVIDADE
        Desktop: modo tabela tradicional
        Mobile: transformação em cartões sem perda de dados

    OBSERVAÇÕES TÉCNICAS
        - Banco conectado via mysqli
        - Navbar reutilizada via include
        - CSS centralizado em: ../../assets/css/estilo.css
-->

<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$sql = "SELECT u.id_usuario, u.nome_usuario, u.cpf_usuario, u.rg_usuario, u.genero,
               u.email_usuario, u.telefone, u.cep, c.nome_cargo, u.assiduidade,
               u.data_admissao, u.conta_ativa
        FROM usuario u
        LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
        ORDER BY u.id_usuario ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Usuários | Sistema RH</title>
  <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
  <nav role="navigation" aria-label="Menu principal">
    <?php include("../../include/navbar.php"); ?>
  </nav>

  <main role="main">
    <section class="usuarios-painel" aria-label="Painel de usuários">
      <header class="usuarios-topo">
        <a href="cadastro.php" class="btn-cadastrar">
          Cadastrar Usuário
        </a>

        <form class="busca-usuarios" onsubmit="return false;">
          <input
            type="search"
            id="busca"
            placeholder="Buscar usuário..."
            aria-label="Buscar usuário">
        </form>
      </header>

      <section class="cards-container" id="tabelaUsuarios" aria-label="Lista de usuários">
        <?php while ($row = $result->fetch_assoc()): ?>
          <article class="usuario-card" aria-label="Usuário">

            <!-- FOTO -->
            <figure class="usuario-foto">
              <img src="../../assets/img/user_padrao.png" alt="Foto do usuário">
            </figure>

            <!-- NOME -->
            <h3><?= $row['nome_usuario'] ?></h3>

            <!-- STATUS -->
            <?= $row['conta_ativa']
              ? "<span class='ativo'>Ativo</span>"
              : "<span class='inativo'>Inativo</span>" ?>

            <!-- CARGO -->
            <p class="cargo"><?= $row['nome_cargo'] ?? 'Cargo não definido' ?></p>

            <!-- AÇÃO -->
            <a href="editar.php?id=<?= $row['id_usuario'] ?>" class="btn-perfil">
              Perfil
            </a>
          </article>
        <?php endwhile; ?>
      </section>
    </section>
  </main>

  <script src="../../assets/js/script.js"></script>
</body>

</html>