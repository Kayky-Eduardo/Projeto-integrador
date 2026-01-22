<?php

/**
 * =============================================================
 * ARQUIVO: login.php
 * MÓDULO: Autenticação de Usuário
 * =============================================================
 * 
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Arquivo responsável pelo processo completo de autenticação de
 * usuários no sistema.
 *
 * Executa:
 * - Validação de credenciais (e-mail e senha)
 * - Verificação de senha criptografada (password_verify)
 * - Regeneração segura de sessão
 * - Controle de sessão ativa (apenas um login por usuário)
 * - Registro de login na tabela de auditoria
 * - Rehash automático de senhas antigas
 * 
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Verifica se a requisição é do tipo POST
 * 2. Valida a existência dos campos 'email' e 'senha'
 * 3. Consulta o usuário pelo e-mail (prepared statement)
 * 4. Valida a senha utilizando hash seguro
 * 5. Regenera o ID da sessão (proteção contra session fixation)
 * 6. Finaliza possíveis sessões ativas anteriores do usuário
 * 7. Registra o novo login na tabela 'login'
 * 8. Atualiza o hash da senha, se necessário
 * 9. Redireciona o usuário autenticado para a página inicial
 *
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Uso exclusivo de prepared statements (anti-SQL Injection)
 * - Senhas verificadas com password_verify()
 * - Rehash automático via password_needs_rehash()
 * - session_regenerate_id(true) após autenticação
 * - Mensagens de erro genéricas (anti-enumeração de usuários)
 * - Escapamento de saída com htmlspecialchars()
 *
 *
 * ACESSIBILIDADE
 * -------------------------------------------------------------
 * - Uso correto de <fieldset> e <legend>
 * - Mensagens de erro com role="alert"
 * - Labels associados corretamente aos inputs
 *
 *
 * DEPENDÊNCIAS
 * -------------------------------------------------------------
 * - "../BD/conexao.php": Responsável pela conexão com o banco de 
 *   dados MySQL
 *
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * - usuario
 *   • id_usuario
 *   • nome_usuario
 *   • email_usuario
 *   • senha_usuario
 *   • id_cargo
 *
 * - cargo
 *   • id_cargo
 *   • nivel
 *
 * - login
 *   • id_login
 *   • email_login
 *   • id_usuario
 *   • id_cargo
 *   • data_inicio
 *   • data_fim
 *
 *
 * BOAS PRÁTICAS APLICADAS
 * -------------------------------------------------------------
 * - SELECTs enxutos (sem uso de *)
 * - Separação clara de responsabilidades
 * - Fechamento explícito de statements
 * - Código legível e organizado
 * - CSS com variáveis globais no :root
 *
 *
 * OBSERVAÇÕES
 * -------------------------------------------------------------
 * - A conexão ($conn) não é fechada manualmente, pois o PHP
 *   encerra automaticamente ao final do script.
 * - Statements preparados são fechados explicitamente para
 *   liberação imediata de recursos.
 *
 * 
 * -------------------------------------------------------------
 * Data: 22/01/2026
 * Versão: 1.0
 * =============================================================
 */

include("../BD/conexao.php");
session_start();

