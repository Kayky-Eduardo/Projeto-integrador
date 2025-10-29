<?php
session_start();
include("../BD/conexao.php");

date_default_timezone_set('America/Sao_Paulo');
$data = new datetime();
$agora = $data->format('Y-m-d H:i:s');
$update = $conn->prepare("UPDATE login SET data_fim = ? WHERE id_login = ?");
$update->bind_param("si", $agora, $_SESSION['id_login']);
$update->execute();

// Destroi todas as variáveis de sessão
session_unset();
// Destroi a sessão
session_destroy();
// Redireciona para a página de login
header("Location: login.php");
exit();
?>