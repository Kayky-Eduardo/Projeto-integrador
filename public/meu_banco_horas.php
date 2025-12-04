<?php
session_start();
include("../BD/conexao.php");
include("../include/funcoes/funcoes_banco_horas.php");
include("../include/verificacao.php");

$dados = get_banco_horas($conn, $_SESSION['id_usuario']);

function entregar_dados($dados) {
    $saldo_antigo = $dados['saldo_antigo'];
    // $saldo_minutos = $dados['saldo_minutos'];
    // $saldo_horas = $dados['saldo_horas'];
    $saldo_formatado = $dados['saldo_formatado'] ?? 0;
    $ultima_atualizacao = $dados['ultima_atualizacao'] ?? "";

    echo "
    <article>
        <h2>$saldo_antigo</h1>
        <p>Saldo anterior</p>
    </article>
    ";

    echo "
    <article>
        <h2>$saldo_formatado</h1>
        <p>Saldo atual</p>
    </article>
    ";

    echo "
    <article>
        <h2>$ultima_atualizacao</h1>
        <p>Ultima atualização</p>
    </article>
    ";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu banco</title>
</head>
<body>
    <header>
        <?php include("../include/navbar.php");?>
        <h1>Sistema de RH</h1>        
    </header>

    <h1>Meu banco</h1>
    <?php entregar_dados($dados) ?>
</body>
</html>