<?php
session_start();

// Importa funções de cálculo e conexão com BD
require_once(__DIR__ . '/../services/funcoes_calculo.php');
require_once(__DIR__ . '/../BD/conexao.php');


// -----------------------------
// 1. Verifica se o usuário está logado
// -----------------------------
if (!isset($_SESSION["id_usuario"])) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "Usuário não está logado"]);
    exit;
}

// ID do usuário autenticado
$id_usuario = $_SESSION["id_usuario"];


// -----------------------------
// 2. Recebe o mês (competência)
// -----------------------------
$input = json_decode(file_get_contents("php://input"), true);
$mes_competencia = $input["mes"] ?? null;

// Se não vier o mês, retorna erro
if (!$mes_competencia) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "O campo 'mes' é obrigatório (formato YYYY-MM)"]);
    exit;
}

// Converte para formato completo YYYY-MM-01
$mes_competencia_padrao = $mes_competencia . "-01";


// -----------------------------
// 3. Buscar dados do usuário (nome + salário)
// -----------------------------
$stmt = $conn->prepare("
    SELECT u.nome_usuario, c.salario_bruto 
    FROM usuario u
    JOIN cargo c ON c.id_cargo = u.id_cargo
    WHERE u.id_usuario = ?
");

$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Caso usuário não exista
if (!$usuario) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "Usuário não encontrado"]);
    exit;
}

// Salva dados encontrados
$salario_bruto = floatval($usuario["salario_bruto"]);
$nome_usuario  = $usuario["nome_usuario"];


// -----------------------------
// 4. Buscar eventos (proventos e descontos)
// -----------------------------
$stmt2 = $conn->prepare("
    SELECT tipo, valor 
    FROM eventos 
    WHERE id_usuario = ? AND mes_competencia = ?
");
$stmt2->bind_param("is", $id_usuario, $mes_competencia_padrao);
$stmt2->execute();
$resEventos = $stmt2->get_result();

$total_proventos = 0;
$total_descontos = 0;

// Soma todos os eventos do mês
while ($evt = $resEventos->fetch_assoc()) {
    if ($evt["tipo"] === "provento") {
        $total_proventos += floatval($evt["valor"]);
    } else {
        $total_descontos += floatval($evt["valor"]);
    }
}


// -----------------------------
// 5. Executa cálculos principais
// -----------------------------
$fgts = calcularFGTS($salario_bruto);
$inss = calcularINSS($salario_bruto);
$irrf = calcularIRRF($salario_bruto, $inss, 0); // sem dependentes
$vt = calcularVT($salario_bruto, 300); // valor fixo de transporte
 
// Soma descontos legais aos descontos informados pelo usuário
$total_descontos += $inss + $irrf + $vt;

// Salário líquido final
$salario_liquido = calcularSalarioLiquido(
    $salario_bruto,
    $total_proventos,
    $total_descontos
);


// -----------------------------
// 6. Inserir ou Atualizar folha no BD
// -----------------------------
$stmt3 = $conn->prepare("
    INSERT INTO folhas 
    (id_usuario, mes_competencia, salario_bruto, total_proventos, total_descontos, fgts, inss, irrf, vt, salario_liquido)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        total_proventos = VALUES(total_proventos),
        total_descontos = VALUES(total_descontos),
        fgts = VALUES(fgts),
        inss = VALUES(inss),
        irrf = VALUES(irrf),
        vt = VALUES(vt),
        salario_liquido = VALUES(salario_liquido)
");

// Preenche os parâmetros da query
$stmt3->bind_param(
    "issddddddd",
    $id_usuario,
    $mes_competencia_padrao,
    $salario_bruto,
    $total_proventos,
    $total_descontos,
    $fgts,
    $inss,
    $irrf,
    $vt,
    $salario_liquido
);

$stmt3->execute();


// -----------------------------
// 7. Formatar mês para texto
// -----------------------------
$meses_pt = [
    "01" => "Janeiro",
    "02" => "Fevereiro",
    "03" => "Março",
    "04" => "Abril",
    "05" => "Maio",
    "06" => "Junho",
    "07" => "Julho",
    "08" => "Agosto",
    "09" => "Setembro",
    "10" => "Outubro",
    "11" => "Novembro",
    "12" => "Dezembro"
];

$ano = substr($mes_competencia, 0, 4);
$mes_num = substr($mes_competencia, 5, 2);

// Ex.: Janeiro de 2025
$mes_formatado = $meses_pt[$mes_num] . " de " . $ano;


// -----------------------------
// 8. Montar texto do holerite
// -----------------------------
$texto = 
"Folha de Pagamento — {$mes_formatado}
--------------------------------
Colaborador: {$nome_usuario}
Salário bruto: R$ " . number_format($salario_bruto, 2, ',', '.') . "
Proventos: R$ " . number_format($total_proventos, 2, ',', '.') . "
Descontos totais: R$ " . number_format($total_descontos, 2, ',', '.') . "
   • INSS: R$ " . number_format($inss, 2, ',', '.') . "
   • IRRF: R$ " . number_format($irrf, 2, ',', '.') . "
   • VT: R$ " . number_format($vt, 2, ',', '.') . "
FGTS: R$ " . number_format($fgts, 2, ',', '.') . "
➡ Salário Líquido: R$ " . number_format($salario_liquido, 2, ',', '.');

// -----------------------------
// 9. Retorna texto puro ao frontend
// -----------------------------
header("Content-Type: text/plain; charset=utf-8");
echo $texto;
exit;

?>
