<?php
$result = $conn->query("SELECT * FROM cargo");
$erros = $_SESSION['erros'] ?? [];
unset($_SESSION['erros']);
$old = $_SESSION['old_cargo'] ?? [];
unset($_SESSION['old_cargo']);
$max = $_SESSION['nivel'] >= 3 ? 3 : 2;
?>

<section class="container">
    <h1 class="page-title">Cargos</h1>

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
        <input type="hidden" name="acao_cargo" value="criar">

        <section class="form-linha">
            <article>
                <label class="label">Nome do cargo</label>
                <input class="input" type="text" name="nome_cargo" value="<?= $old['nome_cargo'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Salário bruto</label>
                <input class="input" type="number" name="salario" value="<?= $old['salario'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Nível</label>
                <input class="input" type="number" name="nivel" value="<?= $old['nivel'] ?? '' ?>" step="1" max="<?= $max ?>" min="1" required>
            </article>

            <article>
                <button type="submit" class="btn btn-padrao">
                    Cadastrar cargo
                </button>
            </article>
        </section>
    </form>

    <section class="tabela-padrao tabela-config">
        <table id="tabela-cargos">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Salário Bruto</th>
                    <th>Nível</th>
                    <th>Ação</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-status="<?= $row['ativo'] ? 'ativo' : 'inativo' ?>">
                        <td><?= htmlspecialchars($row["nome_cargo"]) ?></td>
                        <td><?= $row["salario_bruto"] ?></td>
                        <td><?= $row["nivel"] ?></td>

                        <td class="acoes-config">
                            <?php if ($_SESSION['nivel'] >= $row['nivel']): ?>
                                <form action="editar_cargo.php" method="GET" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $row['id_cargo'] ?>">
                                    <button class="btn btn-padrao" type="submit">Gerenciar</button>
                                </form>

                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_cargo" value="<?= $row['id_cargo'] ?>">

                                    <button
                                        class="btn <?= $row['ativo'] ? 'btn-desativar' : 'btn-ativar' ?>"
                                        name="acao_cargo_btn"
                                        value="<?= $row['ativo'] ? 'desativar' : 'ativar' ?>">
                                        <?= $row['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>

                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_cargo" value="<?= $row['id_cargo'] ?>">

                                    <button
                                        class="btn btn-excluir"
                                        name="acao_cargo_btn"
                                        value="excluir">
                                        Excluir
                                    </button>
                                </form>

                            <?php else: ?>
                                <span class="text-muted">Sem permissão</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</section>