<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);
$pagina = $_GET['pagina'] ?? 'empresa';
$dias = ['seg', 'ter', 'qua', 'qui', 'sex', 'sáb', 'dom'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // EMPRESA
    if (isset($_POST['acao_empresa'])) {

        if ($_POST['acao_empresa'] === 'editar') {
            $_SESSION['modo_edicao_empresa'] = true;
            header("Location: config.php?pagina=empresa");
            exit;
        }

        if ($_POST['acao_empresa'] === 'salvar') {

            $id_usuario = $_SESSION['id_usuario'];

            $razao = $_POST['razao_social'] ?? '';
            $fantasia = $_POST['nome_fantasia'] ?? '';
            $cnpj = $_POST['cnpj'] ?? '';
            $uf = $_POST['estado'] ?? '';
            $cidade = $_POST['cidade'] ?? '';
            $bairro = $_POST['bairro'] ?? '';
            $numero = $_POST['numero'] ?? '';
            $cep = $_POST['cep'] ?? '';

            if (empty($razao) || empty($cnpj)) {
                $_SESSION['erros'][] = 'Preencha os campos obrigatórios.';
            } else {

                $check = $conn->query("SELECT id_modificador FROM empresas LIMIT 1");

                if ($check->num_rows > 0) {
                    $sql = $conn->prepare("UPDATE empresas SET
                    id_modificador=?,
                    data_modificacao=CURDATE(),
                    razao_social=?, nome_fantasia=?, cnpj=?, uf=?, cidade=?, bairro=?, numero=?, cep=?");

                    $sql->bind_param(
                        "issssssss",
                        $id_usuario,
                        $razao,
                        $fantasia,
                        $cnpj,
                        $uf,
                        $cidade,
                        $bairro,
                        $numero,
                        $cep
                    );
                } else {
                    $sql = $conn->prepare("INSERT INTO empresas
                    (id_modificador, data_modificacao, razao_social, nome_fantasia, cnpj, uf, cidade, bairro, numero, cep)
                    VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)");

                    $sql->bind_param(
                        "issssssss",
                        $id_usuario,
                        $razao,
                        $fantasia,
                        $cnpj,
                        $uf,
                        $cidade,
                        $bairro,
                        $numero,
                        $cep
                    );
                }

                if ($sql->execute()) {
                    unset($_SESSION['modo_edicao_empresa']);
                } else {
                    $_SESSION['erros'][] = 'Erro ao salvar empresa.';
                }
            }

            header("Location: config.php?pagina=empresa");
            exit;
        }
    }

    // PAUSA
    if (isset($_POST['pausa']) && $_POST['pausa'] === 'criar') {

        $descricao = strtolower(trim($_POST['descricao']));
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
        $limite_pausa_diario = intval($_POST['limite_pausa_diario'] ?? 0);

        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['erros'][] = 'Preencha os campos corretamente.';
            $_SESSION['old_pausa'] = $_POST;
        } else if ($tempo_min >= $tempo_max) {
            $_SESSION['erros'][] = 'Tempo máximo deve ser maior que o mínimo.';
            $_SESSION['old_pausa'] = $_POST;
        } else if (!isset($_POST['setor'])) {
            $_SESSION['erros'][] = 'Selecione um setor.';
            $_SESSION['old_pausa'] = $_POST;
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

                    unset($_SESSION['old_pausa']);
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $_SESSION['erros'][] = 'Erro: Este nome já existe.';
                } else {
                    $_SESSION['erros'][] = 'Erro ao salvar.';
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
                $_SESSION['erros'][] = 'Não é possível excluir. Existem registros vinculados a esta pausa.';
            } else {
                $sql = "DELETE FROM pausa_config WHERE id_config = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_config);
                $stmt->execute();
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
            $_SESSION['erros'][] = 'Preencha todos os campos.';
            $_SESSION['old_jornada'] = $_POST;
        } else if (empty($dias)) {
            $_SESSION['erros'][] = 'Selecione ao menos um dia.';
            $_SESSION['old_jornada'] = $_POST;
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
                    unset($_SESSION['old_jornada']);
                }
            } catch (mysqli_sql_exception $e) {
                $_SESSION['erros'][] = 'Erro ao salvar jornada.';
            }
        }

        header("Location: config.php?pagina=jornada");
        exit;
    }

    if (isset($_POST['acao_jornada_btn'])) {

        $id_tempo = intval($_POST['id_tempo']);
        $acao = $_POST['acao_jornada_btn'];

        if ($acao === 'excluir') {
            $sql = "SELECT 1 FROM setor WHERE id_tempo = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_tempo);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                $_SESSION['erros'][] = 'Não é possível excluir. Existem setores vinculados a esta jornada.';
            } else {

                // 🗑️ Pode excluir
                $sql = "DELETE FROM tempo_jornada WHERE id_tempo = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_tempo);
                $stmt->execute();
            }

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        if ($acao === 'ativar' || $acao === 'desativar') {

            $novo_estado = ($acao === 'ativar') ? 1 : 0;

            $sql = "UPDATE tempo_jornada SET ativo = ? WHERE id_tempo = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $novo_estado, $id_tempo);
            $stmt->execute();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
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