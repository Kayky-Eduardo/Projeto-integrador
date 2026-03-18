<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

// controla qual módulo será carregado
$pagina = $_GET['pagina'] ?? 'jornada';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Configurações</title>
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

        <section class="container">
            <?php
            switch ($pagina) {

                case 'pausas':
                    include(__DIR__ . "/pausa_config_content.php");
                    break;

                case 'jornada':
                default:
            ?>
                    <h1>Definir Jornada de Trabalho</h1>
                    <p>Configure a carga horária e o limite diário de horas extras.</p>

                    <form class="form-linha" id="form-jornada">
                        <label class="label">Jornada diária padrão:</label>
                        <input class="input" type="time" required>

                        <label class="label">Limite de hora extra:</label>
                        <input class="input" type="time" required>

                        <button type="submit" class="btn btn-padrao">
                            Salvar configuração
                        </button>
                    </form>

                    <p id="resposta"></p>
            <?php
                    break;
            }
            ?>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>