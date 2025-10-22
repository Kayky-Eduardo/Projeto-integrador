<?php
include("../BD/conexao.php");
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
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email'], $_POST['senha'])) {
        $email = $_POST['email'];
        $senha = $_POST['senha'];
        $stmt = $conn->prepare("
        SELECT usuario.*, cargo.nome_cargo, cargo.nivel, usuario.senha_usuario
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
                if(!isset($_SESSION)) {
                    session_start();
                    // ini_set('session.save_handler', 'redis');
                    // ini_set('session.save_path', 'tcp://127.0.0.1:6379');
                    $_SESSION['nivel'] = $usuario['nivel'];
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nome_usuario'] = $usuario['nome_usuario'];

                    if ($senha === $senha_banco) {
                        $novo_hash = password_hash($senha, PASSWORD_DEFAULT);

                        $update = $conn->prepare("UPDATE usuario SET senha_usuario = ? WHERE id_usuario = ?");
                        $update->bind_param("si", $novo_hash, $usuario['id_usuario']);
                        $update->execute();
                    }
                    header("Location: index.php");
                }
            } else {
                echo "Falha ao tentar logar! Verifique se o email ou senha estão digitados de forma correta";
            }
        } else {
            echo "Falha ao tentar logar! Verifique se o email ou senha estão digitados de forma correta";
        }
    }
}
?>