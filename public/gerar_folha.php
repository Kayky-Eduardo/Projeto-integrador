<?php

session_start();
include(__DIR__ . "/../BD/conexao.php");

$nome_usuario = $_SESSION['nome_usuario'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Teste – Gerar Folha de Pagamento</title>
</head>
<body>

<div class="card">
    <h2>Gerar Folha de Pagamento</h2>
    <h2>Olá, <?php echo $nome_usuario; ?></h2>
    <label>Mês:</label>

    <!-- Input do tipo month já valida mês/ano.
         min = menor data permitida.
         max = mês atual, impedindo seleção futura. -->
    <input type="month" id="mes" min="2024-01" max="<?= date('Y-m'); ?>">

    <!-- Botão para gerar PDF (abre em nova aba) -->
    <button onclick="gerarPDF()">Gerar PDF</button>
</div>

<script>

// Função chamada ao clicar em "Gerar PDF"
function gerarPDF() {

    let mes = document.getElementById("mes").value;
    let atual = new Date().toISOString().slice(0, 7);

    // Mesmas validações da função gerar()
    if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
        alert("Selecione um mês válido no formato YYYY-MM");
        return;
    }

    if (mes > atual) {
        alert("Você não pode escolher um mês futuro.");
        return;
    }

    // Abre o PHP que gera PDF em outra aba
    window.open("../api/api_gerar_pdf.php?mes=" + mes, "_blank");
}

</script>

</body>
</html>
