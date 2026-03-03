<?php
// Conexão com banco de dados
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// -----------------------------
// 1. Recebe o mês (competência)
// -----------------------------
$mes = $_GET["mes"] ?? null;
if (!$mes) {
    // No redirecionamento
    header("Location: gerar_folhas_todos.php?erro=Mês inacessível.");
    exit();
};

$mes_comp = $mes . "-01";

// -----------------------------
// 2. Usuário
// -----------------------------
$id_usuario_logado = $_SESSION['id_usuario'];
$id_usuario = $_GET['id_usuario'] ?? $id_usuario_logado;

// -----------------------------
// 3. Nível usuário logado
// -----------------------------
$sql_nivel_logado = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");
$sql_nivel_logado->bind_param("i", $id_usuario_logado);
$sql_nivel_logado->execute();
$nivel_logado = $sql_nivel_logado->get_result()->fetch_assoc()['nivel'] ?? 0;

// -----------------------------
// 4. Nível usuário alvo
// -----------------------------
$sql_nivel_alvo = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");
$sql_nivel_alvo->bind_param("i", $id_usuario);
$sql_nivel_alvo->execute();
$nivel_alvo = $sql_nivel_alvo->get_result()->fetch_assoc()['nivel'] ?? 0;

// -----------------------------
// 5. Permissão
// -----------------------------
if ($id_usuario != $id_usuario_logado && $nivel_logado < $nivel_alvo) {
    header("Location: gerar_folhas_todos.php?erro=Acesso Negado.");
    exit();
}

