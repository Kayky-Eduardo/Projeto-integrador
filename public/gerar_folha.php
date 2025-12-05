<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Teste – Gerar Folha de Pagamento</title>
</head>
<body>

<div class="card">
    <h2>Gerar Folha de Pagamento</h2>

    <!-- Campo que permite selecionar a competência da folha (formato YYYY-MM) -->
    <label>Mês:</label>
    <input type="month" id="mes">

    <!-- Botão que dispara a função gerar() no JavaScript -->
    <button onclick="gerar()">Gerar Folha</button>
</div>

<h3>Resposta da API:</h3>

<!-- Aqui será exibido o texto retornado -->
<pre id="resposta">{ esperando requisição... }</pre>


<script>
// Função executada ao clicar no botão
function gerar() {

    // Obtém o valor do campo <input type="month">
    let mes = document.getElementById("mes").value;

    // Validação simples: verifica se está no formato YYYY-MM
    if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
        alert("Selecione um mês válido no formato YYYY-MM");
        return; // Impede o envio se estiver inválido
    }

    // Envia a requisição para a API
    fetch("../api/api_folha.php", {
        method: "POST", // Método HTTP
        headers: { "Content-Type": "application/json" }, // Corpo em JSON
        body: JSON.stringify({
            mes: mes // Dados enviados para a API
        })
    })

    // A API retorna texto puro
    .then(res => res.text())

    // Exibe o resultado dentro do <pre id="resposta">
    .then(txt => {           
        document.getElementById("resposta").textContent = txt;
    })

    // Caso alguma falha ocorra
    .catch(err => {
        document.getElementById("resposta").textContent =
            "Erro ao comunicar com a API: " + err;
    });
}
</script>
</body>
</html>