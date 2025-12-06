<?php
session_start();
include("../BD/conexao.php");
include("../include/funcoes/funcoes_banco_horas.php");
include("../include/verificacao.php");
date_default_timezone_set('America/Sao_Paulo');

if (isset($_SESSION['id_usuario'], $_SESSION['id_login'])) {
    $id_usuario = $_SESSION['id_usuario'];
    $id_login = $_SESSION['id_login'];
    
    //pegar tempo logado
    $coleta_tempo = verificar_tempo_logado($conn, $id_usuario, $id_login);
    
    if ($coleta_tempo >= 0) {
        adicionar_horas($conn, $id_usuario, $coleta_tempo);
    } else {
        retirar_horas($conn, $id_usuario, $coleta_tempo);
    }
}

$data = new datetime();
$agora = $data->format('Y-m-d H:i:s');
$update = $conn->prepare("UPDATE login SET data_fim = ? WHERE id_login = ?");
$update->bind_param("si", $agora, $id_login);
$update->execute();

// Destroi todas as variáveis de sessão
session_unset();
// Destroi a sessão
session_destroy();

// Redireciona para a página de login
header("Location: login.php");
exit();
?>