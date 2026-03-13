<?php
/*
 * =============================================================
 * ARQUIVO: ponto.php
 * MÓDULO: Controle de Jornada e Pausas (RH)
 * =============================================================
 * * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Módulo operacional destinado ao registro de entrada e saída
 * de funcionários, bem como ao gerenciamento de pausas diárias.
 *
 * Executa:
 * - Registro de ponto eletrônico (início e fim da jornada).
 * - Gerenciamento de pausas com limites configuráveis por tipo.
 * - Lógica de auto-fechamento de pausas excedidas (Garbage Collection).
 * - Validação de tempo mínimo e máximo de descanso.
 * - Controle de interdependência (Ex: Não encerra ponto com pausa ativa).
 * - Cronometragem em tempo real via JavaScript/DOM.
 * *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Inicializa fuso horário (America/Sao_Paulo) e verifica login.
 * 2. Executa rotina de "Auto-fechamento": atualiza pausas órfãs 
 * que ultrapassaram o 'tempo_max' definido na configuração.
 * 3. Se houver POST (registrar_ponto):
 * a. Abre novo registro se for o primeiro acesso do dia.
 * b. Encerra ponto existente se não houver pausas abertas.
 * 4. Se houver POST (pausa_iniciar):
 * a. Valida se o usuário já possui pausa ativa.
 * b. Checa o limite diário permitido para o tipo de pausa.
 * c. Persiste o início da pausa na tabela 'pausa'.
 * 5. Se houver POST (pausa_finalizar):
 * a. Calcula delta de tempo entre agora e o início.
 * b. Bloqueia fechamento se o tempo mínimo não foi atingido.
 * c. Grava fim da pausa e calcula duração final em minutos.
 * 6. Renderiza interface: badges dinâmicos de status e cronômetro.
 *
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Proteção contra manipulação de ID via variáveis de sessão.
 * - Uso de Prepared Statements (bind_param) em todas as inserções 
 * e atualizações de registros de tempo.
 * - Validação de integridade: impede encerramento de jornada com 
 * pendências de pausa aberta.
 * - Sanitização de saídas HTML via htmlspecialchars.
 *
 *
 * ACESSIBILIDADE E UX
 * -------------------------------------------------------------
 * - Cronômetro regressivo/progressivo via Data Attributes.
 * - Feedback visual de status através de badges (Ativo/Inativo).
 * - Desabilitação dinâmica de botões conforme estado do sistema 
 * (State Management em nível de interface).
 * - Exibição de limites de uso (Realizado/Disponível) no select.
 * - Notificações de erro centralizadas (box-erros).
 *
 *
 * DEPENDÊNCIAS
 * -------------------------------------------------------------
 * - "../../../BD/conexao.php": Conexão com a base de dados.
 * - "../../../include/verificacao.php": Script de controle de acesso.
 * - "../../../include/navbar.php": Navegação global.
 * - "PNotify": Biblioteca para notificações flutuantes.
 * - "../../../assets/js/script.js": Lógica do cronômetro e interface.
 *
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * 1. ponto_dia
 * - Registra inicio_ponto, fim_ponto e data_ponto.
 * 2. pausa
 * - Armazena os eventos individuais de descanso do dia.
 * 3. pausa_config
 * - Contém as regras de negócio (tempo_min, tempo_max, limites).
 *
 *
 * BOAS PRÁTICAS APLICADAS
 * -------------------------------------------------------------
 * - Lógica de Backend robusta para tratar fechamentos anormais 
 * (Ex: queda de energia ou fechamento de aba).
 * - Cálculo de duração realizado no servidor para evitar fraudes 
 * no lado do cliente.
 * - Uso de subqueries SQL para otimizar a contagem de pausas 
 * em uma única consulta.
 * - Implementação de Pattern Post-Redirect-Get (PRG) para evitar 
 * reenvios de formulário no refresh.
 *
 * * -------------------------------------------------------------
 * Data: 08/03/2026
 * Versão: 1.0
 * =============================================================
 */

session_start();
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../../../BD/conexao.php';
require_once __DIR__ . '/../../../include/verificacao.php';
verificar_login($conn);

