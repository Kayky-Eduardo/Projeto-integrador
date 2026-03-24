<?php
$listSql = "SELECT * FROM pausa_config ORDER BY ativo DESC, id_config ASC";
$listRes = $conn->query($listSql);
$listSetor = "SELECT * FROM setor";
$setor = $conn->query($listSetor);

function acharGrupo($conn, $id_config)
{
    $sql = "
        SELECT s.nome_setor
        FROM grupo_setor_pausa gs
        LEFT JOIN setor s ON s.id_setor = gs.id_setor
        WHERE gs.id_config = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_config);
    $stmt->execute();
    return $stmt->get_result();
}
?>

<section class="container" id="container-config">
    <h1 class="page-title">Gerenciar Tipos de Pausa</h1>

    <?php if (!empty($_SESSION['msg'])): ?>
        <p><?= htmlspecialchars($_SESSION['msg']); ?></p>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <form class="form-config" method="POST">
        <input type="hidden" name="pausa" value="criar">

        <section class="form-linha">
            <article>
                <label class="label">Descrição:</label>
                <input class="input" type="text" name="descricao" required>
            </article>

            <article>
                <label class="label">Tempo mínimo:</label>
                <input class="input" type="number" name="tempo_min" required>
            </article>

            <article>
                <label class="label">Tempo máximo:</label>
                <input class="input" type="number" name="tempo_max" required>
            </article>

            <article>
                <label class="label">Limite diário:</label>
                <input class="input" type="number" name="limite_pausa_diario" required>
            </article>
        </section>

        <section class="form-linha">
            <article>
                <p class="label">Setores:</p>
            </article>

            <article>
                <?php foreach ($setor as $s): ?>
                    <label class="label box-config">
                        <input type="checkbox" name="setor[]" value="<?= $s['id_setor'] ?>">
                        <span><?= ucfirst($s['nome_setor']) ?></span>
                    </label>
                <?php endforeach; ?>
            </article>

            <article>
                <button type="submit" class="btn btn-padrao">Criar pausa</button>
            </article>
        </section>
    </form>

    <section class="tabela-padrao tabela-config">
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Setores</th>
                    <th>Min</th>
                    <th>Max</th>
                    <th>Limite</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $listRes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucfirst($row['descricao_pausa'])) ?></td>

                        <td>
                            <?php
                            $grupos = acharGrupo($conn, $row['id_config']);
                            foreach ($grupos as $g) {
                                echo htmlspecialchars($g['nome_setor']) . "<br>";
                            }
                            ?>
                        </td>

                        <td><?= $row['tempo_min'] ?></td>
                        <td><?= $row['tempo_max'] ?></td>
                        <td><?= $row['limite_pausa_diario'] == 0 ? "Ilimitado" : $row['limite_pausa_diario'] ?></td>

                        <td>
                            <form method="POST" action="config_pausa/pausa_edit.php">
                                <input type="hidden" name="id_config" value="<?= $row['id_config'] ?>">

                                <button class="btn-link btn-padrao btn-editar-config"  data-id="<?= $row['id_config'] ?>">
                                    Editar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</section>