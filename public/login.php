<?php
include("../BD/conexao.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
        SELECT usuario.*, cargo.nome_cargo, cargo.nivel
        FROM usuario
        JOIN cargo ON usuario.id_cargo = cargo.id_cargo
        WHERE usuario.email_usuario = ? and usuario.senha_usuario = ?
        ");
        $stmt->bind_param("ss", $email, $senha);
        $stmt->execute() or die("Falha ao executar o código SQL: " . $stmt->error);
        $result = $stmt->get_result();
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if(!isset($_SESSION)) {
                session_start();
                $_SESSION['nivel'] = $usuario['cargo.nivel'];
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                header("Location: index.php");
            }
        } else {
            echo "Falha ao tentar logar! Verifique se o email ou senha estão digitados de forma correta";
        }
    }
}
?>