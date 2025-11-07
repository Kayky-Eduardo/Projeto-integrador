<?php
include "../../BD/conexao.php";

// Verifica se existe alguma pausa ativa
$sql = "SELECT p.*, c.tempo_max, c.saida_automatica 
        FROM pausa p
        JOIN pausa_config c ON p.id_usuario = c.id_usuario
        WHERE p.fim IS NULL";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $id_pausa = $row['id_pausa'];
    $id_usuario = $row['id_usuario'];
    $inicio = $row['inicio'];
    $tempo_max = (int)$row['tempo_max'];
    $saida_automatica = (int)$row['saida_automatica'];

    // Calcula quanto tempo a pausa ta ativa
    $diff_sql = "SELECT TIMESTAMPDIFF(MINUTE, '$inicio', NOW()) AS minutos_passados";
    $diff_result = $conn->query($diff_sql);
    $tempo_decorrido = $diff_result->fetch_assoc()['minutos_passados'];

    // Se passou do tempo máximo e a saída automática estiver ligada
    if ($tempo_decorrido >= $tempo_max && $saida_automatica == 1) {
        $update = "UPDATE pausa 
                SET fim = NOW(), duracao = TIMESTAMPDIFF(MINUTE, inicio, NOW()) 
                WHERE id_pausa = $id_pausa";
        $conn->query($update);


        echo "Pausa do usuário $id_usuario encerrada automaticamente após $tempo_decorrido minutos.<br>";
    }
}

$conn->close();
?>
