<?php
// Conexão com banco de dados
require_once "../../BD/conexao.php";
session_start();

// CODIGUINHO DO DABI ↓
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    $dados = json_decode(file_get_contents('php://input'), true);

    if($dados){
        if (isset($dados['acao']) && $dados['acao'] == 'salvar'){
            $id_usuario = $dados['id_usuario'] ?? '';
            
            $sql = $conn->prepare("UPDATE empresas SET nome = ?, cnpj = ? WHERE id_usuario = ?");
            $sql->bind_param('ssi', $nome_empresa, $cnpj_empresa, $id_usuario);
            if ($sql->execute()) {
                echo json_encode(['status' => 'sucesso', 'msg' => 'Dados da empresa salvos!']);
            } else {
                echo json_encode(['status' => 'erro', 'msg' => 'Erro ao salvar dados.']);
            }
            
            // Por enquanto, apenas retornamos sucesso
            echo json_encode(['status' => 'sucesso', 'msg' => 'Dados da empresa salvos!']);
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

<body>

<div id="holerite">
<a href="gerar_folhas_todos.php?$mes=<?= $mes ?>">voltar</a>
<h1>Edição De Dados da Empresa</h1>

<form action="" id="meuForm">
    <label>Nome da Empresa</label>
    <input type="text" name="nome" required>

    <label>CNPJ</label>
    <input type="text" name="cnpj" required>
</form>



<br>
<button class="btn-salvar" id="<?= $id_usuario ?>">Salvar</button>

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
            let lista = [];
            const form = document.getElementById('meuForm');
            const formData = new FormData(form);

            for (let [chave, valor] of formData.entries()) {
                lista.push({chave, valor})
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