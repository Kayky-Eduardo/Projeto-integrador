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
    <style>
        /* =========================
   0 - VARIÁVEIS DO SISTEMA
========================= */
        :root {
            --cor-principal: #020617;
            --cor-principal-hover: #020617e8;
            --cor-destaque: #38bdf8;
        }

        /* =========================
   1 - RESET GLOBAL
========================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 100%;
        }

        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background-color: #F2F2F2;
            color: #0D0D0D;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* =========================
   2 - LAYOUT GLOBAL
========================= */
        main {
            flex: 1;
            padding: 2rem;
        }

        .main-center {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* =========================
   5 - FORMULÁRIOS (LOGIN)
========================= */
        .login-sistema input {
            padding: 0.65rem;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 0.9rem;
            background-color: #f8fafc;
        }

        .login-sistema input::placeholder {
            color: #94a3b8;
        }

        .login-sistema input:focus {
            outline: none;
            border-color: #020617;
        }

        /* =========================
   LOGIN.PHP
========================= */
        .pagina-login {
            background: url(../assets/IMG/fundo_login.jpg) no-repeat center center fixed;
            background-size: cover;
        }

        .pagina-login::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.55);
            z-index: -1;
        }

        .login-sistema {
            max-width: 460px;
            width: 100%;
            background-color: #ffffff;
            padding: 2.4rem;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.35),
                0 3px 10px rgba(0, 0, 0, 0.12);
        }

        /* título */
        .login-sistema h2 {
            font-size: 1.4rem;
            color: #020617;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        /* subtítulo */
        .login-sistema p {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 1.6rem;
        }

        /* fieldset */
        .login-sistema fieldset {
            border: none;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        /* labels */
        .login-sistema label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #334155;
        }

        /* botão */
        .login-sistema button {
            margin-top: 1.2rem;
            padding: 0.75rem;
            background-color: #020617;
            color: white;
            border: none;
            font-size: 0.9rem;
            border-radius: 4px;
            cursor: pointer;
            letter-spacing: 0.4px;
            transition: background-color 0.2s ease;
        }

        .login-sistema button:hover {
            background-color: var(--cor-principal-hover);
        }

        /* erro */
        .erro-login {
            background-color: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 0.7rem 0.9rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        /* =========================
   RESPONSIVIDADE
========================= */
        @media (max-width: 1000px) {
            main {
                padding: 1.2rem;
            }
        }
    </style>

    <!-- <link rel="stylesheet" href="../assets/css/estilo.css"> -->
</head>

<body class="pagina-login">
    <main role="main" aria-label="Tela de acesso" class="main-center">
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