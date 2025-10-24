<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// função para verificar se o login do usuário é válido

function verificar_login($conn) {
    if (!isset($_SESSION['id_login']) || !isset($_SESSION['id_usuario'])) {
        header("Location: logout.php");
        exit;
    }
    $stmt = $conn->prepare("
        SELECT data_fim FROM login
        WHERE id_login = ? AND id_usuario = ? LIMIT 1
    ");
    $stmt->bind_param("ii", $_SESSION['id_login'], $_SESSION['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        header("Location: logout.php");
        exit;
    }

    $row = $result->fetch_assoc();
    if (!is_null($row['data_fim'])) {
        header("Location: logout.php");
        exit;
    }
}
?>