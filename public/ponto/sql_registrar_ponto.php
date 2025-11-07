<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registro'])) {

    // Validação básica que até um jegue saberia
    $id_usuario = $_SESSION['id_usuario'] ?? null;

    if (!$id_usuario) {
        echo "Erro: usuário não autenticado.";
        exit;
    }

    // atribuindo os posts as variáveis para facilitar meu mundinho
    $entrada         = $_POST['entrada'] ?? null;
    $saida           = $_POST['saida'] ?? null;
    $almoco_saida    = $_POST['almoco_saida'] ?? null;
    $almoco_entrada  = $_POST['almoco_entrada'] ?? null;
    $observacao  = $_POST['observacao'] ?? null;
    $data_ponto = date('Y-m-d'); // Pega a data atual no fuso correto

     // Verifica se já existe ponto hoje
    $check = $conn->prepare("SELECT id_ponto FROM ponto WHERE id_usuario = ? AND data_ponto = ?");
    $check->bind_param("is", $id_usuario, $data_ponto);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        echo "Você já registrou ponto hoje.";
        echo "<a href='registrar_ponto.php'>Voltar</a>";
        exit;
    }
    $check->close();

    // Criando inserção
    $sql = "INSERT INTO ponto (
                id_usuario, 
                data_ponto, 
                hora_entrada, 
                hora_saida, 
                hora_almoco_saida, 
                hora_almoco_retorno,
                observacao,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')";

    // statement prepare para preparar a query
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro ao preparar query: " . $conn->error);
    }

    // Associando os parâmetros(palavra bonita usada pra parecer inteligente)
    $stmt->bind_param(
        "issssss", 
        $id_usuario,
        $data_ponto,
        $entrada,
        $saida,
        $almoco_saida,
        $almoco_entrada,
        $observacao
    );

    // Execução
    if ($stmt->execute()) {
        header("Location: registrar_ponto.php?sucesso=1");
        exit;
    } else {
        echo "Erro ao registrar ponto: " . $stmt->error;
        echo "<a href='registrar_ponto.php'>Voltar</a>";
    }

    $stmt->close();
}
?>