<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require __DIR__ . "/../../include/verificacao.php";
verificar_login($conn);

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

// =======================
// DEFINIR PÁGINA DE VOLTA
// =======================
// Usuário comum volta para histórico
// RH volta para gerenciar
$pagina_voltar = ($nivel >= 2) ? "../ponto/gerenciar.php" : "../ponto/historico.php";

// ================
// VALIDAR id_ponto
// ================
// Captura o ID do ponto passado pela URL
$id_ponto = isset($_GET['id_ponto']) ? intval($_GET['id_ponto']) : 0;

// Se o ID for inválido, bloqueia o acesso
if ($id_ponto <= 0) {
    echo "<p>Registro inválido. <a href=\"$pagina_voltar\">Voltar</a></p>";
    exit;
}

// ==========================
// BUSCAR O REGISTRO DO PONTO
// ==========================
// Funcionário só pode ver o seu próprio ponto
// RH pode ver de qualquer usuário

// Caso seja RH, busca pelo id_ponto apenas
if ($nivel >= 2) {
    $stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ?");
    $stmt->bind_param("i", $id_ponto);
} else {
    // Funcionário só acessa pontos que pertencem a ele
    $stmt = $conn->prepare("SELECT * FROM ponto_dia WHERE id_ponto = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_ponto, $id_usuario);
}

// Executa a consulta
$stmt->execute();

// Obtém o resultado
$res = $stmt->get_result();

// Recupera os dados do ponto
$reg = $res->fetch_assoc();

// Se não encontrar, mostra erro de permissão ou inexistência
if (!$reg) {
    echo "<p>Ponto não encontrado ou você não tem permissão. 
          <a href=\"$pagina_voltar\">Voltar</a></p>";
    exit;
}

// -- Código Davi --
// ==========================
// BUSCAR AS PAUSAS DO PONTO
// ==========================
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
// Usa id_usuario e data do registro do ponto ($reg) já validado
$stmt_pausas->bind_param("is", $reg['id_usuario'], $reg['data_ponto']);
$stmt_pausas->execute();
$pausas = $stmt_pausas->get_result();

// Armazena as pausas em um array para uso no formulário
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
                            <input type="time" name="valor_novo_ponto" id="valor_novo_ponto" class="input">
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
                </form>
            </section>
        </section>
    </main>

    <script>
        function mostrarCamposAjuste() {
            const tipo = document.getElementById('tipo_ajuste').value;
            const ajustePonto = document.getElementById('ajuste_ponto');
            const ajustePausa = document.getElementById('ajuste_pausa');
            ajustePonto.style.display = 'none';
            ajustePausa.style.display = 'none';
            document.getElementById('campo_ponto').required = false;
            document.getElementById('valor_novo_ponto').required = false;
            document.getElementById('id_pausa').required = false;
            document.getElementById('campo_pausa').required = false;
            document.getElementById('pausa_nova').required = false;

            if (tipo === 'ponto') {
                ajustePonto.style.display = 'block';
                document.getElementById('campo_ponto').required = true;
                document.getElementById('valor_novo_ponto').required = true;
            } else if (tipo === 'pausa') {
                ajustePausa.style.display = 'block';
                document.getElementById('id_pausa').required = true;
            }
        }
    </script>
</body>

</html>