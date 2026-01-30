<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// BUSCAR CARGOS
$cargos = [];
$result = $conn->query("SELECT id_cargo, nome_cargo FROM cargo ORDER BY nome_cargo ASC");

while ($row = $result->fetch_assoc()) {
    $cargos[] = $row;
}

// =================================
// Função de validação
// =================================
function validarDados($dados, $conn)
{
    $erros = [];

    $fotoPreview = "user_padrao.png";

    // Nome
    if (empty($dados['nome_usuario'])) {
        $erros[] = "Campo 'Nome' está vazio.";
    }

    // CPF
    if (empty($dados['cpf_usuario'])) {
        $erros[] = "Campo 'CPF' está vazio.";
    } elseif (!preg_match('/^\d{11}$/', $dados['cpf_usuario'])) {
        $erros[] = "CPF inválido.";
    } else {
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE cpf_usuario = ?");
        $stmt->bind_param("s", $dados['cpf_usuario']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows) $erros[] = "CPF já cadastrado.";
        $stmt->close();
    }

    // RG
    if (empty($dados['rg_usuario'])) {
        $erros[] = "Campo 'RG' está vazio.";
    } elseif (!preg_match('/^\d{9}$/', $dados['rg_usuario'])) {
        $erros[] = "RG inválido.";
    } else {
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE rg_usuario = ?");
        $stmt->bind_param("s", $dados['rg_usuario']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows) $erros[] = "RG já cadastrado.";
        $stmt->close();
    }

    // Gênero
    if (empty($dados['genero'])) {
        $erros[] = "Escolha um gênero.";
    }

    // Email
    if (empty($dados['email_usuario']) || !filter_var($dados['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Email inválido.";
    } else {
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email_usuario = ?");
        $stmt->bind_param("s", $dados['email_usuario']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows) $erros[] = "Email já cadastrado.";
        $stmt->close();
    }

    // Senha
    if (strlen($dados['senha_usuario']) < 6) {
        $erros[] = "Senha deve ter no mínimo 6 caracteres.";
    }

    // Telefone
    if (!preg_match('/^\d{11}$/', $dados['telefone'])) {
        $erros[] = "Telefone inválido.";
    }

    // CEP
    if (!preg_match('/^\d{8}$/', $dados['cep'])) {
        $erros[] = "CEP inválido.";
    }

    // Cargo
    if (!$dados['id_cargo']) {
        $erros[] = "Cargo inválido.";
    }

    // Data
    if (empty($dados['data_admissao'])) {
        $erros[] = "Data obrigatória.";
    }

    return $erros;
}

// =================================
// Cadastro do Usuário
// =================================
function cadastrarUsuario($conn, $dados)
{
    $sql = "INSERT INTO usuario (
        nome_usuario, cpf_usuario, rg_usuario, genero,
        email_usuario, senha_usuario, telefone, cep, id_cargo,
        data_admissao, foto_usuario, conta_ativa
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $senhaHash = password_hash($dados['senha_usuario'], PASSWORD_DEFAULT);

    $stmt->bind_param(
        "ssssssssissi",
        $dados['nome_usuario'],
        $dados['cpf_usuario'],
        $dados['rg_usuario'],
        $dados['genero'],
        $dados['email_usuario'],
        $senhaHash,
        $dados['telefone'],
        $dados['cep'],
        $dados['id_cargo'],
        $dados['data_admissao'],
        $dados['foto_usuario'],
        $dados['conta_ativa']
    );

    return $stmt->execute();
}

// =================================
// PROCESSAMENTO
// =================================
$erros = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Upload da foto
    $foto = "user_padrao.png";
    $fotoPreview = "user_padrao.png";

    if (!empty($_FILES['foto_usuario']['name'])) {

        $dir = "../../assets/img/usuarios/";

        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['foto_usuario']['name'], PATHINFO_EXTENSION));
        $permitidas = ["jpg", "jpeg", "png", "webp"];

        if (in_array($ext, $permitidas)) {
            $foto = uniqid("user_") . "." . $ext;
            move_uploaded_file($_FILES['foto_usuario']['tmp_name'], $dir . $foto);

            $fotoPreview = $foto;
        }
    }

    // Dados
    $dados = [
        "nome_usuario"   => trim($_POST['nome_usuario']),
        "cpf_usuario"    => preg_replace('/\D/', '', $_POST['cpf_usuario']),
        "rg_usuario"     => preg_replace('/\D/', '', $_POST['rg_usuario']),
        "genero"         => $_POST['genero'],
        "email_usuario"  => trim($_POST['email_usuario']),
        "senha_usuario"  => $_POST['senha_usuario'],
        "telefone"       => preg_replace('/\D/', '', $_POST['telefone']),
        "cep"            => preg_replace('/\D/', '', $_POST['cep']),
        "id_cargo"       => (int) $_POST['id_cargo'],
        "data_admissao"  => $_POST['data_admissao'],
        "foto_usuario"   => $foto,
        "conta_ativa"    => 1
    ];

    // Validação
    $erros = array_merge($erros, validarDados($dados, $conn));

    if (empty($erros)) {
        if (cadastrarUsuario($conn, $dados)) {
            header("Location: lista.php");
            exit;
        } else {
            $erros[] = "Erro ao salvar usuário.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-perfil">
        <form class="perfil-grid" method="POST" enctype="multipart/form-data">

            <!-- COLUNA DA FOTO -->
            <section class="container perfil-header">
                <label class="label-foto">
                    <?php
                    $caminhoPreview = "../../assets/img/user_padrao.png";

                    if (!empty($fotoPreview) && file_exists("../../assets/img/usuarios/" . $fotoPreview)) {
                        $caminhoPreview = "../../assets/img/usuarios/" . $fotoPreview;
                    }
                    ?>

                    <img src="<?= $caminhoPreview ?>" class="perfil-foto" id="preview-foto">
                    <input type="file" class="input-foto" name="foto_usuario" id="input-foto" accept="image/*">
                    <span>Alterar foto</span>
                </label>

                <h2>Novo Usuário</h2>
                <span class="status-ativo">Cadastro</span>
                <p class="perfil-cargo">Sistema de Recursos Humanos</p>
            </section>

            <!-- COLUNA FORMULÁRIO -->
            <section class="container perfil-visualizacao">
                <article class="perfil-artigo">

                    <!-- ERROS -->
                    <?php if (!empty($erros)): ?>
                        <div class="box-erros">
                            <strong>Erros encontrados:</strong>
                            <ul class="lista-erro erro">
                                <?php foreach ($erros as $erro): ?>
                                    <li><?= $erro ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <h4>Cadastro de Funcionário</h4>

                    <section class="form">
                        <label class="label">Nome</label>
                        <input class="input" name="nome_usuario" value="<?= $_POST['nome_usuario'] ?? '' ?>" required>

                        <label class="label">CPF</label>
                        <input class="input" id="cpf" name="cpf_usuario" value="<?= $_POST['cpf_usuario'] ?? '' ?>" required>

                        <label class="label">RG</label>
                        <input class="input" id="rg" name="rg_usuario" value="<?= $_POST['rg_usuario'] ?? '' ?>" required>

                        <label class="label">Gênero</label>
                        <select class="input" name="genero" required>
                            <option value="">Selecione</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Outro">Outro</option>
                            <option value="Não Declarado">Não Declarado</option>
                        </select>

                        <label class="label">Email</label>
                        <input class="input" name="email_usuario" value="<?= $_POST['email_usuario'] ?? '' ?>" required>

                        <label class="label">Senha</label>
                        <input class="input" type="password" name="senha_usuario" required>

                        <label class="label">Telefone</label>
                        <input class="input" id="telefone" name="telefone" value="<?= $_POST['telefone'] ?? '' ?>" required>

                        <label class="label">CEP</label>
                        <input class="input" id="cep" name="cep" value="<?= $_POST['cep'] ?? '' ?>" required>

                        <label class="label">Cargo</label>
                        <select class="input" name="id_cargo" required>
                            <option value="">Selecione</option>
                            <?php foreach ($cargos as $cargo): ?>
                                <option value="<?= $cargo['id_cargo']; ?>">
                                    <?= htmlspecialchars($cargo['nome_cargo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="label">Data de admissão</label>
                        <input class="input" type="date" name="data_admissao" required>

                        <button type="submit" class="btn btn-padrao">Cadastrar Usuário</button>

                        <section class="voltar-final">
                            <a href="lista.php" class="btn-link btn-voltar">Voltar</a>
                        </section>
                    </section>

                </article>
            </section>
        </form>
    </main>

    <script src="https://unpkg.com/imask"></script>
    <script src="../../assets/js/script.js"></script>
</body>

</html>