<?php
// Conexão com banco de dados
require_once "../../BD/conexao.php";
session_start();

// ID usuário logado
$id_usuario = $_SESSION['id_usuario'];

// CODIGUINHO DO DABI ↓
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    $dados = json_decode(file_get_contents('php://input'), true);

    if($dados){
        if (isset($dados['acao']) && $dados['acao'] == 'salvar'){
            $id_usuario = $dados['id_usuario'] ?? '';
            $d = $dados['dicionario'] ?? [];
            
            $sql = $conn->prepare("INSERT INTO empresas (
            id_modificador, 
            data_modificacao, 
            razao_social, 
            nome_fantasia, 
            cnpj, 
            uf, 
            cidade, 
            bairro, 
            numero, 
            cep
            ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)");

            // Mapeamento: 1 inteiro (id) + 8 strings
            $tipos = 'issssssss'; 
            
            $sql->bind_param($tipos, 
                $id_usuario,
                $d['razao_social'], 
                $d['nome_fantasia'], 
                $d['cnpj'], 
                $d['estado'], 
                $d['cidade'], 
                $d['bairro'], 
                $d['numero'], 
                $d['cep']
            );

            if ($sql->execute()) {
                echo json_encode(['status' => 'sucesso', 'msg' => 'Dados da empresa salvos!']);
            } else {
                echo json_encode(['status' => 'erro', 'msg' => 'Erro ao salvar dados.']);
            }
            exit;
        }
    }
}
// CODIGUINHO DO DABI ↑

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Dados da Empresa</title>
</head>
<style>
body { font-family: Arial; padding: 25px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
td, th { border: 1px solid #444; padding: 8px; }
.titulo { background: #ddd; font-weight: bold; }
h1 { text-align: center; }
button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
</style>
<body>

<div id="holerite">
<a href="gerar_folhas_todos.php">voltar</a>
<h1>Edição De Dados da Empresa</h1>

<form action="" id="meuForm">
    <label>Razão Social</label>
    <br>
    <input type="text" name="razao_social" required>
    <br>

    <label>Nome Fantasia</label>
    <br>
    <input type="text" name="nome_fantasia" required>
    <br>

    <label>CNPJ</label>
    <br>
    <input type="number" name="cnpj" required>
    <br>

    <label>Estado</label>
    <br>
    <input type="text" name="estado" required>
    <br>

    <label>Cidade</label>
    <br>
    <input type="text" name="cidade" required>
    <br>

    <label>Bairro</label>
    <br>
    <input type="text" name="bairro" required>
    <br>

    <label>Número</label>
    <br>
    <input type="number" name="numero" required>
    <br>

    <label>CEP</label>
    <br>
    <input type="number" name="cep" required>
    <br>
</form>


<br>

<br>
<button class="btn-salvar" id="<?= $id_usuario ?>">Salvar</button>

<table>
    <tr><td><b>Empresa</b></td></tr>
    <tr><td>Nome:</td><td><?= isset($empresa["nome_fantasia"]) ? $empresa['nome_fantasia'] : 'Sem Nome'?></td></tr>
    <tr><td>Endereço:</td><td> <?= isset($empresa["uf"]) ? $empresa['uf'] : 'Sem endereço'?></td></tr>
    <tr><td>CNPJ:</td><td><?= isset($empresa["cnpj"]) ? $empresa['cnpj'] : 'Sem CNPJ'?></td></tr>
</table>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    //CÓDIGO DAVI ↓↓↓↓↓
    
    // salvar
    btnSalvar = document.querySelector('.btn-salvar');
    btnSalvar.addEventListener('click', function(){
        let resposta = confirm('Tem certeza que deseja Salvar?');
        const idUsuario = this.getAttribute('id');
        if (resposta){
            let dicionario = {};
            const form = document.getElementById('meuForm');
            const formData = new FormData(form);

            for (let [chave, valor] of formData.entries()) {
                dicionario[chave] = valor;
            }
            
            fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        acao: 'salvar',
                        id_usuario: idUsuario,
                        dicionario : dicionario
                    })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.msg);
                    if(data.status === 'sucesso') location.reload(); // Recarrega para ver a mudança
                })
                .catch(err => console.error("Erro na requisição:", err));
        }
    })
    //CÓDIGO DAVI ↑↑↑↑↑

</script>

</body>
</html>