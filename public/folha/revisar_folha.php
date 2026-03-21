<?php
require_once "../../BD/conexao.php";
require_once "../../include/funcoes/calculo_desconto_falta.php";
session_start();

$mes = $_GET["mes"] ?? null;
if (!$mes) {
    header("Location: gerar_folhas_todos.php?erro=Mês inacessível.");
    exit();
}

$mes_comp = $mes . "-01";

$id_usuario_logado = $_SESSION['id_usuario'];
$id_usuario = $_GET['id_usuario'] ?? $id_usuario_logado;

// Nível logado
$sql_nivel_logado = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");

$sql_nivel_logado->bind_param("i", $id_usuario_logado);
$sql_nivel_logado->execute();
$nivel_logado = $sql_nivel_logado->get_result()->fetch_assoc()['nivel'] ?? 0;

// Nível alvo
$sql_nivel_alvo = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");

$sql_nivel_alvo->bind_param("i", $id_usuario);
$sql_nivel_alvo->execute();
$nivel_alvo = $sql_nivel_alvo->get_result()->fetch_assoc()['nivel'] ?? 0;

// Permissão
if ($id_usuario != $id_usuario_logado && $nivel_logado < $nivel_alvo) {
    header("Location: gerar_folhas_todos.php?erro=Acesso Negado.");
    exit();
}

// Dados usuário
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

// Folha
$sql_folha = $conn->prepare("
    SELECT *
    FROM folhas
    WHERE id_usuario = ? AND mes_competencia = ?
");

$sql_folha->bind_param("is", $id_usuario, $mes_comp);
$sql_folha->execute();
$folha = $sql_folha->get_result()->fetch_assoc();

// POST revisar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if ($dados && ($dados['acao'] ?? '') === 'revisar') {
        $id_usuario = $dados['id_usuario'];
        $sql = $conn->prepare("UPDATE folhas SET revisado = 1 WHERE id_usuario = ? AND revisado = 0");
        $sql->bind_param('i', $id_usuario);

        echo json_encode([
            'status' => $sql->execute() ? 'sucesso' : 'erro',
            'msg' => $sql->execute() ? 'Folha Revisada!' : 'Erro ao revisar.'
        ]);

        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Holerite <?php echo $mes; ?></title>

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
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

    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <?php if (!$folha): ?>
                <article>
                    <h1 class="page-title">Holerite não encontrado</h1>

                    <p>
                        Não existe folha de pagamento para o mês<br>
                        <b><?php echo date("m/Y", strtotime($mes_comp)); ?></b>.
                    </p>
                </article>
            <?php else: ?>
                <h1 class="page-title">HOLERITE <?php echo date("m/Y", strtotime($mes_comp)); ?></h1>

                <article id="holerite">
                    <table>
                        <tr class="titulo">
                            <td colspan="2">Empresa</td>
                        </tr>

                        <tr>
                            <td>Nome:</td>
                            <td>Sem nome</td>
                        </tr>

                        <tr>
                            <td>Endereço:</td>
                            <td>Sem endereço</td>
                        </tr>

                        <tr>
                            <td>CNPJ:</td>
                            <td>Sem CNPJ</td>
                        </tr>

                        <tr class="titulo">
                            <td colspan="2">Funcionário</td>
                        </tr>

                        <tr>
                            <td>Nome:</td>
                            <td><?= $user["nome_usuario"] ?></td>
                        </tr>

                        <tr>
                            <td>CPF:</td>
                            <td><?= $user["cpf_usuario"] ?></td>
                        </tr>

                        <tr>
                            <td>Cargo:</td>
                            <td><?= $user["nome_cargo"] ?></td>
                        </tr>

                        <tr>
                            <td>Admissão:</td>
                            <td><?= date("d/m/Y", strtotime($user["data_admissao"])) ?></td>
                        </tr>

                        <tr class="titulo">
                            <td colspan="2">Proventos e Descontos</td>
                        </tr>

                        <?php if (empty($eventos)): ?>
                            <tr>
                                <td colspan="2">Nenhum evento cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventos as $e): ?>
                                <tr>
                                    <td><?= strtoupper($e["tipo"]) . " - " . $e["descricao"] ?></td>
                                    <td>R$ <?= number_format($e["valor"], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <tr class="titulo">
                            <td colspan="2">Resumo</td>
                        </tr>

                        <tr>
                            <td>Salário Bruto:</td>
                            <td>R$ <?= number_format($folha["salario_bruto"], 2, ',', '.') ?></td>
                        </tr>

                        <tr>
                            <td>Total Proventos:</td>
                            <td>R$ <?= number_format($folha["total_proventos"], 2, ',', '.') ?></td>
                        </tr>

                        <tr>
                            <td>Total Descontos:</td>
                            <td>R$ <?= number_format($folha["total_descontos"], 2, ',', '.') ?></td>
                        </tr>

                        <tr>
                            <td>VT:</td>
                            <td>R$ <?= number_format($folha["vt"], 2, ',', '.') ?></td>
                        </tr>

                        <tr>
                            <td>INSS:</td>
                            <td>R$ <?= number_format($folha["inss"], 2, ',', '.') ?></td>
                        </tr>

                        <tr>
                            <td>IRRF:</td>
                            <td>R$ <?= number_format($folha["irrf"], 2, ',', '.') ?></td>
                        </tr>

                        <tr class="titulo">
                            <td><b>Salário Líquido</b></td>
                            <td><b>R$ <?= number_format($folha["salario_liquido"], 2, ',', '.') ?></b></td>
                        </tr>
                    </table>
                </article>

                <button class="btn btn-padrao" id="<?= $id_usuario ?>">Marcar como Revisado</button>
            <?php endif; ?>
        </section>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const btn = document.querySelector(".btn-padrao");

                if (btn) {
                    btn.addEventListener("click", () => {
                        const idUsuario = btn.id;

                        fetch(window.location.href, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    acao: "revisar",
                                    id_usuario: idUsuario
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                alert(data.msg);
                            })
                            .catch(err => {
                                console.error("Erro:", err);
                            });
                    });
                }
            });
        </script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="../../assets/js/script.js"></script>
    </main>
</body>

</html>