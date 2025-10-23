<?php
    // função para verificar se o login do usuário é válido
function verificar_login($conn) {
    $stmt = $conn->prepare("
        select data_fim from login where id_login = ?;     
    ");
    $stmt->bind_param("i", $_SESSION['id_login']);
    $stmt->execute() or die("Falha ao executar o código SQL: " . $stmt->error);
    $result = $stmt->get_result();
    $encontrado = ($result->num_rows == 1) ? true : false;

    if(!isset($_SESSION['id_usuario']) || !$encontrado) {
       return header("Location: logout.php");
    }
}
?>