<?php
// Conexão com banco de dados
require_once "../../BD/conexao.php";
require_once "../../include/funcoes/calculo_desconto_falta.php";
session_start();

// -----------------------------
// 1. Recebe o mês (competência)
// -----------------------------
$mes = $_GET["mes"] ?? null;
if (!$mes) {
    // No redirecionamento
    header("Location: gerar_folhas_todos.php?erro=Mês inacessível.");
    exit();
}

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
if ($id_usuario != $id_usuario_logado && $nivel_logado < $nivel_alvo){
    // No redirecionamento
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
        <div>
            <h2>Holerite não encontrado</h2>
            <p>
                Não existe folha de pagamento para o mês<br>
                <b><?php echo date("m/Y", strtotime($mes_comp)); ?></b>.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

//função e executa o cálculo e atualização do desconto
$desconto = calcularEAplicarDescontoFalta($conn, $id_usuario, $mes_comp, $user, $folha);

// -----------------------------
// 8. Eventos
// -----------------------------
$sql_eventos = $conn->prepare("
    SELECT tipo, descricao, valor 
    FROM eventos 
    WHERE id_usuario = ? AND mes_competencia = ?
");
$sql_eventos->bind_param("is", $id_usuario, $mes_comp);
$sql_eventos->execute();
$eventos = $sql_eventos->get_result()->fetch_all(MYSQLI_ASSOC);

// CODIGUINHO DO DABI ↓
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    $dados = json_decode(file_get_contents('php://input'), true);

    if($dados){
        $acao = $dados['acao'] ?? '';

        if (isset($dados['id_usuario'])) {
            if($acao == 'revisar'){
                $id_usuario = $dados['id_usuario'] ?? '';

                $sql= $conn->prepare("UPDATE folhas SET revisado = 1 WHERE id_usuario = ? AND revisado = 0");
                    $sql->bind_param('i', $id_usuario);
                    if ($sql->execute()) {
                        echo json_encode(['status' => 'sucesso', 'msg' => 'Folha Revisada! ']);
                    } else {
                        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao revisar.']);
                    }
                    exit;
            }
        }
    }
}
// CODIGUINHO DO DABI ↑

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Holerite <?php echo $mes; ?></title>

<style>
body { font-family: Arial; padding: 25px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
td, th { border: 1px solid #444; padding: 8px; }
.titulo { background: #ddd; font-weight: bold; }
h1 { text-align: center; }
button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
</style>
</head>

<body>

<div id="holerite">
<a href="gerar_folhas_todos.php?$mes=<?= $mes ?>">voltar</a>
<h1>HOLERITE <?php echo date("m/Y", strtotime($mes_comp)); ?></h1>

<table>
    <tr class="titulo"><td colspan="2">Empresa</td></tr>
    <tr><td>Nome:</td><td>Sem nome</td></tr>
    <tr><td>Endereço:</td><td>Sem endereço</td></tr>
    <tr><td>CNPJ:</td><td>Sem CNPJ</td></tr>

    <tr class="titulo"><td colspan="2">Funcionário</td></tr>
    <tr><td>Nome:</td><td><?php echo $user["nome_usuario"]; ?></td></tr>
    <tr><td>CPF:</td><td><?php echo $user["cpf_usuario"]; ?></td></tr>
    <tr><td>Cargo:</td><td><?php echo $user["nome_cargo"]; ?></td></tr>
    <tr>
        <td>Admissão:</td>
        <td><?php echo date("d/m/Y", strtotime($user["data_admissao"])); ?></td>
    </tr>

    <tr class="titulo"><td colspan="2">Proventos e Descontos</td></tr>

    <?php if (count($eventos) == 0): ?>
        <tr><td colspan="2">Nenhum evento cadastrado.</td></tr>
    <?php else: ?>
        <?php foreach ($eventos as $e): ?>
            <tr>
                <td><?php echo strtoupper($e["tipo"]) . " - " . $e["descricao"]; ?></td>
                <td>R$ <?php echo number_format($e["valor"], 2, ',', '.'); ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>

    <tr class="titulo"><td colspan="2">Resumo</td></tr>
    <tr><td>Salário Bruto:</td><td>R$ <?php echo number_format($folha["salario_bruto"],2,',','.'); ?></td></tr>
    <tr><td>Total Proventos:</td><td>R$ <?php echo number_format($folha["total_proventos"],2,',','.'); ?></td></tr>
    <tr><td>Total Descontos:</td><td>R$ <?php echo number_format($folha["total_descontos"],2,',','.'); ?></td></tr>
    <tr><td>VT:</td><td>R$ <?php echo number_format($folha["vt"],2,',','.'); ?></td></tr>
    <tr><td>INSS:</td><td>R$ <?php echo number_format($folha["inss"],2,',','.'); ?></td></tr>
    <tr><td>IRRF:</td><td>R$ <?php echo number_format($folha["irrf"],2,',','.'); ?></td></tr>
    <tr class="titulo">
        <td><b>Salário Líquido</b></td>
        <td><b>R$ <?php echo number_format($folha["salario_liquido"],2,',','.'); ?></b></td>
    </tr>
</table>

<br>
<button class="btn-salvar" id="<?= $id_usuario ?>">Marcar como Revisado</button>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    //CÓDIGO DAVI ↓↓↓↓↓
    
    // alterar para revisado
    btnRevisar = document.querySelector('.btn-salvar');
    btnRevisar.addEventListener('click', function(){
        let resposta = confirm('Tem certeza que deseja marcar como revisado? Sua folha não poderá ser modificada depois');
        const idUsuario = this.getAttribute('id');
        if (resposta){
            fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        acao: 'revisar',
                        id_usuario: idUsuario
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
    window.onload = function () {
        // Se não marcou como "acabou de recarregar"
        if (!sessionStorage.getItem("justReloaded")) {
            // Marca que acabou de recarregar
            sessionStorage.setItem("justReloaded", "true");
            // Recarrega a página
            location.reload();
        } else {
            // Limpa a marca para a próxima vez que entrar na página
            sessionStorage.removeItem("justReloaded");
        }
    };

</script>

</body>
</html>