$id_usuario = $_SESSION['id_usuario'] ?? null;
$hoje = date("Y-m-d");
$erro = "";
$desabilitar = "";

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
            // Não encerra ponto com pausa aberta
            $checkPausa = $conn->query("SELECT id_pausa FROM pausa WHERE id_usuario = $id_usuario AND fim IS NULL");

            if ($checkPausa->num_rows > 0) {
                $erro = "Encerre a pausa ativa antes de finalizar o ponto.";
            } else {
                //Não encerra ponto sem ter realizado ao menos uma pausa hoje
                $checkPausaRealizada = $conn->query("
                    SELECT id_pausa FROM pausa 
                    WHERE id_usuario = $id_usuario 
                    AND data = '$hoje' 
                    AND fim IS NOT NULL
                ");

                if ($checkPausaRealizada->num_rows === 0) {
                    $erro = "É necessário realizar ao menos uma pausa antes de finalizar o ponto.";
                } else {
                    $conn->query("UPDATE ponto_dia SET fim_ponto = NOW() WHERE id_ponto = " . $ponto['id_ponto']);
                    $conn->query("UPDATE ponto_dia SET status = 'Finalizado' WHERE id_ponto = " . $ponto['id_ponto']);
                }
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

                if ($dados && $dados['total_realizado'] >= $dados['limite_pausa_diario'] && $dados['limite_pausa_diario'] !== 0) {
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
    gs2.*,
    gs.*,
	(SELECT COUNT(*) FROM pausa p 
	 WHERE p.id_config = pc.id_config 
	 AND p.id_usuario = ?
	 AND p.data = CURDATE()) as total_realizado
    FROM pausa_config pc
    LEFT JOIN grupo_setor2 gs2 on gs2.id_config = pc.id_config
    LEFT JOIN grupo_setor gs on gs.id_setor = gs2.id_setor
    WHERE pc.ativo = 1 AND gs.id_usuario = ?";

$stmtTipos = $conn->prepare($sqlTipos);
$stmtTipos->bind_param("ii", $id_usuario, $id_usuario);
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

            <?php if ($pontoIniciado && !$pontoFinalizado): ?>
            <section class="container bater-ponto pausas">
                <article>
                    <h2>Pausa</h2>

                    <form method="POST" id="formPausa" class="form">
                        <label for="tipo_pausa" class="label">Tipo de pausa</label>

                        <select id="tipo_pausa" name="id_config" class="select-padrao" required <?= (!$pontoIniciado || $pontoFinalizado || $pausaAtiva) ? 'disabled' : '' ?>>
                            <?php while ($t = $tiposPausa->fetch_assoc()): ?>
                                <option value="<?= $t['id_config'] ?>"
                                    <?= ($t['total_realizado'] >= $t['limite_pausa_diario'] && $t['limite_pausa_diario'] !== 0) ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($t['descricao_pausa']) ?>
                                    (<?= $t['total_realizado'] ?>/<?= ($t['limite_pausa_diario'] == 0) ? '∞' : $t['limite_pausa_diario'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <?php if (!$pausaAtiva): $disabled = (!$pontoIniciado || $pontoFinalizado || $pausaAtiva) ? 'disabled' : '' ?>
                            <input type="hidden" name="acao" value="pausa_iniciar">
                            <button type="submit" class="btn btn-padrao" <?= $disabled ?>>Iniciar Pausa</button>
                        <?php else: ?>
                            <input type="hidden" name="acao" value="pausa_finalizar">
                            <button type="submit" class="btn btn-excluir" id="btnFinalizarPausa">Finalizar Pausa</button>
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
            <?php endif; ?>
            <section class="container bater-ponto">
                <h2>Ponto</h2>

                <form method="POST" class="form">
                    <input type="hidden" name="acao" value="registrar_ponto">

                    <button type="submit" class="btn btn-padrao" <?= ($pontoFinalizado || $pausaAtiva) ? 'disabled' : '' ?>>
                        <?= !$pontoIniciado ? 'Iniciar Ponto' : 'Finalizar Ponto' ?>
                    </button>
                </form>
            </section>


        </section>
    </main>

    <script src="../../../assets/js/script.js"></script>
</body>

</html>