<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

$setores = $conn->query("SELECT id_setor, nome_setor FROM setor ORDER BY nome_setor");
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
            <h1>Definir Jornada de Trabalho</h1>
            <p>Configure a carga horária e o limite diário de horas extras.</p>

            <form class="form-linha" id="form-jornada">
                <label class="label" for="set-descricao">Descrição da jornada:</label>
                <input class="input" id="set-descricao" type="text" placeholder="Descrição da jornada" required>

                <label class="label" for="set-jornada">Jornada diária padrão:</label>
                <input class="input" id="set-jornada" type="time" required>

                <label class="label" for="set-hora-max">Limite de hora extra:</label>
                <input class="input" id="set-hora-max" type="time" required>

                <button type="submit" class="btn btn-padrao">Salvar configuração</button>
            </form>

            <p id="resposta" role="alert" aria-live="polite" tabindex="0"></p>
        </section>
        <!-- 
            <select name="usuario">
                <option value="">-- Todos --</option>
                </*?php while ($u = $users->fetch_assoc()): ?>
                    <option value="<//?= $u['id_usuario'] ?>"
                    <//?= ($u['id_usuario'] == $filtroUser) ? 'selected' : '' ?>>
                    <//?= $u['nome_usuario'] ?> (ID: <//?= $u['id_usuario'] ?>)
                </option>
                <//?php endwhile; ?>
            </select>
            -->
        <select name="setor" id="set-setores">
            <option value="">Selecione um setor</option>
            <?php while ($s = $setor->fetch_assoc()): ?>
                
        </select>
        <section>
            <form class="form-linha">
                <label class="label" for="set-setor">Setor</label>
                
            </form>
        </section>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>

</html>