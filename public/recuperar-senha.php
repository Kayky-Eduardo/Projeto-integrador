<?php
include("../BD/conexao.php");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="../../assets/estilo.css">
</head>
<body>
    <form method="POST" action="">
        <h1>Recuperar Senha</h1>

        <label>E-mail:</label>
        <input type="email" name="email" required>

        <label>CPF:</label>
        <input type="text" name="cpf" required>

        <button type="submit">Verificar</button>
    </form>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se os campos foram enviados
    if (!empty($_POST['email']) && !empty($_POST['cpf'])) {

        $email = $_POST['email'];
        $cpf = $_POST['cpf'];

        // Prepara a consulta
        $stmt = $conn->prepare("SELECT senha_usuario FROM usuario WHERE email_usuario = ? AND cpf_usuario = ?");
        $stmt->bind_param("ss", $email, $cpf);
        $stmt->execute();
        $result = $stmt->get_result();

        // Verifica se encontrou o usuário
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            echo "<p style='color: green;'>Senha do usuário: <strong>{$usuario['senha_usuario']}</strong></p>";
        } else {
            echo "<p style='color: red;'>Nenhum usuário encontrado com esse e-mail e CPF.</p>";
        }

        $stmt->close();
    } else {
        echo "<p style='color: red;'>Preencha todos os campos.</p>";
    }
    echo "<a href='login.php'>Voltar</a>";
}
?>
