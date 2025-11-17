<?php
include '../../BD/conexao.php';
include '../../include/verificacao.php'; // garante que o usuário está logado

header('Content-Type: application/json');

$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    echo json_encode(['erro' => true, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

// Busca pausa ativa do usuário
$sql = "SELECT * FROM pausa WHERE id_usuario = ? AND status = 'ativa' ORDER BY id_pausa DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['erro' => true, 'mensagem' => 'Nenhuma pausa ativa encontrada.']);
    exit;
}

$pausa = $result->fetch_assoc();
$id_pausa = $pausa['id_pausa'];
$inicio = $pausa['inicio'];

// Busca configuração da pausa
$sql = "SELECT * FROM pausa_config WHERE id_usuario = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$configRes = $stmt->get_result();
$config = $configRes->fetch_assoc();

$tempo_min = $config['tempo_min'] ?? 5;
$tempo_max = $config['tempo_max'] ?? 15;

// Calcula o tempo de pausa (em minutos)
$sql = "SELECT TIMESTAMPDIFF(MINUTE, ?, NOW()) AS minutos";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $inicio);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$minutos = intval($row['minutos']);

// Verifica se respeitou o tempo mínimo
if ($minutos < $tempo_min) {
    echo json_encode(['erro' => true, 'mensagem' => "A pausa mínima é de $tempo_min minutos. Aguarde mais um pouco."]);
    exit;
}

// Encerra pausa 
$sql = "UPDATE pausa SET fim = NOW(), status = 'encerrada' WHERE id_pausa = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pausa);

if ($stmt->execute()) {
    echo json_encode([
        'erro' => false,
        'mensagem' => "Pausa encerrada com sucesso! Duração total: {$minutos} minuto(s)."
    ]);
} else {
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao encerrar pausa.']);
}
?>
