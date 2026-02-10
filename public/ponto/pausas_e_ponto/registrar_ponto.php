<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require_once __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) die("Acesso negado.");
$hoje = date("Y-m-d");
$erro = "";
$desabilitar = "";

// LÓGICA DE AUTO-FECHAMENTO (BACKEND)
// Fecha pausas que excederam o tempo_max caso o usuário tenha fechado o navegador
$conn->query("UPDATE pausa p 
              JOIN pausa_config c ON p.id_config = c.id_config 
              SET p.fim = DATE_ADD(p.inicio, INTERVAL c.tempo_max MINUTE), 
                  p.duracao_minutos = c.tempo_max 
              WHERE p.fim IS NULL AND p.id_usuario = $id_usuario 
              AND TIMESTAMPDIFF(SECOND, p.inicio, NOW()) >= (c.tempo_max * 60)");

// PROCESSAMENTO DE AÇÕES VIA POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'registrar_ponto') {
        $res = $conn->query("SELECT id_ponto, inicio_ponto, fim_ponto FROM ponto_dia WHERE id_usuario = $id_usuario AND data_ponto = '$hoje'");
        $ponto = $res->fetch_assoc();

        if (!$ponto) {
            $stmt = $conn->prepare("INSERT INTO ponto_dia (id_usuario, data_ponto, inicio_ponto) VALUES (?, ?, NOW())");
            $stmt->bind_param("is", $id_usuario, $hoje);
            $stmt->execute();
        } elseif (empty($ponto['fim_ponto'])) {
            // Regra: Não encerra ponto com pausa aberta
            $checkPausa = $conn->query("SELECT id_pausa FROM pausa WHERE id_usuario = $id_usuario AND fim IS NULL");

            if ($checkPausa->num_rows > 0) {
                $erro = "Encerre a pausa ativa antes de finalizar o ponto.";
            } else {
                $conn->query("UPDATE ponto_dia SET fim_ponto = NOW() WHERE id_ponto = " . $ponto['id_ponto']);
            }
        }
    } elseif ($acao === 'pausa_iniciar') {

        if (empty($_POST['id_config'])) {
            $erro = "Selecione um tipo de pausa.";
        } else {
            $id_config = intval($_POST['id_config']);

            $checkAtiva = $conn->query(
                "SELECT id_pausa FROM pausa 
             WHERE id_usuario = $id_usuario AND fim IS NULL"
            );

            if ($checkAtiva->num_rows > 0) {
                $erro = "Você já possui uma pausa ativa.";
            } else {
                $stmtLimite = $conn->prepare("
                    SELECT 
                        pc.limite_pausa_diario,
                        COUNT(p.id_pausa) AS total_realizado
                    FROM pausa_config pc
                    LEFT JOIN pausa p 
                        ON p.id_config = pc.id_config
                        AND p.id_usuario = ?
                        AND p.data = CURDATE()
                    WHERE pc.id_config = ?
                    GROUP BY pc.id_config
                ");

                $stmtLimite->bind_param("ii", $id_usuario, $id_config);
                $stmtLimite->execute();
                $dados = $stmtLimite->get_result()->fetch_assoc();

                if ($dados && $dados['total_realizado'] >= $dados['limite_pausa_diario']) {
                    $erro = "Você já atingiu o limite diário dessa pausa.";
                } else {

                    $stmt = $conn->prepare(
                        "
                        INSERT INTO pausa (id_usuario, id_config, inicio, data)
                        VALUES (?, ?, NOW(), ?)"
                    );
                    $stmt->bind_param("iis", $id_usuario, $id_config, $hoje);
                    $stmt->execute();
                }
            }
        }
    } elseif ($acao === 'pausa_finalizar') {
        $resP = $conn->query("SELECT p.id_pausa, p.inicio, c.tempo_min FROM pausa p 
                              JOIN pausa_config c ON p.id_config = c.id_config 
                              WHERE p.id_usuario = $id_usuario AND p.fim IS NULL LIMIT 1");

        if ($pausa = $resP->fetch_assoc()) {
            $segundos_decorridos = time() - strtotime($pausa['inicio']);

            if ($segundos_decorridos < ($pausa['tempo_min'] * 60)) {
                $erro = "Tempo mínimo de " . $pausa['tempo_min'] . " minutos não atingido.";
            } else {
                $minutos = floor($segundos_decorridos / 60);
                $stmt = $conn->prepare("UPDATE pausa SET fim = NOW(), duracao_minutos = ? WHERE id_pausa = ?");
                $stmt->bind_param("ii", $minutos, $pausa['id_pausa']);
                $stmt->execute();
            }
        }
    }

    if (!$erro) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// DADOS PARA A INTERFACE
$statusPonto = $conn->query("SELECT * FROM ponto_dia WHERE id_usuario = $id_usuario AND data_ponto = '$hoje'")->fetch_assoc();

$pausaAtiva = $conn->query("SELECT p.*, c.descricao_pausa, c.tempo_max, c.tempo_min FROM pausa p 
                            JOIN pausa_config c ON p.id_config = c.id_config 
                            WHERE p.id_usuario = $id_usuario AND p.fim IS NULL LIMIT 1")->fetch_assoc();

$pontoIniciado = ($statusPonto && !empty($statusPonto['inicio_ponto']));
$pontoFinalizado = ($statusPonto && !empty($statusPonto['fim_ponto']));

// Busca os tipos de pausa e já conta quantas o usuário fez hoje
$sqlTipos = "SELECT 
                pc.*, 
                (SELECT COUNT(*) FROM pausa p 
                 WHERE p.id_config = pc.id_config 
                 AND p.id_usuario = ? 
                 AND p.data = CURDATE()) as total_realizado
             FROM pausa_config pc
             WHERE pc.ativo = 1";

$stmtTipos = $conn->prepare($sqlTipos);
$stmtTipos->bind_param("i", $id_usuario);
$stmtTipos->execute();
$tiposPausa = $stmtTipos->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Ponto e Pausas</title>
    <link href="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/BrightTheme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@pnotify/mobile@5.2.0/dist/PNotifyMobile.js"></script>
    <link rel="stylesheet" href="../../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Registro de Ponto</h1>

            <section class="container ponto-dia">
                <?php if ($erro): ?>
                    <div class="box-erros">
                        <strong>Erro:</strong> <?= $erro ?>
                    </div>
                <?php endif; ?>

                <ul>
                    <li class="card">
                        <h2>Ponto:</h2>

                        <span class="status-badge <?= !$pontoIniciado ? 'status-badge-inativo' : 'status-badge-ativo' ?>">
                            <?= !$pontoIniciado ? 'Nenhum' : ($pontoFinalizado ? 'Finalizado' : 'Em andamento') ?>
                        </span>
                    </li>

                    <li class="card">
                        <h2>Pausa(as):</h2>

                        <span class="status-badge <?= $pausaAtiva ? 'status-badge-ativo' : 'status-badge-inativo' ?>">
                            <?= $pausaAtiva ? 'Ativa' : 'Nenhuma' ?>
                        </span>
                    </li>
                </ul>
            </section>

            <section class="container bater-ponto">
                <h2>Ponto</h2>

                <form method="POST" class="form">
                    <input type="hidden" name="acao" value="registrar_ponto">

                    <button type="submit" class="btn btn-padrao" <?= ($pontoFinalizado || $pausaAtiva) ? 'disabled' : '' ?>>
                        <?= !$pontoIniciado ? 'Iniciar Ponto' : 'Finalizar Ponto' ?>
                    </button>
                </form>
            </section>

            <section class="container bater-ponto">
                <article>
                    <h2>Pausa</h2>

                    <form method="POST" id="formPausa" class="form">
                        <label for="tipo_pausa" class="label">Tipo de pausa</label>

                        <select id="tipo_pausa" name="id_config" class="select-padrao" required <?= (!$pontoIniciado || $pontoFinalizado || $pausaAtiva) ? 'disabled' : '' ?>>
                            <?php while ($t = $tiposPausa->fetch_assoc()): ?>
                                <option value="<?= $t['id_config'] ?>"
                                    <?= ($t['total_realizado'] >= $t['limite_pausa_diario']) ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($t['descricao_pausa']) ?>
                                    (<?= $t['total_realizado'] ?>/<?= $t['limite_pausa_diario'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <?php if (!$pausaAtiva): $disabled = (!$pontoIniciado || $pontoFinalizado || $pausaAtiva) ? 'disabled' : '' ?>
                            <input type="hidden" name="acao" value="pausa_iniciar">
                            <button type="submit" class="btn btn-padrao" <?= $disabled ?>>Iniciar Pausa</button>
                        <?php else: ?>
                            <input type="hidden" name="acao" value="pausa_finalizar">
                            <button type="submit" class="btn btn-excluir" id="btnFinalizarPausa" <?= $disabled ?>>Finalizar Pausa</button>
                            <p id="statusTempo" class="status-tempo"></p>
                        <?php endif; ?>
                    </form>
                </article>

                <article>
                    <h2>Pausa Ativa</h2>

                    <?php if ($pausaAtiva): ?>
                        <article class="tabela-padrao">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Início</th>
                                        <th>Tempo</th>
                                        <th>Limites</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td><?= htmlspecialchars($pausaAtiva['descricao_pausa']) ?></td>
                                        <td><?= date('H:i:s', strtotime($pausaAtiva['inicio'])) ?></td>

                                        <td id="cronometro"
                                            data-inicio="<?= $pausaAtiva['data'] . 'T' . $pausaAtiva['inicio'] ?>"
                                            data-min="<?= $pausaAtiva['tempo_min'] ?>"
                                            data-max="<?= $pausaAtiva['tempo_max'] ?>">
                                            00:00
                                        </td>

                                        <td>
                                            <?= $pausaAtiva['tempo_min'] ?> /
                                            <?= $pausaAtiva['tempo_max'] ?> min
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </article>
                    <?php else: ?>
                        <p>Nenhuma pausa ativa no momento.</p>
                    <?php endif; ?>
                </article>
            </section>
        </section>
    </main>

    <script src="../../../assets/js/script.js"></script>
</body>

</html>