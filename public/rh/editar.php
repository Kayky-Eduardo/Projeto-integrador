<?php
session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID inválido.");
}

$id = intval($_GET['id']);

$sql = "
    SELECT 
        a.campo,
        a.motivo,
        a.id_ajuste,
        a.id_ponto,
        a.id_pausa,
        ps.inicio as inicio_pausa,
        ps.fim as fim_pausa,
        ps.id_pausa,
        p.inicio_ponto,
        p.fim_ponto,
        p.data_ponto,
        u.nome_usuario
    FROM ajustes_ponto a
    INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
    LEFT JOIN pausa ps ON ps.id_pausa = a.id_pausa
    WHERE a.id_ajuste = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$ajuste = $stmt->get_result()->fetch_assoc();

if (!$ajuste) {
    die("Ajuste não encontrado." . $id);
}


$campo_editavel = $ajuste['campo'];

function desabilitar($nomeCampo, $campoEditavel)
{
    return ($nomeCampo !== $campoEditavel) ? 'readonly disabled' : '';
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Editar Ajuste</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Editar horário solicitado</h1>

            <section class="container solicitar-ajuste">
                <form method="post" action="salvar.php" class="form">
                    <input type="hidden" name="id_ajuste" value="<?= $ajuste['id_ajuste'] ?>">
                    <input type="hidden" name="id_pausa" value="<?= $ajuste['id_pausa'] ?>">
                    <input type="hidden" name="id_ponto" value="<?= $ajuste['id_ponto'] ?>">
                    <input type="hidden" name="campo" value="<?= $campo_editavel ?>">

                    <article>
                        <p class="label">Funcionário: <?= htmlspecialchars($ajuste['nome_usuario']) ?></p>
                        <p class="label">
                            Data: <?= date('d-m-Y', strtotime($ajuste['data_ponto'])) ?>
                        </p>
                    </article>

                    <article>
                        <label class="label">Entrada:</label>
                        <input class="input" type="time" name="inicio_ponto" value="<?= date('H:i', strtotime($ajuste['inicio_ponto'])) ?>" <?= desabilitar('inicio_ponto', $campo_editavel) ?>>
                    </article>

                    <article>
                        <label class="label">Início Pausa:</label>
                        <input class="input" type="time" name="inicio_pausa" value="<?= date('H:i', strtotime($ajuste['inicio_pausa'])) ?>" <?= desabilitar('inicio_pausa', $campo_editavel) ?>>
                    </article>

                    <article>
                        <label class="label">Fim Pausa:</label>
                        <input class="input" type="time" name="fim_pausa" value="<?= date('H:i', strtotime($ajuste['fim_pausa'])) ?>" <?= desabilitar('fim_pausa', $campo_editavel) ?>>
                    </article>

                    <article>
                        <label class="label">Saída:</label>
                        <input class="input" type="time" name="fim_ponto" value="<?= date('H:i', strtotime($ajuste['fim_ponto'])) ?>" <?= desabilitar('fim_ponto', $campo_editavel) ?>>
                    </article>

                    <article>
                        <label class="label">Justificativa:</label>
                        <textarea class="input" name="motivo" rows="4" cols="50" placeholder="Escreva aqui a justificativa..."><?= htmlspecialchars($ajuste['motivo']) ?></textarea>
                    </article>

                    <button class="btn btn-padrao" type="submit">Salvar Ajuste</button>
                </form>
            </section>
        </section>
    </main>
</body>

</html>