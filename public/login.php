<?php
include("../BD/conexao.php");
session_start();

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
        if($result->num_rows == 1) {
            $usuario = $result->fetch_assoc();
            $senha_banco = $usuario['senha_usuario'];
            if (password_verify($senha, $senha_banco) || $senha === $senha_banco) {
                    $_SESSION['nivel'] = $usuario['nivel'];
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                    $verificacao_logado = $conn->prepare("
                    SELECT id_login FROM login
                    WHERE id_usuario = ? AND (data_fim IS NULL OR data_fim = '')
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
                    $update_login = $conn -> prepare("
                    INSERT INTO login (email_login, id_usuario, id_cargo) VALUES
                    (?, ?, ?)
                    ");
                    $update_login->bind_param(
                        'sii', $usuario['email_usuario'], $usuario['id_usuario'], $usuario['id_cargo']
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
                echo "Falha ao tentar logar! Verifique se o email ou senha estão digitados de forma correta";
            }
        } else {
            echo "Falha ao tentar logar! Verifique se o email ou senha estão digitados de forma correta";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>
    <form method="POST" action="">
        <h1>Login</h1>

        <label>E-mail:</label>
        <input type="text" name="email">

        <label>Senha</label>
        <input type="password" name="senha">

        <button type="submit">Login</button>
    </form>
</body>
</html>