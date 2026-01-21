<!--
    MÓDULO: AUTENTICAÇÃO DO USUÁRIO

    OBJETIVO
        Realizar a validação de acesso ao sistema através de e-mail e senha

    ESTRUTURA SEMÂNTICA
        main      - Área principal do acesso
        section   - Bloco central de autenticação
        form      - Coleta de credenciais
        fieldset  - Agrupamento de campos
        label     - Identificação de campos
        input     - Entrada de dados
        button    - Ação de envio

    FUNCIONALIDADES
        1. Validação de e-mail e senha
        2. Criação de sessão do usuário
        3. Controle de login ativo
        4. Inserção de histórico de login
        5. Atualização automática de hash de senha
        6. Mensagem de erro em caso de falha

    ACESSIBILIDADE
        - Uso semântico de HTML
        - Leitores de tela reconhecem campos corretamente
        - role="alert" para mensagens de erro

    SEGURANÇA
        - Senha verificada com password_verify
        - Prepared Statements (mysqli)
        - Proteção contra SQL Injection
        - Hash automático quando detectada senha antiga

    OBSERVAÇÕES TÉCNICAS
        - Banco conectado via mysqli
        - Sessão controlada por PHP
        - Estilos centralizados em: ../assets/css/estilo.css
-->

<?php
include("../BD/conexao.php");
session_start();

$erro_login = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email'], $_POST['senha'])) {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $stmt = $conn->prepare("
        SELECT usuario.*, cargo.nome_cargo, cargo.nivel, cargo.id_cargo, usuario.senha_usuario
        FROM usuario
        JOIN cargo ON usuario.id_cargo = cargo.id_cargo
        WHERE usuario.email_usuario = ?
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute() or die("Falha ao executar o código SQL: " . $stmt->error);

        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $usuario = $result->fetch_assoc();
            $senha_banco = $usuario['senha_usuario'];

            if (password_verify($senha, $senha_banco) || $senha === $senha_banco) {
                $_SESSION['nivel'] = $usuario['nivel'];
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                $verificacao_logado = $conn->prepare("
                    SELECT id_login FROM login
                    WHERE id_usuario = ? AND data_fim IS NULL
                    LIMIT 1
                    ");

                $verificacao_logado->bind_param("i", $usuario['id_usuario']);
                $verificacao_logado->execute();
                $verificacao_logado = $verificacao_logado->get_result();

                if ($verificacao_logado && $verificacao_logado->num_rows > 0) {
                    $row = $verificacao_logado->fetch_assoc();
                    $logout = $conn->prepare("UPDATE login SET data_fim = NOW() WHERE id_login = ?");
                    $logout->bind_param("i", $row['id_login']);
                    $logout->execute();
                    $logout->close();
                    $verificacao_logado->close();
                }
                // não é definitivo, lembrar das dependencias
                // como cuidar do hash e etc.
                $update_login = $conn->prepare("
                    INSERT INTO login (email_login, id_usuario, id_cargo) VALUES
                    (?, ?, ?)
                    ");
                $update_login->bind_param(
                    'sii',
                    $usuario['email_usuario'],
                    $usuario['id_usuario'],
                    $usuario['id_cargo']
                );
                $update_login->execute();
                $_SESSION['id_login'] = $update_login->insert_id;
                $update_login->close();
                if ($senha === $senha_banco) {
                    $novo_hash = password_hash($senha, PASSWORD_DEFAULT);

                    $update = $conn->prepare("UPDATE usuario SET senha_usuario = ? WHERE id_usuario = ?");
                    $update->bind_param("si", $novo_hash, $usuario['id_usuario']);
                    $update->execute();
                    $update->close();
                    $conn->close();
                }
                header("Location: index.php");
            } else {
                $erro_login = "E-mail ou senha incorretos.";
            }
        } else {
            $erro_login = "E-mail ou senha incorretos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Acesso ao Sistema de RH</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
</head>

<body class="pagina-login">
    <main role="main" aria-label="Tela de acesso">
        <section class="login-sistema" aria-label="Área de autenticação">
            <h2>Acesso ao Sistema</h2>
            <p>Utilize suas credenciais corporativas</p>

            <?php if (!empty($erro_login)): ?>
                <p class="erro-login" role="alert">
                    <?= htmlspecialchars($erro_login) ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <fieldset>
                    <label for="email">E-mail corporativo</label>
                    <input type="email" id="email" name="email" required>

                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>

                    <button type="submit">Entrar</button>
                </fieldset>
            </form>
        </section>
    </main>
</body>

</html>