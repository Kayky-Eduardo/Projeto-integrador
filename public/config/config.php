<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
$pagina = $_GET['pagina'] ?? 'empresa';
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

        $id_config = intval($_POST['id_config']);
        $acao = $_POST['acao'];

        if ($acao === 'excluir') {

            $sql = "SELECT 1 FROM pausa WHERE id_config = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_config);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $_SESSION['msg'] = 'Erro: Não é possível excluir esta pausa.';
            } else {
                $sql = "DELETE FROM pausa_config WHERE id_config = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_config);
                $stmt->execute();

                $_SESSION['msg'] = 'Pausa excluída com sucesso.';
            }

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        if ($acao === 'ativar' || $acao === 'desativar') {

            $novo_estado = ($acao === 'ativar') ? 1 : 0;

            $sql = "UPDATE pausa_config SET ativo = ? WHERE id_config = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $novo_estado, $id_config);
            $stmt->execute();

            $_SESSION['msg'] = 'Status atualizado com sucesso.';

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
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
                    $_SESSION['msg'] = '';
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
                    <a href="?pagina=empresa" class="<?= $pagina == 'empresa' ? 'ativo' : '' ?>">
                        Dados da Empresa
                    </a>
                </li>

                <li>
                    <a href="?pagina=jornada" class="<?= $pagina == 'jornada' ? 'ativo' : '' ?>">
                        Jornadas de Trabalho
                    </a>
                </li>

                <li>
                    <a href="?pagina=pausas" class="<?= $pagina == 'pausas' ? 'ativo' : '' ?>">
                        Tipos de Pausa
                    </a>
                </li>

                <li>
                    <a href="?pagina=setores" class="<?= $pagina == 'setores' ? 'ativo' : '' ?>">
                        Setores
                    </a>
                </li>
            </ul>
        </aside>

        <section class="pagina-padrao pagina-config">
            <?php
            switch ($pagina) {
                case 'empresa':
                    include(__DIR__ . "/opcoes_config/empresa_config_content.php");
                    break;

                case 'pausas':
                    include(__DIR__ . "/opcoes_config/pausa_config_content.php");
                    break;

                case 'jornada':
                default:
                    include(__DIR__ . "/opcoes_config/jornada_config_content.php");
                    break;

                case 'setores':
                    include(__DIR__ . "/opcoes_config/setor_config_content.php");
                    break;
            }
            ?>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>