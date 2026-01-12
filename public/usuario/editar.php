<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

/* ===============================
   BUSCA DO USUÁRIO
================================ */

if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];

    $sql = "SELECT u.*, c.nome_cargo 
        FROM usuario u
        LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
        WHERE u.id_usuario = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    } else {
        die("<h3>Usuário não encontrado! <a href='lista.php'>Voltar</a></h3>");
    }
} else {
    die("<h3>Erro: nenhum usuário selecionado.<br><a href='lista.php'>Voltar</a></h3>");
}

/* ===============================
   FOTO DO USUÁRIO
================================ */

$caminho_foto = "../../assets/img/user_padrao.png";

if (
    !empty($usuario['foto_usuario']) &&
    file_exists("../../assets/img/usuarios/" . $usuario['foto_usuario'])
) {
    $caminho_foto = "../../assets/img/usuarios/" . $usuario['foto_usuario'];
}

/* ===============================
   ATUALIZAÇÃO DE DADOS
================================ */

if (isset($_POST['editar_usuario'])) {

    $id = $_POST['id_usuario'];
    $nome = $_POST['nome_usuario'];
    $cpf = $_POST['cpf_usuario'] ?? $usuario['cpf_usuario'];
    $rg = $_POST['rg_usuario'] ?? $usuario['rg_usuario'];
    $genero = $_POST['genero'] ?? $usuario['genero'];
    $email = $_POST['email_usuario'];
    $senha = $_POST['senha_usuario'];
    $telefone = $_POST['telefone'];
    $cep = $_POST['cep'] ?? $usuario['cep'];
    $id_cargo = $_POST['id_cargo'] ?? $usuario['id_cargo'];
    $assiduidade = $_POST['assiduidade'] ?? $usuario['assiduidade'];
    $data_admissao = $_POST['data_admissao'] ?? $usuario['data_admissao'];

    if (!empty($senha)) {

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE usuario SET
            nome_usuario=?, cpf_usuario=?, rg_usuario=?, genero=?,
            email_usuario=?, senha_usuario=?, telefone=?, cep=?,
            id_cargo=?, assiduidade=?, data_admissao=?
            WHERE id_usuario=?";

        $stmt = $conn->prepare($sqlUpdate);
        $stmt->bind_param(
            "ssssssssidsi",
            $nome,
            $cpf,
            $rg,
            $genero,
            $email,
            $senha_hash,
            $telefone,
            $cep,
            $id_cargo,
            $assiduidade,
            $data_admissao,
            $id
        );
    } else {

        $sqlUpdate = "UPDATE usuario SET
            nome_usuario=?, cpf_usuario=?, rg_usuario=?, genero=?,
            email_usuario=?, telefone=?, cep=?,
            id_cargo=?, assiduidade=?, data_admissao=?
            WHERE id_usuario=?";

        $stmt = $conn->prepare($sqlUpdate);
        $stmt->bind_param(
            "sssssssidsi",
            $nome,
            $cpf,
            $rg,
            $genero,
            $email,
            $telefone,
            $cep,
            $id_cargo,
            $assiduidade,
            $data_admissao,
            $id
        );
    }

    if ($stmt->execute()) {
        header("Location: editar.php?id=" . $id);
        exit();
    } else {
        echo "Erro ao atualizar: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>

    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="perfil">

        <form class="perfil-grid perfil-grid-editar" method="POST" enctype="multipart/form-data">
            <section class="perfil-header">

                <label class="foto-upload">
                    <img src="<?= $caminho_foto ?>" class="perfil-foto" id="preview-foto">

                    <input type="file" name="foto_usuario" accept="image/*">
                    <span>Alterar foto</span>
                </label>

                <h2><?= $usuario['nome_usuario'] ?></h2>

                <span class="<?= $usuario['conta_ativa'] ? 'status-ativo' : 'status-inativo' ?>">
                    <?= $usuario['conta_ativa'] ? 'Usuário Ativo' : 'Usuário Inativo' ?>
                </span>

                <p class="perfil-cargo">
                    <?= $usuario['nome_cargo'] ?? 'Cargo não definido' ?>
                </p>
            </section>

            <section class="perfil-visualizacao">

                <h4>Informações do Funcionário</h4>

                <article class="info-bloco">
                    <span>Nome</span>
                    <p><?= $usuario['nome_usuario'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>CPF</span>
                    <p><?= $usuario['cpf_usuario'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>RG</span>
                    <p><?= $usuario['rg_usuario'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>Gênero</span>
                    <p><?= $usuario['genero'] ?? 'Não informado' ?></p>
                </article>

                <article class="info-bloco">
                    <span>Email</span>
                    <p><?= $usuario['email_usuario'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>Telefone</span>
                    <p><?= $usuario['telefone'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>CEP</span>
                    <p><?= $usuario['cep'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>Data de Admissão</span>
                    <p>
                        <?= date('d/m/Y', strtotime($usuario['data_admissao'])) ?>
                    </p>
                </article>

                <article class="info-bloco">
                    <span>Assiduidade</span>
                    <p><?= $usuario['assiduidade'] ?>%</p>
                </article>

                <section class="acoes-perfil">
                    <button type="button" class="btn-padrao" id="btn-editar">
                        Editar
                    </button>

                    <a href="excluir.php?id=<?= $usuario['id_usuario'] ?>"
                        class="btn-excluir">
                        Excluir
                    </a>

                    <section class="voltar-final">
                        <a href="lista.php">Voltar</a>
                    </section>
                </section>
            </section>

            <section class="perfil-edicao" id="painel-edicao">
                <h4>Editar Funcionário</h4>

                <section class="form-padrao">
                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

                    <label class="label-padrao">Nome</label>
                    <input class="input-padrao" name="nome_usuario"
                        value="<?= $usuario['nome_usuario'] ?>" required>

                    <label class="label-padrao">Gênero</label>
                    <select class="input-padrao" name="genero">
                        <option value="<?= $usuario['genero'] ?>"><?= $usuario['genero'] ?></option>
                        <option>Masculino</option>
                        <option>Feminino</option>
                        <option>Outro</option>
                        <option>Não Declarado</option>
                    </select>

                    <label class="label-padrao">Email</label>
                    <input class="input-padrao" name="email_usuario"
                        value="<?= $usuario['email_usuario'] ?>">

                    <label class="label-padrao">Telefone</label>
                    <input class="input-padrao" name="telefone"
                        value="<?= $usuario['telefone'] ?>">

                    <label class="label-padrao">CEP</label>
                    <input class="input-padrao" name="cep"
                        value="<?= $usuario['cep'] ?>">

                    <label class="label-padrao">Senha</label>
                    <input class="input-padrao" type="password"
                        name="senha_usuario"
                        placeholder="Nova senha (opcional)">

                    <button class="btn-padrao" name="editar_usuario">
                        Salvar Alterações
                    </button>
                </section>
            </section>
        </form>
    </main>

    <script>
        const btnEditar = document.getElementById('btn-editar');
        const painelEdicao = document.getElementById('painel-edicao');

        btnEditar.addEventListener('click', () => {
            painelEdicao.classList.toggle('ativo');
        });
    </script>
</body>

</html>