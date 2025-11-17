<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// função para verificar se o login do usuário é válido

function verificar_login($conn) {
    if (!isset($_SESSION['id_login']) || !isset($_SESSION['id_usuario'])) {
        header("Location: /Projeto-integrador/public/logout.php");
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
    $verificacao_tempo = $conn->query("
        SELECT TIMESTAMPDIFF(MINUTE, data_inicio, CURRENT_TIMESTAMP) AS minutos_passados
        FROM login
        WHERE data_fim is null;
    ");
    $result = $verificacao_tempo->get_result();

    $linha = $result->fetch_row();

    $tempo_logado = $linha[0];

    // teste
    if ($tempo_logado > 480) {
        header("Location: /Projeto-integrador/public/logout.php");
        exit;
    }
    // se a verificacao retornar acima do tempo maximo estimado
    // ele seta a data_fim e então a verificação acima vai derrubar o login
}
?>