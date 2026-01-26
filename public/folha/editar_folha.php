<?php
// Conexão com banco de dados
require_once "../../BD/conexao.php";
session_start();

// -----------------------------
// 1. Recebe o mês (competência)
// -----------------------------
$mes = $_GET["mes"] ?? null;
if (!$mes) { die("Mês não informado."); }

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
if ($id_usuario != $id_usuario_logado && $nivel_logado <= $nivel_alvo) {
    die("Acesso negado.");
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
    SELECT tipo, descricao, valor 
    FROM eventos 
    WHERE id_usuario = ? AND mes_competencia = ?
");
$sql_eventos->bind_param("is", $id_usuario, $mes_comp);
$sql_eventos->execute();
$eventos = $sql_eventos->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Holerite <?= $mes; ?></title>

<style>
body { font-family: Arial; padding: 25px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
td, th { border: 1px solid #444; padding: 8px; }
.titulo { background: #ddd; font-weight: bold; }
h1 { text-align: center; }
button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
input[type="text"], input[type="email"] {
            width: 100%;
            box-sizing: border-box; /* Garante que o padding/border não aumente a largura total */
        }
</style>
</head>

<body>

<button onclick="gerarPDF()">📄 Baixar PDF</button>
<a href="../">voltar</a>

<div id="holerite">

    <h1>HOLERITE <?= date("m/Y", strtotime($mes_comp)); ?></h1>

    <table>
        <tr class="titulo"><td colspan="2">Empresa</td></tr>
        <tr><td>Nome:</td><td>Sem nome</td></tr>

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
            <?php foreach ($eventos as $e): ?>
                <tr>
                    <td><?= strtoupper($e["tipo"]) . " - " . $e["descricao"]; ?></td>
                    <td>R$ <input value="<?= number_format($e["valor"], 2, ',', '.'); ?>"></input></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <tr class="titulo"><td colspan="2">Resumo</td></tr>
        <tr><td>Salário Bruto:</td><td>R$<input value=" <?= number_format($folha["salario_bruto"],2,',','.'); ?>"></input></td></tr>
        <tr><td>Total Proventos:</td><td>R$<input value=" <?= number_format($folha["total_proventos"],2,',','.'); ?>"></input></td></tr>
        <tr><td>Total Descontos:</td><td>R$<input value=" <?= number_format($folha["total_descontos"],2,',','.'); ?>"></input></td></tr>
        <tr><td>VT:</td><td>R$<input value=" <?= number_format($folha["vt"],2,',','.'); ?>"></input></td></tr>
        <tr><td>INSS:</td><td>R$<input value=" <?= number_format($folha["inss"],2,',','.'); ?>"></input></td></tr>
        <tr><td>IRRF:</td><td>R$<input value=" <?= number_format($folha["irrf"],2,',','.'); ?>"></input></td></tr>
        <tr class="titulo">
            <td><b>Salário Líquido</b></td>
            <td><b>R$ <?= number_format($folha["salario_liquido"],2,',','.'); ?></b></td>
        </tr>
    </table>

    <br>
    <div style="text-align:center;">Gerado automaticamente</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>

// Recebe o nome do funcionário vindo do PHP e adiciona barras de escape para evitar problemas com aspas
const nomeFuncionario = "<?= addslashes($user['nome_usuario']); ?>";

// Recebe o mês/ano da competência (ex: "01-2026") vindo do PHP
const mesCompetencia  = "<?= date('m-Y', strtotime($mes_comp)); ?>";

async function gerarPDF() {

    // Importa o construtor jsPDF do objeto global window.jspdf
    const { jsPDF } = window.jspdf;

    // Seleciona o elemento HTML que contém o holerite
    const element = document.getElementById("holerite");

    // Converte o elemento HTML em um canvas usando html2canvas
    // scale: 2 aumenta a resolução da imagem gerada
    const canvas = await html2canvas(element, { scale: 2 });

    // Converte o canvas em uma imagem no formato PNG (base64)
    const imgData = canvas.toDataURL("image/png");

    // Cria um novo documento PDF
    // "p" = orientação retrato (portrait)
    // "mm" = unidade de medida em milímetros
    // "a4" = tamanho da página
    const pdf = new jsPDF("p", "mm", "a4");

    // Obtém a largura da página do PDF
    const pageWidth = pdf.internal.pageSize.getWidth();

    // Calcula a altura da imagem mantendo a proporção original
    const imgHeight = (canvas.height * pageWidth) / canvas.width;

    // Adiciona a imagem gerada ao PDF
    // Parâmetros: imagem, formato, posição X, posição Y, largura, altura
    pdf.addImage(imgData, "PNG", 0, 0, pageWidth, imgHeight);

    // Remove acentos e normaliza o nome do funcionário
    // Também substitui espaços por "_" para evitar problemas no nome do arquivo
    const nomeLimpo = nomeFuncionario
        .normalize("NFD")              // Normaliza caracteres com acentos
        .replace(/[\u0300-\u036f]/g, "") // Remove os acentos
        .replace(/\s+/g, "_");           // Substitui espaços por "_"

    // Monta o nome final do arquivo PDF
    // Exemplo: holerite_Joao_Silva_01-2026.pdf
    const nomeArquivo = `holerite_${nomeLimpo}_${mesCompetencia}.pdf`;

    // Salva o PDF com o nome definido
    pdf.save(nomeArquivo);
}

</script>

</body>
</html>