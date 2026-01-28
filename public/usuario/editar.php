<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

/* BUSCAR USUÁRIO */
if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];
    $sql = "SELECT u.*, c.nome_cargo FROM usuario u LEFT JOIN cargo c ON u.id_cargo = c.id_cargo WHERE u.id_usuario = ?";
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

/* PADRONIZAR ERROS */
$erros = [];

if (isset($_SESSION['erros'])) {
    $erros = $_SESSION['erros'];
    unset($_SESSION['erros']);
}

/* ATIVAR / DESATIVAR USUÁRIO */
if (isset($_POST['toggle_status'], $_POST['id_usuario'])) {

    $id = intval($_POST['id_usuario']);
    $novo_status = intval($_POST['toggle_status']);

    $stmt = $conn->prepare(
        "UPDATE usuario SET conta_ativa = ? WHERE id_usuario = ?"
    );

    $stmt->bind_param("ii", $novo_status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: editar.php?id=" . $id);
    exit;
}

/* BUSCAR CARGOS */
$cargos = $conn->query("SELECT id_cargo, nome_cargo FROM cargo");

/* BUSCAR FOTO DO USUÁRIO */
$caminho_foto = "../../assets/img/user_padrao.png";

if (!empty($usuario['foto_usuario']) && file_exists("../../assets/img/usuarios/" . $usuario['foto_usuario'])) {
    $caminho_foto = "../../assets/img/usuarios/" . $usuario['foto_usuario'];
}

/* FORMATAÇÕES PADRÃO */
function formatarCPF($cpf)
{
    return (strlen($cpf) === 11) ? preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $cpf) : $cpf;
}

function formatarRG($rg)
{
    return (strlen($rg) === 9) ? preg_replace("/(\d{2})(\d{3})(\d{3})(\d{1})/", "$1.$2.$3-$4", $rg) : $rg;
}

function formatarCEP($cep)
{
    return (strlen($cep) === 8) ? preg_replace("/(\d{5})(\d{3})/", "$1-$2", $cep) : $cep;
}

function formatarTelefone($tel)
{
    if (strlen($tel) === 10) {
        return preg_replace("/(\d{2})(\d{4})(\d{4})/", "($1) $2-$3", $tel);
    } elseif (strlen($tel) === 11) {
        return preg_replace("/(\d{2})(\d{5})(\d{4})/", "($1) $2-$3", $tel);
    }

    return $tel;
}

