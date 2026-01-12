<?php
session_start();

// Pega o nome do usuário logado da sessão
$nome_usuario = $_SESSION['nome_usuario'] ?? "Usuário"; // fallback se não tiver
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerar Folha de Pagamento</title>
</head>
<body>


<div class="card">

    <h2>Olá, <?= htmlspecialchars($nome_usuario) ?>!</h3>

    <h2>Gerar Folha de Pagamento</h2>

    <label>Mês:</label><br>

    <input type="month" id="mes" min="2005-01" max="<?= date('Y-m'); ?>">

    <br><br>

    <!-- Botão para gerar a folha em HTML -->
    <button onclick="gerar()">Ver Folha</button>

    <!-- Botão para gerar PDF -->
    <button onclick="gerarPDF()">Baixar PDF</button>
</div>

<script>
function gerar() {

    let mes = document.getElementById("mes").value;
    let atual = new Date().toISOString().slice(0, 7);

    if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
        alert("Selecione um mês válido no formato YYYY-MM");
        return;
    }

    if (mes > atual) {
        alert("Você não pode escolher um mês futuro.");
        return;
    }

    // Abre o holerite em HTML
    window.open("../api/api_gerar_pdf.php?mes=" + mes, "_blank");
}

function gerarPDF() {

    let mes = document.getElementById("mes").value;
    let atual = new Date().toISOString().slice(0, 7);

    if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
        alert("Selecione um mês válido no formato YYYY-MM");
        return;
    }

    if (mes > atual) {
        alert("Você não pode escolher um mês futuro.");
        return;
    }

    // Abre o holerite e chama a função de PDF automaticamente
    let win = window.open("../api/api_gerar_pdf.php?mes=" + mes, "_blank");

    win.onload = () => {
        win.gerarPDF();
    };
}
</script>

</body>
</html>
