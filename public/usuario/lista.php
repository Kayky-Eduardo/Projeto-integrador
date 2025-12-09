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
  <!-- NAV -->
  <nav role="navigation" aria-label="Menu principal">
    <?php include("../../include/navbar.php"); ?>
  </nav>

  <!-- CONTEÚDO -->
  <main>
    <section class="usuarios-painel">

      <header class="usuarios-topo">
        <a href="cadastro.php" class="btn-cadastrar">Cadastrar Usuário</a>

        <form class="busca-usuarios" onsubmit="return false">
          <input
            type="search"
            id="busca"
            placeholder="Buscar usuário..."
            onkeyup="filtrarUsuarios()">
        </form>
      </header>

      <!-- TABELA -->
      <section class="tabela-container">
        <table>
          <thead>
            <tr>
              <th>Nome</th>
              <th>CPF</th>
              <th>RG</th>
              <th>Gênero</th>
              <th>Email</th>
              <th>Telefone</th>
              <th>CEP</th>
              <th>Cargo</th>
              <th>Assiduidade</th>
              <th>Admissão</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>

          <tbody id="tabelaUsuarios">
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td data-label="Nome"><?= $row['nome_usuario'] ?></td>
                <td data-label="CPF"><?= $row['cpf_usuario'] ?></td>
                <td data-label="RG"><?= $row['rg_usuario'] ?></td>
                <td data-label="Gênero"><?= $row['genero'] ?></td>
                <td data-label="Email"><?= $row['email_usuario'] ?></td>
                <td data-label="Telefone"><?= $row['telefone'] ?></td>
                <td data-label="CEP"><?= $row['cep'] ?></td>
                <td data-label="Cargo"><?= $row['nome_cargo'] ?? 'Não definido' ?></td>
                <td data-label="Assiduidade"><?= $row['assiduidade'] ?>%</td>
                <td data-label="Admissão"><?= $row['data_admissao'] ?></td>

                <td data-label="Status">
                  <?= $row['conta_ativa'] ? "<span class='ativo'>Ativo</span>" : "<span class='inativo'>Inativo</span>" ?>
                </td>

                <td data-label="Ações" class="acoes">
                  <form action="editar.php" method="GET">
                    <input type="hidden" name="id" value="<?= $row['id_usuario'] ?>">
                    <button type="submit" class="btn-editar">Editar</button>
                  </form>

                  <form action="deletar_usuario.php" method="POST" onsubmit="return confirm('Excluir este usuário?');">
                    <input type="hidden" name="id_usuario" value="<?= $row['id_usuario'] ?>">
                    <button type="submit" class="btn-excluir">Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </section>
    </section>
  </main>

  <!-- SCRIPT -->
  <script src="../../assets/js/script.js"></script>
</body>

</html>