// -----------------------------
// 6. Dados do usuário
// -----------------------------
$sql_user = $conn->prepare("
    SELECT u.nome_usuario, u.cpf_usuario, u.data_admissao,
           c.nome_cargo, c.salario_bruto
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");
$sql_user->bind_param("i", $id_usuario);
$sql_user->execute();
$user = $sql_user->get_result()->fetch_assoc();

// -----------------------------
// 7. Folha
// -----------------------------
$sql_folha = $conn->prepare("
    SELECT *
    FROM folhas
    WHERE id_usuario = ? AND mes_competencia = ?
");
$sql_folha->bind_param("is", $id_usuario, $mes_comp);
$sql_folha->execute();
$folha = $sql_folha->get_result()->fetch_assoc();

// -----------------------------
// 7.1 Verifica se a folha existe
// -----------------------------
if (!$folha) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Holerite não encontrado</title>
    </head>
    <body>
        <a href="../">voltar</a>
        <div>
            <h2>Holerite não encontrado</h2>
            <p>
                Não existe folha de pagamento para o mês<br>
                <b><?= date("m/Y", strtotime($mes_comp)); ?></b>.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}


// -----------------------------
// 8. Eventos
// -----------------------------
$sql_eventos = $conn->prepare("
    SELECT id_evento, tipo, descricao, valor 
    FROM eventos 
    WHERE id_usuario = ? AND mes_competencia = ?
");
$sql_eventos->bind_param("is", $id_usuario, $mes_comp);
$sql_eventos->execute();
$eventos = $sql_eventos->get_result()->fetch_all(MYSQLI_ASSOC);

// CODIGUINHO DO DABI ↓
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    $dados = json_decode(file_get_contents('php://input'), true);

    if($dados && isset($dados['acao'])){
        $acao = $dados['acao'] ?? '';
        // deletar
        if ($acao == 'deletar' && !empty($dados['id_evento'])) {
            if (isset($dados['confirmado']) && $dados['confirmado'] === true) {
                $sql = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
                $sql->bind_param('i', $dados['id_evento']);
                if ($sql->execute()) {
                echo json_encode(['status' => 'sucesso', 'msg' => 'Evento Deletado!']);
                } else {
                    echo json_encode(['status' => 'erro', 'msg' => 'Erro ao deletar.']);
                }
                exit;
            }
        } 
        // editar
        else if($acao == 'editar' && !empty($dados['id_editar'])) {
            $sql = $conn->prepare("UPDATE eventos SET valor = ? WHERE id_evento = ?");
            $sql->bind_param('di', $dados['valor'], $dados['id_editar']);
            if ($sql->execute()) {
                echo json_encode(['status' => 'sucesso', 'msg' => 'Evento Editado!']);
            } else {
                echo json_encode(['status' => 'erro', 'msg' => 'Erro ao editar.']);
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
<title>Editar Holerite <?= $mes; ?></title>

<style>
body { font-family: Arial; padding: 25px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
td, th { border: 1px solid #444; padding: 8px; }
.titulo { background: #ddd; font-weight: bold; }
h1 { text-align: center; }
button {cursor: pointer;}
input[type="text"], input[type="email"] {
            width: 100%;
            box-sizing: border-box; /* Garante que o padding/border não aumente a largura total */
        }
</style>
</head>

<body>
<a href="gerar_folhas_todos.php?$mes=<?= $mes ?>">voltar</a>

<div id="holerite">

    <h1>EDITAR HOLERITE <?= date("m/Y", strtotime($mes_comp)); ?></h1>

    <table>
        <tr class="titulo"><td colspan="2">Empregador</td></tr>
        <tr><td>Nome:</td><td><?= isset($empresa["nome_fantasia"]) ? $empresa['nome_fantasia'] : 'Sem Nome'?></td></tr>
        <tr><td>Endereço:</td><td> <?= isset($empresa["uf"]) ? $empresa['uf'] : 'Sem endereço'?></td></tr>
        <tr><td>CNPJ:</td><td><?= isset($empresa["cnpj"]) ? $empresa['cnpj'] : 'Sem CNPJ'?></td></tr>

        <tr class="titulo"><td colspan="2">Funcionário</td></tr>
        <tr><td>Nome:</td><td><?= $user["nome_usuario"]; ?></td></tr>
        <tr><td>CPF:</td><td><?= $user["cpf_usuario"]; ?></td></tr>
        <tr><td>Cargo:</td><td><?= $user["nome_cargo"]; ?></td></tr>
        <tr>
            <td>Admissão:</td>
            <td><?= date("d/m/Y", strtotime($user["data_admissao"])); ?></td>
        </tr>

        <tr class="titulo"><td colspan="2">Proventos e Descontos</td></tr>

        <?php if (count($eventos) == 0): ?>
            <tr><td colspan="2">Nenhum evento cadastrado.</td></tr>
        <?php else: ?>
            <?php foreach ($eventos as $e): ?><?= $e['valor'] ?>
                    <tr>
                        <td><?= strtoupper($e["tipo"]) . " - " . $e["descricao"]; ?></td>
                        <td>R$ <input id="<?= $e["id_evento"] ?>" class="input-editar"
                        type="number" step="0.01" value="<?= $e["valor"] ?>"></input>
                        <button type="button" class="btn-deletar" data-id="<?= $e["id_evento"] ?>">deletar</button></td>
                    </tr>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <br>
    <div style="text-align:center;">Gerado automaticamente</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    //CÓDIGO DAVI ↓↓↓↓↓
    // deletar
    document.querySelectorAll('.btn-deletar').forEach(botao => {
        botao.addEventListener('click', function() {
            const idEvento = this.getAttribute('data-id');
            const resposta = confirm("Tem certeza que deseja deletar este evento?");

            if (resposta) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        confirmado: true, 
                        acao: 'deletar', 
                        id_evento: idEvento 
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'sucesso') location.reload(); // Recarrega para ver a mudança
                })
                .catch(err => console.error("Erro na requisição:", err));
            }  
        });
    });
    
    // editar
    document.querySelectorAll('.input-editar').forEach(input => {
        input.addEventListener('change', function(event){
            const idEditar = this.getAttribute('id');
            const valorNovo = event.target.value;
            fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        acao: 'editar',
                        valor: valorNovo,
                        id_editar : idEditar
                    })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.msg);
                    if(data.status === 'sucesso') location.reload(); // Recarrega para ver a mudança
                })
                .catch(err => console.error("Erro na requisição:", err));
        })
        
    });
    //CÓDIGO DAVI ↑↑↑↑↑

</script>

</body>
</html>