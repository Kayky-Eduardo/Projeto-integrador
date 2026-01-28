<?php
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
                <a href="cadastro.php" class="link-cadastrar">Cadastrar Usuário</a>

                <form class="busca-usuarios" onsubmit="return false;">
                    <input type="search" id="busca" placeholder="Buscar usuário..." aria-label="Buscar usuário">
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
                        class="usuario-card"
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

                        <a href="editar.php?id=<?= (int) $row['id_usuario'] ?>" class="link-perfil">Perfil</a>
                    </article>
                <?php endwhile; ?>
            </section>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>