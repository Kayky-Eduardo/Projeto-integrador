<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$setores = $conn->query("SELECT id_setor, nome_setor FROM setor ORDER BY nome_setor");
$jornadas = $conn->query("SELECT * FROM tempo_jornada ORDER BY descricao");

function formatar_dias(array $numeros) {

}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nome_cargo'], $_POST['nivel'], $_POST['salario'])) {

    }
}


?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Configurações</title>
    <?php include("../../include/link.html"); ?>
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="configuracoes">
        <aside aria-label="Menu de configurações">
            <ul class="menu-config">
                <li><a href="#" class="ativo">Definir Jornadas de Trabalho</a></li>
                <li><a href="#">#</a></li>
                <li><a href="#">#</a></li>
                <li><a href="#">#</a></li>
            </ul>
        </aside>

        <section class="container" aria-label="Definição de jornada">
            <h1>Definir jornada por setor:</h1>

            <p id="resposta" role="alert" aria-live="polite" tabindex="0"></p>


            <form class="form-linha">
                    <label class="label" for="set-setores">Setor</label>
                    <select class="select-padrao" name="setor" id="set-setores">
                        <option value="">Selecione um setor:</option>
                            <?php while ($s = $setores->fetch_assoc()): ?>
                                <option value="<?= $s['id_setor'] ?>">
                                <?= $s['nome_setor'] ?>
                            <?php endwhile; ?>
                    </select>

                    <label for="set-setor-jornada">Selecione uma jornada:</label>
                    <select class="select-padrao" name="set-setor-jornada" id="set-setor-jornada">
                        <option value="">Selecione uma jornada</option>
                        <?php while ($j = $jornadas->fetch_assoc()): ?>
                            <option name="<?= $j['id_tempo'] ?>"
                            value="<?= $j['id_tempo'] ?>">
                            <?= $j['descricao'] ?> | <?= $j['jornada'] ?>
                            | <?= $j['maximo_hora_extra'] ?>
                            | <?= $j['dias_semana'] ?>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" class="btn btn-padrao">Aplicar</button>
            </form>
        </section>

    </main>
     <script type="text/javascript">

     </script>
</body>

</html>