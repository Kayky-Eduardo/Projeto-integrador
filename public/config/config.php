<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
$pagina = $_GET['pagina'] ?? 'jornada';
$dias = ['seg', 'ter', 'qua', 'qui', 'sex', 'sáb', 'dom'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // PAUSA
    if (isset($_POST['pausa']) && $_POST['pausa'] === 'criar') {

        $descricao = strtolower(trim($_POST['descricao']));
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
        $limite_pausa_diario = intval($_POST['limite_pausa_diario'] ?? 0);

        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['msg'] = 'Preencha os campos corretamente.';
        } else if ($tempo_min >= $tempo_max) {
            $_SESSION['msg'] = 'Tempo máximo deve ser maior que o mínimo.';
        } else if (!isset($_POST['setor'])) {
            $_SESSION['msg'] = 'Selecione um setor.';
        } else {

            try {
                $sql = "INSERT INTO pausa_config 
                        (descricao_pausa, tempo_min, tempo_max, limite_pausa_diario)
                        VALUES (?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siii", $descricao, $tempo_min, $tempo_max, $limite_pausa_diario);

                if ($stmt->execute()) {

                    $id_config = $conn->insert_id;

                    foreach ($_POST['setor'] as $s) {
                        $id_setor = intval($s);

                        $sql_setor = "INSERT INTO grupo_setor_pausa (id_setor, id_config) VALUES (?, ?)";
                        $stmt_setor = $conn->prepare($sql_setor);
                        $stmt_setor->bind_param("ii", $id_setor, $id_config);
                        $stmt_setor->execute();
                    }

                    $_SESSION['msg'] = 'Tipo de pausa criado com sucesso.';
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $_SESSION['msg'] = 'Erro: Este nome já existe.';
                } else {
                    $_SESSION['msg'] = 'Erro ao salvar.';
                }
            }
        }

        header("Location: config.php?pagina=pausas");
        exit;
    }

    // JORNADA
    if (isset($_POST['acao_jornada']) && $_POST['acao_jornada'] === 'criar') {
        $descricao = strtolower(trim($_POST['descricao']));
        $jornada = $_POST['jornada'];
        $hora_extra = $_POST['hora_extra'];
        $dias = $_POST['dias'] ?? [];

        if ($descricao === '' || !$jornada || !$hora_extra) {
            $_SESSION['msg'] = 'Preencha todos os campos.';
        } else if (empty($dias)) {
            $_SESSION['msg'] = 'Selecione ao menos um dia.';
        } else {

            try {
                $dias_json = json_encode(array_map('intval', $dias));

                $sql = "INSERT INTO tempo_jornada 
                        (descricao, jornada, maximo_hora_extra, dias_semana)
                        VALUES (?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $descricao, $jornada, $hora_extra, $dias_json);

                if ($stmt->execute()) {
                    $_SESSION['msg'] = 'Jornada criada com sucesso.';
                }
            } catch (mysqli_sql_exception $e) {
                $_SESSION['msg'] = 'Erro ao salvar jornada.';
            }
        }

        header("Location: config.php?pagina=jornada");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Configurações</title>
    <?php include("../../include/link.html"); ?>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="configuracoes">
        <aside aria-label="Menu de configurações">
            <ul class="menu-config">
                <li>
                    <a href="?pagina=jornada" class="<?= $pagina == 'jornada' ? 'ativo' : '' ?>">
                        Definir Jornadas de Trabalho
                    </a>
                </li>
                <li>
                    <a href="?pagina=pausas" class="<?= $pagina == 'pausas' ? 'ativo' : '' ?>">
                        Tipos de Pausa
                    </a>
                </li>
            </ul>
        </aside>

        <section class="pagina-padrao">
            <?php
            switch ($pagina) {

                case 'pausas':
                    include(__DIR__ . "/pausa_config_content.php");
                    break;

                case 'jornada':
                default:
                    include(__DIR__ . "/jornada_config_content.php");
                    break;
            }
            ?>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>