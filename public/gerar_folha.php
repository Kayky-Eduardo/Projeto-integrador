<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Teste – Gerar Folha de Pagamento</title>
</head>
<body>

<div class="card">
    <h2>Gerar Folha de Pagamento</h2>

    <label>Mês:</label>

    <!-- Input do tipo month já valida mês/ano.
         min = menor data permitida.
         max = mês atual, impedindo seleção futura. -->
    <input type="month" id="mes" min="2005-01" max="<?= date('Y-m'); ?>">

    <!-- Botão para gerar apenas a folha em HTML/texto -->
    <button onclick="gerar()">Gerar Folha</button>

    <!-- Botão para gerar PDF (abre em nova aba) -->
    <button onclick="gerarPDF()">Gerar PDF</button>
</div>

<h3>Resposta da API:</h3>

<!-- Área onde o retorno da API será exibido -->
<pre id="resposta">{ esperando requisição... }</pre>

<script>

// Função chamada ao clicar em "Gerar Folha"
function gerar() {

    // Pega o valor do <input>
    let mes = document.getElementById("mes").value;

    // Captura o mês atual no formato YYYY-MM
    let atual = new Date().toISOString().slice(0, 7);

    // Validação: campo vazio ou formato inválido
    if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
        alert("Selecione um mês válido no formato YYYY-MM");
        return;
    }

    // Impede seleção de meses futuros
    if (mes > atual) {
        alert("Você não pode escolher um mês futuro.");
        return;
    }

    // Envia requisição POST para api_folha.php
    fetch("../api/api_folha.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ mes: mes })  // Envia JSON
    })

    // Recebe a resposta como texto
    .then(res => res.text())

    // Exibe a resposta dentro do <pre id="resposta">
    .then(txt => {
        document.getElementById("resposta").textContent = txt;
    })

    // Caso aconteça algum erro na comunicação
    .catch(err => {
        document.getElementById("resposta").textContent =
            "Erro ao comunicar com a API: " + err;
    });
}



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
