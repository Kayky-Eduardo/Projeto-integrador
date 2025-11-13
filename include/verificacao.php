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
        header("Location: /Projeto-integrador/public/logout.php");
        exit;
    }

    $row = $result->fetch_assoc();
    if (!is_null($row['data_fim'])) {
        header("Location: /Projeto-integrador/public/logout.php");
        exit;
    }
}


// pensei em fazer algo do tipo
// sql => tempo_logado = TIMESTAMPDIFF(MINUTE, data_inicio, agora)
// se tempo logado > $_GLOBAL['hora_max_extra'];
// executa o script abaixo
function verificar_tempo_logado($conn) {
    $verificacao = $conn->query("
        
    ");
    /*
    UPDATE login
    SET data_fim = NOW()
    WHERE 
    AND TIMESTAMPDIFF(MINUTE, data_inicio, NOW())
    AND data_fim IS NULL;
    */

}
?>