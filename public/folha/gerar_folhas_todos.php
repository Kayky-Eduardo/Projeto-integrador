<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/funcoes/funcoes_calculo.php";
$msg_acesso = $_GET['erro'] ?? '';
$mensagem_evento = $_SESSION['msg'] ?? '';
$cor_evento = $_SESSION['cor'] ?? '';
unset($_SESSION['msg'], $_SESSION['cor']);

// 1. Recebe mês do formulário
$mes = date('Y-m'); // default: mês atual
$mes_padrao = $mes . "-01";

// 2. Buscar todos os usuários ativos
$sql = "SELECT u.id_usuario,
        u.nome_usuario,
        u.id_cargo,
        c.id_cargo,
        c.nome_cargo
        FROM usuario u
        JOIN cargo c ON c.id_cargo = u.id_cargo
        WHERE conta_ativa = 1";
$result = $conn->query($sql);
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

// 3. Adicionar evento (form separado)
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($_POST['add_evento'])) {
        $id_usuario_evento = $_POST['id_usuario_evento'] ?? null;
        $tipo = $_POST['tipo'] ?? '';
        $descricao = $_POST['descricao'] ?? 'Não Informado';
        $valor = floatval($_POST['valor'] ?? 0);
        $data = $_POST['mesEvento'] ?? $mes;

        if ($id_usuario_evento === 'todos') {
            $stmt = $conn->prepare("
                SELECT * FROM folhas WHERE mes_competencia = ? AND revisado = 0
            ");

            $stmt->bind_param("s", $mes_padrao);
            $stmt->execute();
            $verificacao = $stmt->get_result();

            if (($verificacao->num_rows > 0)) {
                foreach ($usuarios as $u) {
                    $stmtInsert = $conn->prepare("
                    INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
                    VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->bind_param("issds", $u['id_usuario'], $tipo, $descricao, $valor, $mes_padrao);
                    $stmtInsert->execute();
                }
                $_SESSION['msg'] = "Evento adicionado com sucesso!";
                $_SESSION['cor'] = "green";

                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $_SESSION['msg'] = "Todas as folhas já foram revisadas!";
                $_SESSION['cor'] = "red";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }

        if ($id_usuario_evento && $tipo && $valor > 0 && $data == date('Y-m')) {
            $stmt = $conn->prepare("
                SELECT * FROM folhas WHERE id_usuario = ? AND mes_competencia = ? AND revisado = 0
            ");

            $stmt->bind_param("is", $id_usuario_evento, $mes_padrao);
            $stmt->execute();
            $verificacao = $stmt->get_result();

            if (($verificacao->num_rows > 0)) {
                $stmtInsert = $conn->prepare("
                    INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmtInsert->bind_param("issds", $id_usuario_evento, $tipo, $descricao, $valor, $mes_padrao);
                $stmtInsert->execute();
                $_SESSION['msg'] = "Evento adicionado com sucesso!";
                $_SESSION['cor'] = "green";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $_SESSION['msg'] = "Folha do Evento já foi revisada!";
                $_SESSION['cor'] = "red";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['msg'] = "Preencha todos os campos corretamente.";
            $_SESSION['cor'] = 'red';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// 4. Função para gerar folha de um usuário
function gerarFolhaUsuario(array $usuario, string $mes_padrao, $conn)
{
    $id_usuario = $usuario['id_usuario'];
    $stmtCheck = $conn->prepare("SELECT id_folha, salario_liquido, revisado FROM folhas WHERE id_usuario = ? AND mes_competencia = ?");
    $stmtCheck->bind_param("is", $id_usuario, $mes_padrao);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $folhaExistente = $resCheck->fetch_assoc();
    $stmtCheck->close();

    if ($folhaExistente && $folhaExistente['revisado'] == 1) {
        return [
            'id_usuario' => $id_usuario,
            'nome_usuario' => $usuario['nome_usuario'],
            'salario_liquido' => $folhaExistente['salario_liquido'],
            'revisado' => 1,
        ];
    }

    $stmt = $conn->prepare("
        SELECT c.salario_bruto 
        FROM usuario u
        JOIN cargo c ON c.id_cargo = u.id_cargo
        WHERE u.id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    if (!$userData) return null;

    $salario_bruto = floatval($userData['salario_bruto']);
    $stmt2 = $conn->prepare("SELECT tipo, valor FROM eventos WHERE id_usuario = ? AND mes_competencia = ?");
    $stmt2->bind_param("is", $id_usuario, $mes_padrao);
    $stmt2->execute();
    $resEventos = $stmt2->get_result();

    $total_proventos = 0;
    $total_descontos = 0;
    while ($evt = $resEventos->fetch_assoc()) {
        if ($evt["tipo"] === "provento") $total_proventos += floatval($evt["valor"]);
        else $total_descontos += floatval($evt["valor"]);
    }

    $fgts = calcularFGTS($salario_bruto);
    $inss = calcularINSS($salario_bruto);
    $irrf = calcularIRRF($salario_bruto, $inss, 0);
    $vt   = calcularVT($salario_bruto, 300);
    $descontos_totais_calculados = $total_descontos + $inss + $irrf + $vt;
    $salario_liquido = calcularSalarioLiquido($salario_bruto, $total_proventos, $descontos_totais_calculados);

    if ($folhaExistente && $folhaExistente['revisado'] == 0) {
        $stmtUpdate = $conn->prepare("
            UPDATE folhas SET
                salario_bruto = ?, total_proventos = ?, total_descontos = ?, 
                fgts = ?, inss = ?, irrf = ?, vt = ?, salario_liquido = ?
            WHERE id_folha = ?
        ");

        $stmtUpdate->bind_param(
            "dddddddii",
            $salario_bruto,
            $total_proventos,
            $descontos_totais_calculados,
            $fgts,
            $inss,
            $irrf,
            $vt,
            $salario_liquido,
            $folhaExistente['id_folha']
        );

        $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        $stmtInsert = $conn->prepare("
            INSERT INTO folhas 
            (id_usuario, mes_competencia, salario_bruto, total_proventos, total_descontos, fgts, inss, irrf, vt, salario_liquido, revisado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");

        $stmtInsert->bind_param(
            "issddddddd",
            $id_usuario,
            $mes_padrao,
            $salario_bruto,
            $total_proventos,
            $descontos_totais_calculados,
            $fgts,
            $inss,
            $irrf,
            $vt,
            $salario_liquido
        );

        $stmtInsert->execute();
        $stmtInsert->close();
    }

    return [
        'id_usuario' => $id_usuario,
        'nome_usuario' => $usuario['nome_usuario'],
        'revisado' => 0
    ];
}

// 5. Gerar folhas se botão foi clicado
$folhas_geradas = [];
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $msg_acesso = '';
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'gerar') {
        foreach ($usuarios as $usuario) {
            $folha = gerarFolhaUsuario($usuario, $mes_padrao, $conn);
            if ($folha) $folhas_geradas[] = $folha;
        }
    }
    if ($acao === 'revisar') {
        $stmtRevisar = $conn->prepare('SELECT revisado FROM folhas WHERE revisado = 0 AND mes_competencia = ?');
        $stmtRevisar->bind_param('s', $mes_padrao);
        $stmtRevisar->execute();
        $totalLinhas = $stmtRevisar->get_result();
        if ($totalLinhas->num_rows > 0) {
            $stmt5 = $conn->prepare('UPDATE folhas SET revisado = 1 WHERE mes_competencia = ?');
            $stmt5->bind_param('s', $mes_padrao);
            $stmt5->execute();
        } else {
            $msg_acesso = 'Todas as Folhas já foram Revisadas.';
        }
    }
}

$dt = new DateTime($mes_padrao);

$meses = [
    'January' => 'Janeiro',
    'February' => 'Fevereiro',
    'March' => 'Março',
    'April' => 'Abril',
    'May' => 'Maio',
    'June' => 'Junho',
    'July' => 'Julho',
    'August' => 'Agosto',
    'September' => 'Setembro',
    'October' => 'Outubro',
    'November' => 'Novembro',
    'December' => 'Dezembro'
];

$mes_formatado = $meses[$dt->format('F')] . ' de ' . $dt->format('Y');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerar Folhas de Pagamento</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Gerar Folhas de Pagamento</h1>

            <section class="container gerar-folhas">
                <form class="form" method="POST">
                    <article>
                        <h2>Adicionar Provento/Desconto</h2>

                        <label class="label">Mês:</label>
                        <input class="input" type="month" name="mesEvento" id="mesEvento" value='<?= $mes ?>'>

                        <label class="label">Usuário:</label>
                        <select class="select-padrao" name="id_usuario_evento" required>
                            <option value="">-- Selecione --</option>
                            <option value="todos">Todos</option>

                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id_usuario'] ?>"><?= $u['nome_usuario'] ?> (<?= $u['nome_cargo'] ?>)</option>
                            <?php endforeach; ?>
                        </select>

                        <label class="label">Tipo:</label>
                        <select class="select-padrao" name="tipo" required>
                            <option value="provento">Provento</option>
                            <option value="desconto">Desconto</option>
                        </select>

                        <label class="label">Descrição:</label>
                        <input class="input" type="text" name="descricao" placeholder="Descrição do evento">

                        <label class="label">Valor:</label>
                        <input class="input" type="number" step="0.01" name="valor" placeholder="0.00">

                        <button class="btn btn-padrao" type="submit" name="add_evento" id="add_evento">Adicionar Evento</button>
                    </article>

                    <?php if ($mensagem_evento): ?>
                        <p style="color:<?= $cor_evento ?>;"><?= $mensagem_evento ?></p>
                    <?php endif; ?>
                </form>
            </section>

            <section class="container gerar-folhas">
                <form class="form" method="POST" id="Form">
                    <h2 id="h2">Folhas Geradas - <?= $mes_formatado ?></h2>

                    <label class="label">Mês:</label>
                    <input type="hidden" name="acao" id="inputAcao" value="">
                    <input class="input" type="month" id="mesGerar" name="mesGerar" value="<?= $mes ?>">

                    <button class="btn btn-padrao" type="button" id="gerar_folhas" name="gerar_folhas">
                        Gerar Todas as Folhas
                    </button>

                    <?php if (!empty($folhas_geradas)): ?>
                        <button class="btn btn-desativar btn-revisar" type="button">
                            Revisar todos
                        </button>
                    <?php endif; ?>
                </form>
            </section>

            <section class="gerar-folhas">
                <?php if (!empty($folhas_geradas)): ?>
                    <table class="tabela-padrao" id="tabela">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($folhas_geradas as $f): ?>
                                <tr>
                                    <td><?= $f['nome_usuario'] ?></td>

                                    <td>
                                        <?php if ($f['revisado'] == 1): ?>
                                            <a>Nenhuma ação disponível</a>
                                        <?php else: ?>
                                            <a class="btn-link btn-padrao" href="revisar_folha.php?mes=<?= $mes ?>&id_usuario=<?= $f['id_usuario'] ?>">
                                                Revisar PDF
                                            </a>

                                            <a class="btn-link btn-desativar" href="editar_folha.php?mes=<?= $mes ?>&id_usuario=<?= $f['id_usuario'] ?>">
                                                Editar Eventos
                                            </a>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </section>

        <script src="../../assets/js/script.js"></script>
    </main>
</body>

</html>
