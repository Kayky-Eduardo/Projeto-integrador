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
    <input type="month" id="mes">

    <button onclick="gerar()">Gerar Folha</button>
</div>

<h3>Resposta da API:</h3>
<pre id="resposta">{ esperando requisição... }</pre>

<script>
function gerar() {
    // Obtém o valor digitado no campo "mes"
    let mes = document.getElementById("mes").value;

    // Verifica se o mês foi preenchido e se está no formato correto YYYY-MM usando regex
    if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
        alert("Selecione um mês válido no formato YYYY-MM");
        return; // Para a execução se o formato estiver errado
    }

    // Envia uma requisição POST para o backend (API)
    fetch("http://localhost/Projeto-integrador/api/api_folha.php", {
        method: "POST",                           // Define o método como POST
        headers: { "Content-Type": "application/json" }, // Informa que o corpo será JSON
        body: JSON.stringify({
            mes: mes                              // Envia o mês no corpo da requisição
        })
    })
    // Recebe a resposta como texto
    .then(res => res.text())
    // Exibe a resposta dentro da <div id="resposta">
    .then(txt => {           
        document.getElementById("resposta").textContent = txt;
    })
    // Caso ocorra algum erro na comunicação com a API
    .catch(err => {
        document.getElementById("resposta").textContent =
            "Erro: " + err;
    });
}
</script>


</body>
</html>
