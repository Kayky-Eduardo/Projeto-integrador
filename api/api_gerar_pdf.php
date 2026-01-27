<?php
// Conexão com banco de dados
require_once "../BD/conexao.php";
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

// 7.2 DESCONTO AUTOMÁTICO POR FALTA DE HORAS (MÊS)

// 1. CALCULA OS MINUTOS TRABALHADOS NO MÊS
// Prepara a query que soma os minutos trabalhados
// (diferença entre início e fim do ponto, menos as pausas)
$sql_trabalhado = $conn->prepare("
    SELECT 
        SUM(
            TIMESTAMPDIFF(MINUTE, p.inicio_ponto, p.fim_ponto)
            - IFNULL(pa.duracao_minutos, 0)
        ) AS minutos
    FROM ponto_dia p
    LEFT JOIN pausa pa 
        ON pa.id_usuario = p.id_usuario
        AND pa.data = p.data_ponto
    WHERE p.id_usuario = ?                 -- Usuário específico
      AND p.fim_ponto IS NOT NULL          -- Apenas pontos finalizados
      AND DATE_FORMAT(p.data_ponto, '%Y-%m') = DATE_FORMAT(?, '%Y-%m') -- Mês/Ano
");

// Associa os parâmetros
$sql_trabalhado->bind_param("is", $id_usuario, $mes_comp);

// Executa a query
$sql_trabalhado->execute();

// Recupera o total de minutos trabalhados no mês
$minutos_trabalhados = (int) (
    $sql_trabalhado->get_result()->fetch_assoc()['minutos'] ?? 0
);

// 2. CALCULA OS MINUTOS ESPERADOS NO MÊS
// Busca a jornada diária (em minutos) e a quantidade de dias trabalhados
$sql_jornada = $conn->prepare("
    SELECT 
        TIME_TO_SEC(t.jornada) / 60 AS minutos_dia, -- Jornada diária em minutos
        COUNT(DISTINCT p.data_ponto) AS dias        -- Quantidade de dias no mês
    FROM ponto_dia p
    JOIN grupo_setor gs ON gs.id_usuario = p.id_usuario
    JOIN setor s ON s.id_setor = gs.id_setor
    JOIN tempo_jornada t ON t.id_tempo = s.id_tempo
    WHERE p.id_usuario = ?
      AND DATE_FORMAT(p.data_ponto, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
");

// Associa os parâmetros
$sql_jornada->bind_param("is", $id_usuario, $mes_comp);

// Executa a query
$sql_jornada->execute();

// Resultado da jornada
$res_jornada = $sql_jornada->get_result()->fetch_assoc();

// Calcula o total de minutos esperados no mês
$minutos_esperados =
    ((int)$res_jornada['minutos_dia']) *
    ((int)$res_jornada['dias']);

// 3. CALCULA A DIFERENÇA DE MINUTOS
// Diferença entre o que trabalhou e o que deveria trabalhar
$diferenca_minutos = $minutos_trabalhados - $minutos_esperados;

// 4. CALCULA O VALOR DO MINUTO
// Calcula o valor da hora (salário dividido por 220 horas)
$valor_hora = $user['salario_bruto'] / 220;

// Calcula o valor do minuto
$valor_minuto = $valor_hora / 60;

// 5. CALCULA O DESCONTO POR FALTA
// Inicializa o desconto
$valor_desconto_falta = 0;

// Se a diferença for negativa, houve falta
if ($diferenca_minutos < 0) {
    // Converte os minutos faltantes em valor monetário
    $valor_desconto_falta = abs($diferenca_minutos) * $valor_minuto;
}

// 6. CRIA OU ATUALIZA O EVENTO DE DESCONTO
// Descrição padrão do evento
$descricao_evento = "Desconto por falta";

// Verifica se já existe evento de desconto para o mês
$sql_evento = $conn->prepare("
    SELECT id_evento 
    FROM eventos 
    WHERE id_usuario = ? 
      AND mes_competencia = ?
      AND descricao = ?
");

// Associa os parâmetros
$sql_evento->bind_param("iss", $id_usuario, $mes_comp, $descricao_evento);

// Executa a query
$sql_evento->execute();

// Busca o evento existente (se houver)
$evento = $sql_evento->get_result()->fetch_assoc();

// Só cria ou atualiza se houver desconto
if ($valor_desconto_falta > 0) {

    // Se o evento já existe, atualiza o valor
    if ($evento) {

        $sql_upd = $conn->prepare("
            UPDATE eventos 
            SET valor = ?
            WHERE id_evento = ?
        ");

        $sql_upd->bind_param("di", $valor_desconto_falta, $evento['id_evento']);
        $sql_upd->execute();

    } 
    // Se não existe, cria um novo evento de desconto
    else {

        $sql_ins = $conn->prepare("
            INSERT INTO eventos 
            (id_usuario, tipo, descricao, valor, mes_competencia)
            VALUES (?, 'desconto', ?, ?, ?)
        ");

        $sql_ins->bind_param(
            "isds",
            $id_usuario,
            $descricao_evento,
            $valor_desconto_falta,
            $mes_comp
        );

        $sql_ins->execute();
    }
}

// 7. ATUALIZA OS TOTAIS DA FOLHA
// Soma todos os proventos e descontos do mês
$sql_totais = $conn->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'provento' THEN valor ELSE 0 END) AS proventos,
        SUM(CASE WHEN tipo = 'desconto' THEN valor ELSE 0 END) AS descontos
    FROM eventos
    WHERE id_usuario = ? 
      AND mes_competencia = ?
");

// Associa os parâmetros
$sql_totais->bind_param("is", $id_usuario, $mes_comp);

// Executa a query
$sql_totais->execute();

// Busca os totais
$totais = $sql_totais->get_result()->fetch_assoc();

// Define os totais (evita null)
$total_proventos = $totais['proventos'] ?? 0;
$total_descontos = $totais['descontos'] ?? 0;

// Calcula o salário líquido
$salario_liquido =
    $folha['salario_bruto'] +
    $total_proventos -
    $total_descontos;

// Atualiza a folha de pagamento
$sql_folha_upd = $conn->prepare("
    UPDATE folhas 
    SET total_proventos = ?, 
        total_descontos = ?, 
        salario_liquido = ?
    WHERE id_folha = ?
");

// Associa os valores
$sql_folha_upd->bind_param(
    "dddi",
    $total_proventos,
    $total_descontos,
    $salario_liquido,
    $folha['id_folha']
);

// Executa a atualização
$sql_folha_upd->execute();


// 8. Eventos

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
<title>Holerite <?php echo $mes; ?></title>
<!--Isso so ta aqui pq sem css fica muito feio a folha de pagamento (pode arrancar daqui depois Bruno✌)-->
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

<button onclick="gerarPDF()">📄 Baixar PDF</button>

<div id="holerite">

<h1>HOLERITE <?php echo date("m/Y", strtotime($mes_comp)); ?></h1>

<table>
    <tr class="titulo"><td colspan="2">Empresa</td></tr>
    <tr><td>Nome:</td><td>Sem nome</td></tr>

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
    <tr><td>VT:</td><td>R$ <?php echo number_format($folha["vt"],2,',','.'); ?></td></tr>
    <tr><td>INSS:</td><td>R$ <?php echo number_format($folha["inss"],2,',','.'); ?></td></tr>
    <tr><td>IRRF:</td><td>R$ <?php echo number_format($folha["irrf"],2,',','.'); ?></td></tr>
    <tr><td>Total Proventos:</td><td>R$ <?php echo number_format($folha["total_proventos"],2,',','.'); ?></td></tr>
    <tr><td>Total Descontos:</td><td>R$ <?php echo number_format($folha["total_descontos"],2,',','.'); ?></td></tr>
    <tr class="titulo">
        <td><b>Salário Líquido</b></td>
        <td><b>R$ <?php echo number_format($folha["salario_liquido"],2,',','.'); ?></b></td>
    </tr>
</table>

<br>
<div style="text-align:center;">Gerado automaticamente</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>

// Recebe o nome do funcionário vindo do PHP e adiciona barras de escape para evitar problemas com aspas
const nomeFuncionario = "<?php echo addslashes($user['nome_usuario']); ?>";

// Recebe o mês/ano da competência (ex: "01-2026") vindo do PHP
const mesCompetencia  = "<?php echo date('m-Y', strtotime($mes_comp)); ?>";

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