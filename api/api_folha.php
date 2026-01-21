<?php
session_start();

// Importa funções de cálculo (INSS, FGTS, IRRF etc.) e conexão com o banco
require_once '../include/funcoes/funcoes_calculo.php';
require_once(__DIR__ . '/../BD/conexao.php');


// -----------------------------
// 1. Verifica se o usuário está logado
// -----------------------------
if (!isset($_SESSION["id_usuario"])) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "Usuário não está logado"]);
    exit;
}

$id_usuario = $_SESSION["id_usuario"];

// -----------------------------
// 2. Recebe o mês (competência)
// -----------------------------
$input = json_decode(file_get_contents("php://input"), true);
$mes_competencia = $input["mes"] ?? null;

// Validação básica
if (!$mes_competencia) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "O campo 'mes' é obrigatório (formato YYYY-MM)"]);
    exit;
}

// Converte para formato válido do BD (primeiro dia do mês)
$mes_competencia_padrao = $mes_competencia . "-01";

// -----------------------------
// 3. Buscar dados do usuário (nome + salário base/bruto)
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

// Caso o ID exista na sessão mas não no banco
if (!$usuario) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "Usuário não encontrado"]);
    exit;
}

// Guarda dados
$salario_bruto = floatval($usuario["salario_bruto"]);
$nome_usuario  = $usuario["nome_usuario"];

// -----------------------------
// 4. Buscar proventos e descontos adicionais cadastrados no mês
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

// Soma tudo dinamicamente baseado no tipo
while ($evt = $resEventos->fetch_assoc()) {
    if ($evt["tipo"] === "provento") {
        $total_proventos += floatval($evt["valor"]);
    } else {
        $total_descontos += floatval($evt["valor"]);
    }
}

// -----------------------------
// 5. Cálculos legais (INSS, FGTS, IRRF, VT)
// -----------------------------
$fgts = calcularFGTS($salario_bruto);
$inss = calcularINSS($salario_bruto);
$irrf = calcularIRRF($salario_bruto, $inss, 0); // sem dependentes
$vt = calcularVT($salario_bruto, 300); // valor fixo passado como exemplo

// Soma descontos obrigatórios aos descontos cadastrados pelo usuário
$total_descontos += $inss + $irrf + $vt;

// Calcula salário líquido
$salario_liquido = calcularSalarioLiquido(
    $salario_bruto,
    $total_proventos,
    $total_descontos
);

// -----------------------------
// 6. Insere ou atualiza a folha no banco
// -----------------------------
// - INSERT padrão
// - Caso já exista uma folha desse usuário nesse mês,
//   UPDATE automático usando ON DUPLICATE KEY
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
// 7. Converte o mês para texto (Ex.: 2025-01 → Janeiro de 2025)
// -----------------------------
$meses_pt = [
    "01" => "Janeiro","02" => "Fevereiro","03" => "Março","04" => "Abril",
    "05" => "Maio","06" => "Junho","07" => "Julho","08" => "Agosto",
    "09" => "Setembro","10" => "Outubro","11" => "Novembro","12" => "Dezembro"
];

$ano = substr($mes_competencia, 0, 4);
$mes_num = substr($mes_competencia, 5, 2);

$mes_formatado = $meses_pt[$mes_num] . " de " . $ano;

?>