/* ATUALIZAR DADOS DO USUÁRIO */
if (isset($_POST['editar_usuario'])) {
    $foto_nova = $usuario['foto_usuario'];

    if (!empty($_FILES['foto_usuario']['name'])) {
        $dir = "../../assets/img/usuarios/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['foto_usuario']['name'], PATHINFO_EXTENSION));
        $permitidas = ["jpg", "jpeg", "png", "webp"];

        if (in_array($ext, $permitidas)) {
            $foto_nova = uniqid("user_") . "." . $ext;
            move_uploaded_file($_FILES['foto_usuario']['tmp_name'], $dir . $foto_nova);

            if (!empty($usuario['foto_usuario']) && file_exists($dir . $usuario['foto_usuario'])) {
                unlink($dir . $usuario['foto_usuario']);
            }
        }
    }

    $id = $_POST['id_usuario'];
    $nome = $_POST['nome_usuario'];
    $cpf = preg_replace("/\D/", "", $_POST['cpf_usuario']);
    $rg = preg_replace("/\D/", "", $_POST['rg_usuario']);
    $genero = $_POST['genero'] ?? $usuario['genero'];
    $email = $_POST['email_usuario'];
    $senha = $_POST['senha_usuario'];
    $telefone = preg_replace("/\D/", "", $_POST['telefone']);
    $cep = preg_replace("/\D/", "", $_POST['cep']);
    $id_cargo = $_POST['id_cargo'] ?? $usuario['id_cargo'];
    $assiduidade = $_POST['assiduidade'] ?? $usuario['assiduidade'];
    $data_admissao = $_POST['data_admissao'] ?? $usuario['data_admissao'];

    /* VERIFICAR DUPLICIDADE DE DADOS */
    // CPF
    $sql = "SELECT id_usuario FROM usuario WHERE cpf_usuario = ? AND id_usuario != ?";
    $stmtCheck = $conn->prepare($sql);
    $stmtCheck->bind_param("si", $cpf, $id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $erros[] = "CPF já cadastrado.";
    }

    $stmtCheck->close();

    // RG
    $sql = "SELECT id_usuario FROM usuario WHERE rg_usuario = ? AND id_usuario != ?";
    $stmtCheck = $conn->prepare($sql);
    $stmtCheck->bind_param("si", $rg, $id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $erros[] = "RG já cadastrado.";
    }

    $stmtCheck->close();

    // EMAIL
    $sql = "SELECT id_usuario FROM usuario WHERE email_usuario = ? AND id_usuario != ?";
    $stmtCheck = $conn->prepare($sql);
    $stmtCheck->bind_param("si", $email, $id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $erros[] = "Email já cadastrado.";
    }

    $stmtCheck->close();

    if (!empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE usuario SET
            nome_usuario=?, cpf_usuario=?, rg_usuario=?, genero=?, email_usuario=?, senha_usuario=?, telefone=?, cep=?, id_cargo=?, assiduidade=?, data_admissao=?, foto_usuario=?
            WHERE id_usuario=?";

        $stmt = $conn->prepare($sqlUpdate);

        $stmt->bind_param(
            "ssssssssidssi",
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
            $foto_nova,
            $id
        );
    } else {
        $sqlUpdate = "UPDATE usuario SET
            nome_usuario=?, cpf_usuario=?, rg_usuario=?, genero=?, email_usuario=?, telefone=?, cep=?, id_cargo=?, assiduidade=?, data_admissao=?, foto_usuario=?
            WHERE id_usuario=?";

        $stmt = $conn->prepare($sqlUpdate);

        $stmt->bind_param(
            "sssssssidssi",
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
            $foto_nova,
            $id
        );
    }

    if (empty($erros)) {
        try {
            $stmt->execute();
            header("Location: editar.php?id=" . $id);
            exit();
        } catch (mysqli_sql_exception $e) {
            $erros[] = "Erro ao atualizar os dados do usuário.";
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="container perfil">
        <form class="perfil-grid perfil-grid-editar" method="POST" enctype="multipart/form-data">
            <section class="perfil-header">
                <label class="foto-upload">
                    <img src="<?= $caminho_foto ?>" class="perfil-foto" id="preview-foto">
                    <input type="file" name="foto_usuario" id="input-foto" accept="image/*">
                    <span>Alterar foto</span>
                </label>

                <h2><?= $usuario['nome_usuario'] ?></h2>

                <span class="<?= $usuario['conta_ativa'] ? 'status-ativo' : 'status-inativo' ?>">
                    <?= $usuario['conta_ativa'] ? 'Usuário Ativo' : 'Usuário Inativo' ?>
                </span>

                <p class="perfil-cargo"><?= $usuario['nome_cargo'] ?? 'Cargo não definido' ?></p>
                <button type="submit" class="btn-padrao btn-salvar-foto hidden" name="editar_usuario" id="btn-salvar-foto">Salvar foto</button>
            </section>

            <section class="perfil-visualizacao" id="painel-visualizacao">
                <?php if (!empty($erros)): ?>
                    <article class="box-erros">
                        <strong>Erros encontrados:</strong>
                        <ul class="lista-erro erro">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= $erro ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endif; ?>

                <h4>Informações do Funcionário</h4>

                <article class="info-bloco">
                    <span>Nome</span>
                    <p><?= $usuario['nome_usuario'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>CPF</span>
                    <p><?= formatarCPF($usuario['cpf_usuario']) ?></p>
                </article>

                <article class="info-bloco">
                    <span>RG</span>
                    <p><?= formatarRG($usuario['rg_usuario']) ?></p>
                </article>

                <article class="info-bloco">
                    <span>Telefone</span>
                    <p><?= formatarTelefone($usuario['telefone']) ?></p>
                </article>

                <article class="info-bloco">
                    <span>CEP</span>
                    <p><?= formatarCEP($usuario['cep']) ?></p>
                </article>

                <article class="info-bloco">
                    <span>Email</span>
                    <p><?= $usuario['email_usuario'] ?></p>
                </article>

                <article class="info-bloco">
                    <span>Data de Admissão</span>
                    <p><?= date('d/m/Y', strtotime($usuario['data_admissao'])) ?></p>
                </article>

                <article class="info-bloco">
                    <span>Assiduidade</span>
                    <p><?= $usuario['assiduidade'] ?>%</p>
                </article>

                <section class="acoes-perfil">
                    <button type="button" class="btn-padrao" id="btn-editar">Editar</button>
                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

                    <?php if ($usuario['conta_ativa']): ?>
                        <button type="submit" name="toggle_status" value="0" class="btn-status btn-desativar">Desativar usuário</button>
                    <?php else: ?>
                        <button type="submit" name="toggle_status" value="1" class="btn-status btn-ativar">Ativar usuário</button>
                    <?php endif; ?>

                    <button type="submit" class="btn-excluir" formaction="deletar_usuario.php" formmethod="POST">Excluir</button>

                    <section class="voltar-final">
                        <a href="lista.php" class="link-simples">Voltar</a>
                    </section>
                </section>
            </section>

            <section class="perfil-edicao hidden" id="painel-edicao">
                <h4>Editar Funcionário</h4>

                <section class="form-padrao">
                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

                    <label class="label-padrao">Nome</label>
                    <input class="input-padrao" name="nome_usuario" value="<?= $usuario['nome_usuario'] ?>" required>

                    <label class="label-padrao">CPF</label>
                    <input class="input-padrao" id="cpf" name="cpf_usuario" value="<?= $usuario['cpf_usuario'] ?>" required>

                    <label class="label-padrao">RG</label>
                    <input class="input-padrao" id="rg" name="rg_usuario" value="<?= $usuario['rg_usuario'] ?>" required>

                    <label class="label-padrao">Telefone</label>
                    <input class="input-padrao" id="telefone" name="telefone" value="<?= $usuario['telefone'] ?>">

                    <label class="label-padrao">CEP</label>
                    <input class="input-padrao" id="cep" name="cep" value="<?= $usuario['cep'] ?>">

                    <label class="label-padrao">Cargo</label>
                    <select class="input-padrao" name="id_cargo">
                        <?php while ($cargo = $cargos->fetch_assoc()): ?>
                            <option value="<?= $cargo['id_cargo'] ?>" <?= $cargo['id_cargo'] == $usuario['id_cargo'] ? 'selected' : '' ?>>
                                <?= $cargo['nome_cargo'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label class="label-padrao">Gênero</label>
                    <select class="input-padrao" name="genero">
                        <option value="<?= $usuario['genero'] ?>"><?= $usuario['genero'] ?></option>
                        <option>Masculino</option>
                        <option>Feminino</option>
                        <option>Outro</option>
                        <option>Não Declarado</option>
                    </select>

                    <label class="label-padrao">Email</label>
                    <input class="input-padrao" name="email_usuario" value="<?= $usuario['email_usuario'] ?>">

                    <label class="label-padrao">Senha</label>
                    <input class="input-padrao" type="password" name="senha_usuario" placeholder="Nova senha (opcional)">

                    <button class="btn-padrao" name="editar_usuario">Salvar Alterações</button>
                </section>
            </section>
        </form>
    </main>

    <script src="https://unpkg.com/imask"></script>
    <script src="../../assets/js/script.js"></script>
</body>

</html>