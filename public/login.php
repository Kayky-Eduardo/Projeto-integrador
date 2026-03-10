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

        try {
            $stmt = $conn->prepare("
                SELECT
                    email_usuario,
                    id_usuario,
                    nome_usuario,
                    senha_usuario,
                    cargo.id_cargo,
                    cargo.nivel,
                    conta_ativa
                FROM usuario
                JOIN cargo ON cargo.id_cargo = usuario.id_cargo
                WHERE email_usuario = ?;
            ");

            $stmt->bind_param("s", $email);
        
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $usuario = $result->fetch_assoc();
                $senha_banco = $usuario['senha_usuario'];
                
                if ((int)$usuario['conta_ativa'] !== 1) {
                    $erro_login = "Verifique se sua conta esta ativa com seu supervisor";
                } elseif (!password_verify($senha, $senha_banco)) {
                    $erro_login = "E-mail ou senha incorretos.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['nivel'] = $usuario['nivel'];
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                    $stmt_verificacao_logado = $conn->prepare("
                    SELECT id_login FROM login
                    WHERE id_usuario = ? AND data_fim IS NULL
                    LIMIT 1
                    ");
                    
                    $stmt_verificacao_logado->bind_param("i", $usuario['id_usuario']);
                    $stmt_verificacao_logado->execute();
                    $result_stmt_verificacao_logado = $stmt_verificacao_logado->get_result();
                    
                    if ($result_stmt_verificacao_logado && $result_stmt_verificacao_logado->num_rows > 0) {
                        $row = $result_stmt_verificacao_logado->fetch_assoc();
                        $logout = $conn->prepare("UPDATE login SET data_fim = NOW() WHERE id_login = ?");
                        $logout->bind_param("i", $row['id_login']);
                        $logout->execute();
                        $logout->close();
                        $stmt_verificacao_logado->close();
                    }
                    
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
                    if (password_needs_rehash($senha_banco, PASSWORD_DEFAULT)) {
                        $novo_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $update = $conn->prepare("UPDATE usuario SET senha_usuario = ? WHERE id_usuario = ?");
                        $update->bind_param("si", $novo_hash, $usuario['id_usuario']);
                            $update->execute();
                            $update->close();
                    } 

                    header("Location: index.php");
                    $conn->close();
                    exit; 
                }
            } else {
                $erro_login = "E-mail ou senha incorretos.";
            }
        } catch (Exception $e) {
            $erro_login = "Tente novamente mais tarde!";
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
    <main class="main-login">
        <section class="container login-sistema">
            <h2>Acesso ao Sistema</h2>
            <p>Utilize suas credenciais corporativas</p>

            <?php if (!empty($erro_login)): ?>
                <p class="erro-login" role="alert">
                    <?= htmlspecialchars($erro_login) ?>
                </p>
            <?php endif; ?>

            <form class="form" method="POST">
                <legend class="hidden">Credenciais de Acesso</legend>

                <label class="label" for="email">E-mail corporativo</label>
                <input type="email" class="input" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                <label class="label" for="senha">Senha</label>
                <input type="password" class="input" id="senha" name="senha" required>

                <button type="submit" class="btn btn-padrao">Entrar</button>
            </form>
        </section>
    </main>
</body>
</html>