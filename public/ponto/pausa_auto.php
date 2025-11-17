<?php
include "../../BD/conexao.php";

// Busca pausas ativas junto com a configuração global
$sql = "
    SELECT 
        p.id_pausa, 
        p.id_usuario, 
        p.inicio,
        c.tempo_max, 
        c.saida_automatica
    FROM pausa p
    CROSS JOIN pausa_config c
    WHERE p.status = 'ativa'
";
$result = $conn->query($sql);

// Caso não haja pausas ativas
if ($result->num_rows === 0) {
    echo "Nenhuma pausa ativa no momento.<br>";
}

while ($row = $result->fetch_assoc()) {
    $id_pausa = $row['id_pausa'];
    $id_usuario = $row['id_usuario'];
    $inicio = $row['inicio'];
    $tempo_max = (int)$row['tempo_max'];
    $saida_automatica = (int)$row['saida_automatica'];

    // Calcula o tempo decorrido desde o início da pausa
    $diff_sql = "SELECT TIMESTAMPDIFF(MINUTE, ?, NOW()) AS minutos_passados";
    $stmt = $conn->prepare($diff_sql);
    $stmt->bind_param("s", $inicio);
    $stmt->execute();
    $diff_result = $stmt->get_result();
    $tempo_decorrido = $diff_result->fetch_assoc()['minutos_passados'] ?? 0;

    // Se passou do tempo máximo e a saída automática estiver habilitada
    if ($tempo_decorrido >= $tempo_max && $saida_automatica == 1) {
        $update = "
            UPDATE pausa 
            SET 
                fim = NOW(), 
                status = 'encerrada'
            WHERE id_pausa = ?
        ";
        $stmt2 = $conn->prepare($update);
        $stmt2->bind_param("i", $id_pausa);
        $stmt2->execute();

        echo "Pausa do usuário <b>$id_usuario</b> encerrada automaticamente após <b>$tempo_decorrido</b> minutos.<br>";
    } else {
        echo "Pausa ativa do usuário <b>$id_usuario</b> há <b>$tempo_decorrido</b> minutos.<br>";
    }
}

$conn->close();
?>
