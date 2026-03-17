<?php
// Conexão com banco de dados
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// ID usuário logado
$id_usuario = $_SESSION['id_usuario'];

// pegar valores de empresa
$sql_empresa = $conn->prepare("
    SELECT *
    FROM empresas
");

$sql_empresa->execute();
$empresa = $sql_empresa->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $dados = json_decode(file_get_contents('php://input'), true);
    if($dados){
        $id_usuario = $dados['id_usuario'] ?? '';
        if (isset($dados['acao'] ) && $dados['acao'] == 'salvar'){
            $d = $dados['dicionario'] ?? [];

            // caso já exista empresa - UPDATE
            if (isset($empresa)){
                $sql = $conn->prepare("UPDATE empresas SET
                    id_modificador = ?,
                    data_modificacao = CURDATE(),
                    razao_social = ?,
                    nome_fantasia = ?,
                    cnpj = ?,
                    uf = ?,
                    cidade = ?,
                    bairro = ?,
                    numero = ?,
                    cep = ?
                    WHERE id_modificador = ?");

                // 'ssssssssi' significa: 8 strings e 2 inteiros (id_usuario)
                $sql->bind_param('issssssssi',
                    $id_usuario,
                    $d['razao_social'],
                    $d['nome_fantasia'],
                    $d['cnpj'],
                    $d['estado'],
                    $d['cidade'],
                    $d['bairro'],
                    $d['numero'],
                    $d['cep'],
                    $id_usuario
                );

                if ($sql->execute()) {
                    echo json_encode(['status' => 'sucesso', 'msg' => 'Dados da empresa editados com sucesso!']);
                } else {
                    echo json_encode(['status' => 'erro', 'msg' => 'Erro inesperado!' . $conn->error]);
                }
                exit;
                // caso NÃO exista empresa - INSERT
            } else{
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
                    echo json_encode(['status' => 'sucesso', 'msg' => 'Dados da empresa Salvos!']);
                } else {
                    echo json_encode(['status' => 'erro', 'msg' => 'Erro ao salvar dados!' . $conn->error]);
                }
                exit;
            }
        } else if (isset($dados['acao'] ) && $dados['acao'] == 'excluir'){
            $sql = $conn->prepare("DELETE FROM empresas");
            if ($sql->execute()) {
                    echo json_encode(['status' => 'sucesso', 'msg' => 'Dados da empresa Excluídos!']);
            } else {
                echo json_encode(['status' => 'erro', 'msg' => 'Erro ao excluir dados!' . $conn->error]);
            }
            exit;
        }
    }
}

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

<section id="holerite">
<a href="gerar_folhas_todos.php">voltar</a>
<h1>Edição De Dados da Empresa</h1>

<form action="" id="meuForm">
    <label>Razão Social</label>
    <br>
    <input type="text" name="razao_social" value="<?= isset($empresa['razao_social']) ? $empresa['razao_social'] : '' ?>" required>
    <br>

    <label>Nome Fantasia</label>
    <br>
    <input type="text" name="nome_fantasia" value="<?= isset($empresa['nome_fantasia']) ? $empresa['nome_fantasia'] : '' ?>" required>
    <br>

    <label>CNPJ</label>
    <br>
    <input type="number" name="cnpj" value="<?= isset($empresa['cnpj']) ? $empresa['cnpj'] : '' ?>" required>
    <br>

    <label>Estado</label>
    <br>
    <input type="text" name="estado" value="<?= isset($empresa['uf']) ? $empresa['uf'] : '' ?>" required>
    <br>

    <label>Cidade</label>
    <br>
    <input type="text" name="cidade" value="<?= isset($empresa['cidade']) ? $empresa['cidade'] : '' ?>" required>
    <br>

    <label>Bairro</label>
    <br>
    <input type="text" name="bairro" value="<?= isset($empresa['bairro']) ? $empresa['bairro'] : '' ?>" required>
    <br>

    <label>Número</label>
    <br>
    <input type="number" name="numero" value="<?= isset($empresa['numero']) ? $empresa['numero'] : '' ?>" required>
    <br>

    <label>CEP</label>
    <br>
    <input type="number" name="cep" value="<?= isset($empresa['cep']) ? $empresa['cep'] : '' ?>" required>
    <br>
</form>


<br>

<br>
<button class="btn-salvar" id="<?= $id_usuario ?>">Salvar</button>
<button class="btn-excluir" id="<?= $id_usuario ?>">Excluir</button>

<table>
    <caption><b>Empresa</b></caption>
    <thead>
        <tr>
            <th>ID Empresa</th>
            <th>ID Modificador</th>
            <th>Data Modificação</th>

            <th>Razão Social</th>
            <th>Nome Fantasia</th>
            <th>CNPJ</th>
            <th>CEP</th>
            <th>Estado</th>
            <th>Cidade</th>
            <th>Bairro</th>
            <th>Número</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <?php  if ($empresa) foreach($empresa as $e): ?>
                <td><?= isset($e) ? $e : '⊘'?></td>
            <?php endforeach;?>
        </tr>
    </tbody>
</table>

</section>

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
                    if(data.status === 'sucesso') chamarPnotifySuccess("Sucesso!", data.msg);; // Recarrega para ver a mudança
                })
                .catch(err => console.error("Erro na requisição:", err));
        }
    })

    // excluir
    btnExcluir = document.querySelector('.btn-excluir');
    btnExcluir.addEventListener('click', function(){
        let resposta = confirm('Tem certeza que deseja Excluir?');
        if (resposta){
            fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        acao: 'excluir',
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'sucesso') chamarPnotifySuccess("Sucesso!", data.msg); // Recarrega para ver a mudança
                })
                .catch(err => console.error("Erro na requisição:", err));
        }
    })
    //CÓDIGO DAVI ↑↑↑↑↑

</script>

</body>
</html>