<?php
$result = $conn->query("
    SELECT s.*, 
           COUNT(gs.id_usuario) as total_usuarios
    FROM setor s
    LEFT JOIN grupo_setor gs ON gs.id_setor = s.id_setor
    GROUP BY s.id_setor
");

$erros = $_SESSION['erros'] ?? [];
unset($_SESSION['erros']);
$old = $_SESSION['old_setor'] ?? [];
unset($_SESSION['old_setor']);
?>

<section class="container">
    <h1 class="page-title">Setores</h1>

    <?php if (!empty($erros)): ?>
        <article class="box-erros">
            <strong>Erros encontrados:</strong>
            <ul class="lista-erro erro">
                <?php foreach ($erros as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endif; ?>

    <form method="POST" class="form-config">
        <input type="hidden" name="acao_setor" value="criar">

        <section class="form-linha">
            <article>
                <label class="label">Nome do setor</label>
                <input class="input" type="text" name="nome_setor"
                    value="<?= $old['nome_setor'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Jornada</label>
                <select class="select-padrao" name="id_tempo" required>
                    <option value="">Selecione uma jornada</option>

                    <?php
                    $jornadas = $conn->query("SELECT id_tempo, descricao FROM tempo_jornada");
                    while ($j = $jornadas->fetch_assoc()):
                    ?>

                        <option value="<?= $j['id_tempo'] ?>"
                            <?= ((isset($old['id_tempo']) && $old['id_tempo'] == $j['id_tempo']) ? 'selected' : '') ?>>
                            <?= ucfirst(htmlspecialchars($j['descricao'] ?: 'Sem descrição')) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </article>

            <article>
                <button type="submit" class="btn btn-padrao">
                    Criar setor
                </button>
            </article>
        </section>
    </form>

    <section class="form-config">
        <select id="filtro-setor" class="select-padrao">
            <option value="ativos" selected>Ativos</option>
            <option value="inativos">Inativos</option>
            <option value="todos">Todos</option>
        </select>
    </section>

    <section id="editar-usuarios-setor" class="hidden">
        <section class="form-config">
            <h1 id="titulo-setor" class="page-title">
                Gerenciar Usuários do Setor
            </h1>

            <button id="salvar-edicao-setor" class="btn btn-padrao">Salvar</button>
        </section>

        <section class="listas-setor">
            <article>
                <label class="label">No setor</label>
                <article id="usuarios-no-setor"></article>
            </article>

            <article>
                <label class="label">Sem setor</label>
                <article id="usuarios-sem-setor"></article>
            </article>
        </section>
    </section>

    <section class="tabela-padrao tabela-config">
        <table id="tabela-setores">
            <thead>
                <tr>
                    <th>Nome setor</th>
                    <th>Quantidade de Funcionários</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-status="<?= $row['ativo'] ? 'ativo' : 'inativo' ?>">
                        <td><?= htmlspecialchars($row["nome_setor"]) ?></td>
                        <td><?= $row['total_usuarios'] ?> usuário(s)</td>

                        <td class="acoes-config">
                            <button class="btn btn-padrao btn-editar-setor" data-id="<?= $row['id_setor'] ?>" data-nome="<?= htmlspecialchars($row['nome_setor']) ?>">
                                Gerenciar
                            </button>

                            <form method="POST">
                                <input type="hidden" name="id_setor" value="<?= $row['id_setor'] ?>">

                                <button class="btn <?= $row['ativo'] ? 'btn-desativar' : 'btn-ativar' ?>" name="acao_setor_btn" value="<?= $row['ativo'] ? 'desativar' : 'ativar' ?>">
                                    <?= $row['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>

                            <form method="POST">
                                <input type="hidden" name="id_setor" value="<?= $row['id_setor'] ?>">

                                <button class="btn btn-excluir" name="acao_setor_btn" value="excluir" <?= $row['total_usuarios'] > 0 ? 'disabled' : '' ?>>
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <tr id="linha-vazia" style="display: none;">
                    <td colspan="3" class="text-center">
                        Nenhum registro encontrado.
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</section>