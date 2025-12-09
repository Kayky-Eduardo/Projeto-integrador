<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// Verifica se foi passado o ID do usuário via GET
if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];

    // Busca o usuário correspondente
    $sql = "SELECT * FROM usuario WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se encontrou o usuário, guarda os dados
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    } else {
        die("<h3>Usuário não encontrado! <a href='lista.php'>Voltar</a></h3>");
    }
} else {
    die("<h3>Erro: nenhum usuário selecionado para edição.<br><a href='lista.php'>Voltar</a></h3>");
}

// Se enviou o formulário de edição
if (isset($_POST['editar_usuario'])) {
    $id = $_POST['id_usuario'];
    $nome = $_POST['nome_usuario'];
    $cpf = $_POST['cpf_usuario'];
    $rg = $_POST['rg_usuario'];
    $genero = $_POST['genero'];
    $email = $_POST['email_usuario'];
    $senha = $_POST['senha_usuario']; // pode estar vazio
    $telefone = $_POST['telefone'];
    $cep = $_POST['cep'];
    $id_cargo = $_POST['id_cargo'];
    $assiduidade = $_POST['assiduidade'];
    $data_admissao = $_POST['data_admissao'];

    // Verifica se a senha foi alterada
    if (!empty($senha)) {
        // Atualiza senha com hash
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
        // Não altera a senha
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
        echo "Usuário atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar usuário: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Perfil do Usuário</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>

    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="perfil">

        <!-- PERFIL VISUAL -->
        <section class="perfil-header">
            <img src="../../assets/img/avatar-padrao.png" class="perfil-foto">

            <h2><?= $usuario['nome_usuario'] ?></h2>

            <span class="<?= $usuario['conta_ativa'] ? 'ativo' : 'inativo' ?>">
                <?= $usuario['conta_ativa'] ? 'Usuário Ativo' : 'Usuário Inativo' ?>
            </span>

            <p class="perfil-cargo">Cargo ID: <?= $usuario['id_cargo'] ?></p>
        </section>

        <!-- INFORMAÇÕES -->
        <section class="perfil-dados">

            <article>
                <h4>Contato</h4>
                <p>Email: <strong><?= $usuario['email_usuario'] ?></strong></p>
                <p>Telefone: <strong><?= $usuario['telefone'] ?></strong></p>
                <p>CEP: <strong><?= $usuario['cep'] ?></strong></p>
            </article>

            <article>
                <h4>Documentos</h4>
                <p>CPF: <strong><?= $usuario['cpf_usuario'] ?></strong></p>
                <p>RG: <strong><?= $usuario['rg_usuario'] ?></strong></p>
                <p>Gênero: <strong><?= $usuario['genero'] ?></strong></p>
            </article>

            <article>
                <h4>Empresa</h4>
                <p>Assiduidade: <strong><?= $usuario['assiduidade'] ?>%</strong></p>
                <p>Admissão: <strong><?= date('d/m/Y', strtotime($usuario['data_admissao'])) ?></strong></p>
            </article>

            <section class="acoes-empresa">
                <button onclick="document.getElementById('form-edicao').style.display='block'">
                    Editar Perfil
                </button>

                <a href="deletar_usuario.php?id=<?= $usuario['id_usuario'] ?>"
                    onclick="return confirm('Confirma excluir este usuário?')"
                    class="btn-excluir">
                    Excluir Usuário
                </a>
            </section>

            <section class="voltar-final">
                <a href="lista.php">← Voltar</a>
            </section>
        </section>

        <!-- FORMULÁRIO INLINE -->
        <section id="form-edicao" style="display:none;">

            <h3>Editar Dados</h3>

            <form method="POST">

                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

                <label>Nome</label>
                <input name="nome_usuario" value="<?= $usuario['nome_usuario'] ?>" required>

                <label>Email</label>
                <input name="email_usuario" value="<?= $usuario['email_usuario'] ?>" required>

                <label>Telefone</label>
                <input name="telefone" value="<?= $usuario['telefone'] ?>">

                <label>Senha</label>
                <input type="password" name="senha_usuario" placeholder="Nova senha (opcional)">

                <button name="editar_usuario">Salvar alterações</button>
            </form>
        </section>
    </main>
</body>

</html>