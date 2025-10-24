<?php
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$verificacao_logado = $conn->prepare("
SELECT id_login, email_login, data_inicio FROM login
WHERE data_fim IS NULL OR data_fim = '';
");
$verificacao_logado->execute();
$verificacao_logado = $verificacao_logado->get_result();
$usuarios = [];
echo "Usuarios logados: ";
while ($row = $verificacao_logado->fetch_assoc()) {
    echo "<p>id: " . $row['id_login'] . "<br>email: " . $row['email_login'] . "<br>inicio: " . $row['data_inicio'] . "</p>";
}
?>