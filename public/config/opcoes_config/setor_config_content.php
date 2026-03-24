<?php
$result = $conn->query("SELECT * FROM setor");
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
                            <?= (isset($old['id_tempo']) && $old['id_tempo'] == $j['id_tempo']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($j['descricao'] ?: 'Sem descrição') ?>
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

    <section class="tabela-padrao tabela-config">
        <table>
            <thead>
                <tr>
                    <th>Nome setor</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row["nome_setor"] ?></td>
                            <td>
                                <button>Editar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">Nenhum setor cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</section>