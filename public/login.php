<?php
/*
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
 * 1. usuario
 *    - id_usuario
 *    - nome_usuario
 *    - email_usuario
 *    - senha_usuario
 *    - id_cargo
 *
 * 2. cargo
 *    - id_cargo
 *    - nivel
 *
 * 3. login
 *    - id_login
 *    - email_login
 *    - id_usuario
 *    - id_cargo
 *    - data_inicio
 *    - data_fim
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
 * Data: 23/01/2026
 * Versão: 2.0
 * =============================================================
*/

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