$mensagem_padrao = "E-mail ou senha incorretos.";
$erro_login = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'], $_POST['senha'])) {
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        $stmt = $conn->prepare("
            SELECT 
                usuario.id_usuario,
                usuario.nome_usuario,
                usuario.email_usuario,
                usuario.senha_usuario,
                cargo.id_cargo,
                cargo.nivel
            FROM usuario
            JOIN cargo ON usuario.id_cargo = cargo.id_cargo
            WHERE usuario.email_usuario = ?
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            $stmt->close();

            if (password_verify($senha, $usuario['senha_usuario'])) {
                session_regenerate_id(true);
                $_SESSION['nivel']        = $usuario['nivel'];
                $_SESSION['id_usuario']   = $usuario['id_usuario'];
                $_SESSION['nome_usuario'] = $usuario['nome_usuario'];

                // Garante apenas um login ativo por usuário
                $stmt_login = $conn->prepare("
                    SELECT id_login
                    FROM login
                    WHERE id_usuario = ? AND data_fim IS NULL
                    LIMIT 1
                ");

                $stmt_login->bind_param("i", $usuario['id_usuario']);
                $stmt_login->execute();
                $result_login = $stmt_login->get_result();

                if ($result_login->num_rows > 0) {
                    $row = $result_login->fetch_assoc();

                    $stmt_logout = $conn->prepare("
                        UPDATE login SET data_fim = NOW()
                        WHERE id_login = ?
                    ");

                    $stmt_logout->bind_param("i", $row['id_login']);
                    $stmt_logout->execute();
                    $stmt_logout->close();
                }

                $stmt_login->close();

                $stmt_insert = $conn->prepare("
                    INSERT INTO login (email_login, id_usuario, id_cargo)
                    VALUES (?, ?, ?)
                ");

                $stmt_insert->bind_param(
                    "sii",
                    $usuario['email_usuario'],
                    $usuario['id_usuario'],
                    $usuario['id_cargo']
                );

                $stmt_insert->execute();
                $_SESSION['id_login'] = $stmt_insert->insert_id;
                $stmt_insert->close();

                if (password_needs_rehash($usuario['senha_usuario'], PASSWORD_DEFAULT)) {
                    $novo_hash = password_hash($senha, PASSWORD_DEFAULT);

                    $stmt_update = $conn->prepare("
                        UPDATE usuario SET senha_usuario = ?
                        WHERE id_usuario = ?
                    ");

                    $stmt_update->bind_param("si", $novo_hash, $usuario['id_usuario']);
                    $stmt_update->execute();
                    $stmt_update->close();
                }

                header("Location: index.php");
                exit;
            } else {
                $erro_login = $mensagem_padrao;
            }
        } else {
            $stmt->close();
            $erro_login = $mensagem_padrao;
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
        /* 0 - VARIÁVEIS DO SISTEMA */
        :root {
            /* Cores Principais */
            --cor-principal: #020617;
            --cor-principal-hover: #020617e8;
            --cor-fundo-branco: #ffffff;
            --cor-texto-suave: #64748b;
            --cor-texto-branco: white;
            --cor-borda-padrao: #cbd5e1;

            /* Cores de Erro */
            --cor-fundo-erro: #fee2e2;
            --cor-texto-erro: #7f1d1d;
            --cor-borda-erro: #fecaca;

            /* Bordas */
            --raio-borda-padrao: 4px;
            --raio-borda-grande: 8px;

            /* Tipografia */
            --tamanho-texto-padrao: 0.9rem;
            --tamanho-titulo-padrao: 1.4rem;

            /* Sombras */
            --sombra-card: 0 20px 50px rgba(0, 0, 0, 0.35), 0 3px 10px rgba(0, 0, 0, 0.12);
            --sombra-fundo: rgba(2, 6, 23, 0.55);
        }


        /* 1 - RESET GLOBAL */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 2 - LAYOUT GLOBAL */
        main {
            flex: 1;
            padding: 2rem;
        }

        .main-center {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hidden {
            display: none;
        }

        /* 3 - FORMULÁRIOS (LOGIN) */
        .login-sistema input {
            padding: 0.65rem;
            border: 1px solid var(--cor-borda-padrao);
            border-radius: var(--raio-borda-padrao);
            font-size: var(--tamanho-texto-padrao);
            background-color: var(--cor-fundo-branco);
        }

        .login-sistema input:focus {
            outline: none;
            border-color: var(--cor-principal);
        }

        /* LOGIN.PHP */
        .pagina-login {
            background: url(../assets/IMG/fundo_login.jpg) no-repeat center center fixed;
            background-size: cover;
        }

        .pagina-login::before {
            content: "";
            position: fixed;
            inset: 0;
            background: var(--sombra-fundo);
            z-index: -1;
        }

        .login-sistema {
            max-width: 460px;
            width: 100%;
            background-color: var(--cor-fundo-branco);
            padding: 2.4rem;
            border-radius: var(--raio-borda-grande);
            border: 1px solid var(--cor-borda-padrao);
            box-shadow: var(--sombra-card);
        }

        /* Título */
        .login-sistema h2 {
            font-size: var(--tamanho-titulo-padrao);
            color: var(--cor-principal);
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        /* Subtítulo */
        .login-sistema p {
            font-size: var(--tamanho-texto-padrao);
            color: var(--cor-texto-suave);
            margin-bottom: 1.6rem;
        }

        /* Fieldset */
        .login-sistema fieldset {
            border: none;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        /* Labels */
        .login-sistema label {
            font-weight: 600;
            font-size: var(--tamanho-texto-padrao);
            color: var(--cor-principal);
        }

        /* Botão */
        .login-sistema button {
            margin-top: 1.2rem;
            padding: 0.75rem;
            background-color: var(--cor-principal);
            color: var(--cor-texto-branco);
            border: none;
            font-size: var(--tamanho-texto-padrao);
            border-radius: var(--raio-borda-padrao);
            cursor: pointer;
            letter-spacing: 0.4px;
            transition: background-color 0.2s ease;
        }

        .login-sistema button:hover {
            background-color: var(--cor-principal-hover);
        }

        /* Erro */
        .erro-login {
            background-color: var(--cor-fundo-erro);
            color: var(--cor-texto-erro);
            border: 1px solid var(--cor-borda-erro);
            border-radius: var(--raio-borda-padrao);
            padding: 0.7rem 0.9rem;
            font-size: var(--tamanho-texto-padrao);
            margin-bottom: 1rem;
        }

        /* 4 - RESPONSIVIDADE */
        @media (max-width: 1000px) {
            main {
                padding: 1.2rem;
            }
        }
    </style>

    <!-- <link rel="stylesheet" href="../assets/css/estilo.css"> -->
</head>

<body class="pagina-login">
    <main class="main-center">
        <section class="login-sistema">
            <h2>Acesso ao Sistema</h2>
            <p>Utilize suas credenciais corporativas</p>

            <?php if (!empty($erro_login)): ?>
                <p class="erro-login" role="alert">
                    <?= htmlspecialchars($erro_login) ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <fieldset>
                    <legend class="hidden">Credenciais de Acesso</legend>

                    <label for="email">E-mail corporativo</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>

                    <button type="submit">Entrar</button>
                </fieldset>
            </form>
        </section>
    </main>
</body>

</html>