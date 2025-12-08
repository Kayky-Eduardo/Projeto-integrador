<?php
// Conexão com banco de dados
require_once "../BD/conexao.php";

session_start();

// -----------------------------
// 1. Recebe o mês (competência)
// -----------------------------
$mes = $_GET["mes"] ?? null;
if (!$mes) { die("Mês não informado."); }

// Formato padrão da tabela (YYYY-MM-01)
$mes_comp = $mes . "-01";

// Pega o id do usuário logado
$id_usuario = $_SESSION['id_usuario'];

// -----------------------------
// 2. Buscar dados do usuário
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
// 3. Buscar folha gerada no mês
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
// 4. Buscar eventos (proventos/descontos)
// -----------------------------
$sql_eventos = $conn->prepare("
    SELECT tipo, descricao, valor 
    FROM eventos 
    WHERE id_usuario = ? AND mes_competencia = ?
");
$sql_eventos->bind_param("is", $id_usuario, $mes_comp);
$sql_eventos->execute();

// Retorna lista completa de eventos
$eventos = $sql_eventos->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Holerite <?php echo $mes; ?></title>

<style>
/* Deixei aqui pra nn ficar tão feio, o bruno pode arrancar daqui quando for mexer no css */
body { font-family: Arial; padding: 25px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
td, th { border: 1px solid #444; padding: 8px; }
.titulo { background: #ddd; font-weight: bold; }
h1 { text-align: center; }
</style>

</head>
<body>

<!-- Mês da folha de pagamento -->
<h1>HOLERITE – <?php echo date("m/Y", strtotime($mes_comp)); ?></h1>

<table> 

    <!-- Aqui vai ficar o nome da empresa, mas como a gente nao tem deixei um valor aleatorio -->
    <tr class="titulo"><td colspan="2">Empresa</td></tr>
    <tr><td>Nome:</td><td>Sem nome</td></tr>

    <!-- Dados do funcionário -->
    <tr class="titulo"><td colspan="2">Funcionário</td></tr>
    <tr><td>Nome:</td><td><?php echo $user["nome_usuario"]; ?></td></tr>
    <tr><td>CPF:</td><td><?php echo $user["cpf_usuario"]; ?></td></tr>
    <tr><td>Cargo:</td><td><?php echo $user["nome_cargo"]; ?></td></tr>
    <tr><td>Admissão:</td>
        <td><?php echo date("d/m/Y", strtotime($user["data_admissao"])); ?></td></tr>

    <!-- Pega os proventos/descontos -->
    <tr class="titulo"><td colspan="2">Proventos e Descontos</td></tr>

<?php if (count($eventos) == 0): ?>
    <!-- Se não tem eventos -->
    <tr><td colspan="2">Nenhum evento cadastrado.</td></tr>

<?php else: ?>
    <!-- Lista cada evento cadastrado -->
    <?php foreach ($eventos as $e): ?>
        <tr>
            <td><?php echo strtoupper($e["tipo"]) . " – " . $e["descricao"]; ?></td>
            <td>R$ <?php echo number_format($e["valor"], 2, ',', '.'); ?></td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>

    <!-- Resumo financeiro da folha -->
    <tr class="titulo"><td colspan="2">Resumo</td></tr>

    <tr><td>Salário Bruto:</td>
        <td>R$ <?php echo $folha["salario_bruto"]; ?></td></tr>

    <tr><td>Total Proventos:</td>
        <td>R$ <?php echo $folha["total_proventos"]; ?></td></tr>

    <tr><td>Total Descontos:</td>
        <td>R$ <?php echo $folha["total_descontos"]; ?></td></tr>

    <tr><td>INSS:</td>
        <td>R$ <?php echo $folha["inss"]; ?></td></tr>

    <tr><td>FGTS:</td>
        <td>R$ <?php echo $folha["fgts"]; ?></td></tr>

    <tr><td>IRRF:</td>
        <td>R$ <?php echo $folha["irrf"]; ?></td></tr>

    <!-- Salário líquido destacado -->
    <tr class="titulo">
        <td><b>Salário Líquido</b></td>
        <td><b>R$ <?php echo $folha["salario_liquido"]; ?></b></td>
    </tr>

</table>

<br><br>

<!-- Rodapé simples -->
<div style="text-align:center;">Gerado automaticamente</div>

</body>
</html>
