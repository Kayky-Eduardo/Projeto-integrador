<?php
// 1. CONFIGURAÇÃO INICIAL
require_once "../BD/conexao.php";
require_once "../include/funcoes/calculo_desconto_falta.php";
session_start();

// 2. ENTRADAS
$mes = $_GET["mes"] ?? null;

if (!$mes) {
    die("Mês não informado.");
}

$mes_comp = $mes . "-01";
$id_usuario_logado = $_SESSION['id_usuario'];
$id_usuario = $_GET['id_usuario'] ?? $id_usuario_logado;

// 3. NÍVEL USUÁRIO LOGADO
$sql_nivel_logado = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");

$sql_nivel_logado->bind_param("i", $id_usuario_logado);
$sql_nivel_logado->execute();
$nivel_logado = $sql_nivel_logado->get_result()->fetch_assoc()['nivel'] ?? 0;

// 4. NÍVEL USUÁRIO ALVO
$sql_nivel_alvo = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");

$sql_nivel_alvo->bind_param("i", $id_usuario);
$sql_nivel_alvo->execute();
$nivel_alvo = $sql_nivel_alvo->get_result()->fetch_assoc()['nivel'] ?? 0;

// 5. PERMISSÃO
if ($id_usuario != $id_usuario_logado && $nivel_logado <= $nivel_alvo) {
    die("Acesso negado.");
}

// 6. DADOS DO USUÁRIO
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

// 7. FOLHA
$sql_folha = $conn->prepare("
    SELECT *
    FROM folhas
    WHERE id_usuario = ? AND mes_competencia = ?
");

$sql_folha->bind_param("is", $id_usuario, $mes_comp);
$sql_folha->execute();
$folha = $sql_folha->get_result()->fetch_assoc();

// 7.1 VERIFICA EXISTÊNCIA
$erro = null;

if (!$folha) {
    $erro = "Holerite não encontrado";
} else {
    $desconto = calcularEAplicarDescontoFalta(
        $conn,
        $id_usuario,
        $mes_comp,
        $user,
        $folha
    );

    $sql_folha->execute();
    $folha = $sql_folha->get_result()->fetch_assoc();

    // 8. EVENTOS
    $sql_eventos = $conn->prepare("
        SELECT tipo, descricao, valor 
        FROM eventos 
        WHERE id_usuario = ? AND mes_competencia = ?
    ");

    $sql_eventos->bind_param("is", $id_usuario, $mes_comp);
    $sql_eventos->execute();
    $eventos = $sql_eventos->get_result()->fetch_all(MYSQLI_ASSOC);
}

$meses = [
    "01" => "janeiro",
    "02" => "fevereiro",
    "03" => "março",
    "04" => "abril",
    "05" => "maio",
    "06" => "junho",
    "07" => "julho",
    "08" => "agosto",
    "09" => "setembro",
    "10" => "outubro",
    "11" => "novembro",
    "12" => "dezembro"
];

$mes_num = date("m", strtotime($mes_comp));
$ano = date("Y", strtotime($mes_comp));

$mes_formatado = ucfirst($meses[$mes_num]) . " de " . $ano;

$sql_empresa = $conn->query("SELECT * FROM empresas LIMIT 1");
$empresa = $sql_empresa->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Holerite <?php echo $mes; ?></title>
    <link rel="stylesheet" href="../assets/css/estilo.css">

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            padding: 25px;
        }

        td,
        th {
            border: 1px solid #444;
            padding: 8px;
        }

        .titulo {
            background: #ddd;
            font-weight: bold;
        }

        h1 {
            text-align: center;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <nav aria-label="Menu principal">
        <?php include("../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <?php if ($erro): ?>
                <article>
                    <h2>Holerite não encontrado</h2>
                    <p>
                        Não existe folha para o mês<br>
                        <b><?php echo date("m/Y", strtotime($mes_comp)); ?></b>
                    </p>
                </article>
            <?php else: ?>
                <h1 class="page-title">
                    Holerite - <?= $mes_formatado; ?>
                </h1>

                <article id="holerite">
                    <table>
                        <tr class="titulo">
                            <td colspan="2">Empresa</td>
                        </tr>

                        <tr>
                            <td>Nome:</td>
                            <td><?= $empresa["nome_fantasia"] ?? 'Sem Nome' ?></td>
                        </tr>

                        <tr>
                            <td>Endereço:</td>

                            <td>
                                <?= isset($empresa["uf"])
                                    ? "{$empresa["uf"]} - {$empresa["cidade"]} - {$empresa["bairro"]} - {$empresa["numero"]}"
                                    : 'Sem endereço' ?>
                            </td>
                        </tr>

                        <tr>
                            <td>CNPJ:</td>
                            <td><?= $empresa["cnpj"] ?? 'Sem CNPJ' ?></td>
                        </tr>

                        <tr class="titulo">
                            <td colspan="2">Funcionário</td>
                        </tr>

                        <tr>
                            <td>Nome:</td>
                            <td><?php echo $user["nome_usuario"]; ?></td>
                        </tr>

                        <tr>
                            <td>CPF:</td>
                            <td><?php echo $user["cpf_usuario"]; ?></td>
                        </tr>

                        <tr>
                            <td>Cargo:</td>
                            <td><?php echo $user["nome_cargo"]; ?></td>
                        </tr>

                        <tr>
                            <td>Admissão:</td>
                            <td><?php echo date("d/m/Y", strtotime($user["data_admissao"])); ?></td>
                        </tr>

                        <tr class="titulo">
                            <td colspan="2">Proventos e Descontos</td>
                        </tr>

                        <?php if (count($eventos) == 0): ?>
                            <tr>
                                <td colspan="2">Nenhum evento cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventos as $e): ?>
                                <tr>
                                    <td><?php echo strtoupper($e["tipo"]) . " - " . $e["descricao"]; ?></td>
                                    <td>R$ <?php echo number_format($e["valor"], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <tr class="titulo">
                            <td colspan="2">Resumo</td>
                        </tr>

                        <tr>
                            <td>Salário Bruto:</td>
                            <td>R$ <?php echo number_format($folha["salario_bruto"], 2, ',', '.'); ?></td>
                        </tr>

                        <tr>
                            <td>VT:</td>
                            <td>R$ <?php echo number_format($folha["vt"], 2, ',', '.'); ?></td>
                        </tr>

                        <tr>
                            <td>INSS:</td>
                            <td>R$ <?php echo number_format($folha["inss"], 2, ',', '.'); ?></td>
                        </tr>

                        <tr>
                            <td>IRRF:</td>
                            <td>R$ <?php echo number_format($folha["irrf"], 2, ',', '.'); ?></td>
                        </tr>

                        <tr>
                            <td>Total Proventos:</td>
                            <td>R$ <?php echo number_format($folha["total_proventos"], 2, ',', '.'); ?></td>
                        </tr>

                        <tr>
                            <td>Total Descontos:</td>
                            <td>R$ <?php echo number_format($folha["total_descontos"], 2, ',', '.'); ?></td>
                        </tr>

                        <tr class="titulo">
                            <td><b>Salário Líquido</b></td>
                            <td><b>R$ <?php echo number_format($folha["salario_liquido"], 2, ',', '.'); ?></b></td>
                        </tr>
                    </table>
                </article>

                <button class="btn btn-padrao" id="btnGerarPdf">Baixar PDF</button>
            <?php endif; ?>
        </section>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="../assets/js/script.js"></script>
    </main>
</body>

</html>