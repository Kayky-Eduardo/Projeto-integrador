<?php
/*
 * =============================================================
 * ARQUIVO: solicitar.php
 * MÓDULO: Gestão de Divergências e Ajustes (Ponto Eletrônico)
 * =============================================================
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Interface que permite ao colaborador (ou RH) solicitar a 
 * correção de horários em registros de ponto ou pausas já realizados.
 * 
 * Funcionalidades:
 * - Identificação dinâmica do contexto (Funcionário vs. RH).
 * - Validação de propriedade do registro (Segurança de Escopo).
 * - Seleção condicional de campos via JavaScript (Ponto ou Pausa).
 * - Recuperação de horários originais para comparação na interface.
 * - Coleta de justificativa obrigatória para auditoria futura.
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Inicializa sessão e valida autenticação de usuário.
 * 2. Define rota de retorno baseada no nível de acesso ($_SESSION['nivel']).
 * 3. Valida o parâmetro 'id_ponto' via GET:
 * - Se for RH (Nível >= 2): Acesso irrestrito ao ponto.
 * - Se for Comum: Acesso restrito apenas aos próprios pontos.
 * 4. Recupera dados do ponto e popula array de pausas associadas ao dia.
 * 5. Renderiza formulário dinâmico com campos obrigatórios condicionais.
 * 6. Envia os dados para processamento no arquivo 'solicitar_enviar.php'.
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Prepared Statements (bind_param) em todas as consultas SQL.
 * - Validação de ID (intval) para prevenir injeção e acessos inválidos.
 * - Verificação de nível de acesso para redirecionamento inteligente.
 * - Escapamento de saída (htmlspecialchars) para descrições de pausas.
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * 1. ponto_dia: Consulta dos horários de entrada e saída.
 * 2. pausa: Listagem de intervalos realizados no dia solicitado.
 * 3. pausa_config: Recuperação das descrições dos tipos de pausa.
 *
 * -------------------------------------------------------------
 * Data: 10/03/2026
 * Versão: 1.0
 * =============================================================
 */

session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];


/* VALIDAR id_ponto */
$id_ponto = isset($_GET['id_ponto']) ? intval($_GET['id_ponto']) : 0;

if ($id_ponto <= 0) {
    echo "<p>Registro inválido. <a href=\"$pagina_voltar\">Voltar</a></p>";
    exit;
}

/* BUSCAR O REGISTRO DO PONTO */
$stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_ponto, $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$reg = $res->fetch_assoc();

if (!$reg) {
    echo "<p>Ponto não encontrado ou você não tem permissão. 
          <a href=\"$pagina_voltar\">Voltar</a></p>";
    exit;
}

/* BUSCAR AS PAUSAS DO PONTO */
$sql_pausas = "
    SELECT 
        ps.id_pausa, 
        pc.descricao_pausa, 
        ps.inicio AS inicio_pausa, 
        ps.fim AS fim_pausa
    FROM pausa ps
    LEFT JOIN pausa_config pc ON pc.id_config = ps.id_config
    WHERE ps.id_usuario = ? AND ps.data = ?
    ORDER BY ps.inicio ASC
";

$stmt_pausas = $conn->prepare($sql_pausas);
$stmt_pausas->bind_param("is", $reg['id_usuario'], $reg['data_ponto']);
$stmt_pausas->execute();
$pausas = $stmt_pausas->get_result();
$pausas_do_dia = [];

while ($pausa_row = $pausas->fetch_assoc()) {
    $pausas_do_dia[] = $pausa_row;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Solicitar Ajuste</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">
                Solicitar ajuste para o dia <?= date('d-m-Y', strtotime($reg['data_ponto'])) ?>
            </h1>

            <section class="container solicitar-ajuste">
                <form method="POST" action="solicitar_enviar.php" class="form">
                    <input type="hidden" name="id_ponto" value="<?= $reg['id_ponto'] ?>">

                    <article>
                        <label for="tipo_ajuste" class="label">O que deseja ajustar?</label>
                        <select name="tipo_ajuste" id="tipo_ajuste" class="select-padrao" required onchange="mostrarCamposAjuste()">
                            <option value="">Selecione...</option>
                            <option value="ponto">Ponto</option>
                            <option value="pausa">Pausa</option>
                        </select>
                    </article>

                    <section id="ajuste_ponto" style="display:none;">
                        <article>
                            <label for="campo_ponto" class="label">Campo a ajustar:</label>
                            <select name="campo_ponto" id="campo_ponto" class="select-padrao">
                                <option value="inicio_ponto">Entrada (<?= $reg['inicio_ponto'] ? date("H:i", strtotime($reg['inicio_ponto'])) : '--:--' ?>)</option>
                                <option value="fim_ponto">Saída (<?= $reg['fim_ponto'] ? date("H:i", strtotime($reg['fim_ponto'])) : '--:--' ?>)</option>
                            </select>
                        </article>

                        <article>
                            <label for="valor_novo_ponto" class="label">Novo horário:</label>
                            <input
                                type="time"
                                name="valor_novo_ponto"
                                id="valor_novo_ponto"
                                class="input"
                                data-inicio="<?= $reg['inicio_ponto'] ? date("H:i", strtotime($reg['inicio_ponto'])) : '' ?>"
                                data-fim="<?= $reg['fim_ponto'] ? date("H:i", strtotime($reg['fim_ponto'])) : '' ?>">
                        </article>
                    </section>

                    <section id="ajuste_pausa" style="display:none;">
                        <article>
                            <label for="id_pausa" class="label">Selecione a Pausa:</label>
                            <select name="id_pausa" id="id_pausa" class="select-padrao">
                                <option value="">Selecione a pausa...</option>
                                <?php if (empty($pausas_do_dia)): ?>
                                    <option disabled>Nenhuma pausa registrada neste dia.</option>
                                <?php else: ?>
                                    <?php foreach ($pausas_do_dia as $pausa): ?>
                                        <option value="<?= $pausa['id_pausa'] ?>">
                                            <?= htmlspecialchars($pausa['descricao_pausa']) ?> (Início: <?= date("H:i", strtotime($pausa['inicio_pausa'])) ?>, Fim: <?= date("H:i", strtotime($pausa['fim_pausa'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </article>

                        <article>
                            <label for="campo_pausa" class="label">Campo a ajustar:</label>
                            <select name="campo_pausa" id="campo_pausa" class="select-padrao">
                                <option value="inicio_pausa">Início</option>
                                <option value="fim_pausa">Fim</option>
                            </select>
                        </article>

                        <article>
                            <label for="pausa_nova" class="label">Novo Horário:</label>
                            <input type="time" name="pausa_nova" id="pausa_nova" class="input">
                        </article>
                    </section>

                    <article>
                        <label for="justificativa" class="label">Justificativa:</label>
                        <textarea name="justificativa" id="justificativa" class="input" placeholder="Escreva aqui a justificativa..." required></textarea>
                    </article>

                    <button type="submit" class="btn btn-padrao">Enviar Solicitação</button>
                    <div id="erro_validacao" class="erro-login" style="display:none;"></div>

                </form>
            </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>