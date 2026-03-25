<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// 1. Recebe o mês (competência)
$mes = $_GET["mes"] ?? null;

if (!$mes) {
    header("Location: gerar_folhas_todos.php?erro=Mês inacessível.");
    exit();
}

$mes_comp = $mes . "-01";

// 2. Usuário
$id_usuario_logado = $_SESSION['id_usuario'];
$id_usuario = $_GET['id_usuario'] ?? $id_usuario_logado;

// 3. Nível usuário logado
$sql_nivel_logado = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");

$sql_nivel_logado->bind_param("i", $id_usuario_logado);
$sql_nivel_logado->execute();
$nivel_logado = $sql_nivel_logado->get_result()->fetch_assoc()['nivel'] ?? 0;

// 4. Nível usuário alvo
$sql_nivel_alvo = $conn->prepare("
    SELECT c.nivel 
    FROM usuario u
    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
    WHERE u.id_usuario = ?
");

$sql_nivel_alvo->bind_param("i", $id_usuario);
$sql_nivel_alvo->execute();
$nivel_alvo = $sql_nivel_alvo->get_result()->fetch_assoc()['nivel'] ?? 0;

// 5. Permissão
if ($id_usuario != $id_usuario_logado && $nivel_logado < $nivel_alvo) {
    header("Location: gerar_folhas_todos.php?erro=Acesso Negado.");
    exit();
}

// 6. Dados do usuário
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

// 7. Folha
$sql_folha = $conn->prepare("
    SELECT *
    FROM folhas
    WHERE id_usuario = ? AND mes_competencia = ?
");

$sql_folha->bind_param("is", $id_usuario, $mes_comp);
$sql_folha->execute();
$folha = $sql_folha->get_result()->fetch_assoc();

// 8. Eventos
$sql_eventos = $conn->prepare("
    SELECT id_evento, tipo, descricao, valor 
    FROM eventos 
    WHERE id_usuario = ? AND mes_competencia = ?
");

$sql_eventos->bind_param("is", $id_usuario, $mes_comp);
$sql_eventos->execute();
$eventos = $sql_eventos->get_result()->fetch_all(MYSQLI_ASSOC);

// POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if ($dados && isset($dados['acao'])) {
        $acao = $dados['acao'];

        if ($acao == 'deletar' && !empty($dados['id_evento'])) {
            if (!empty($dados['confirmado'])) {
                $sql = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
                $sql->bind_param('i', $dados['id_evento']);

                if ($sql->execute()) {
                    echo json_encode(['status' => 'sucesso', 'msg' => 'Evento Deletado!']);
                } else {
                    echo json_encode(['status' => 'erro', 'msg' => 'Erro ao deletar.']);
                }
                exit;
            }
        } elseif ($acao == 'editar' && !empty($dados['id_editar'])) {
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

// Empresa
$sql_empresa = $conn->prepare("
    SELECT *
    FROM empresas
    WHERE id_empresa = 0
");

$sql_empresa->execute();
$empresa = $sql_empresa->get_result()->fetch_assoc();

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
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Editar Holerite <?= $mes; ?></title>
    <?php include "../../include/link.html"; ?>
    <link rel="stylesheet" href="../../assets/css/estilo.css">

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
            cursor: pointer;
        }

        input {
            width: 100%;
            box-sizing: border-box;
        }

        .campo-valor {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .campo-valor input {
            width: 120px;
        }

        .campo-valor button {
            white-space: nowrap;
        }

        .texto-centro {
            text-align: center;
        }
    </style>
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
                        <b><?= date("m/Y", strtotime($mes_comp)); ?></b>.
                    </p>
                </article>
            <?php else: ?>
                <h1 class="page-title">
                    Editar Holerite - <?= $mes_formatado; ?>
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
                            <td><?= $user["nome_usuario"]; ?></td>
                        </tr>

                        <tr>
                            <td>CPF:</td>
                            <td><?= $user["cpf_usuario"]; ?></td>
                        </tr>

                        <tr>
                            <td>Cargo:</td>
                            <td><?= $user["nome_cargo"]; ?></td>
                        </tr>

                        <tr>
                            <td>Admissão:</td>
                            <td><?= date("d/m/Y", strtotime($user["data_admissao"])); ?></td>
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
                                    <td><?= strtoupper($e["tipo"]) . " - " . $e["descricao"]; ?></td>

                                    <td class="campo-valor">
                                        <span>R$</span>

                                        <input id="<?= $e["id_evento"] ?>"
                                            class="input input-editar"
                                            type="number"
                                            step="0.01"
                                            value="<?= $e["valor"] ?>">

                                        <button type="button"
                                            class="btn btn-padrao btn-deletar"
                                            data-id="<?= $e["id_evento"] ?>">
                                            deletar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </article>

                <article class="texto-centro">Gerado automaticamente</article>
            <?php endif; ?>
        </section>
        </section>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="../../assets/js/script.js"></script>
    </main>
</body>

</html>