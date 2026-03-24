<?php
$listSql = "SELECT * FROM pausa_config ORDER BY ativo DESC, descricao_pausa ASC";
$listRes = $conn->query($listSql);
$listSetor = "SELECT * FROM setor";
$setor = $conn->query($listSetor);
$old = $_SESSION['old_pausa'] ?? [];

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

$erros = $_SESSION['erros'] ?? [];
unset($_SESSION['erros']);
unset($_SESSION['msg']);
?>

<section class="container" id="container-config">
    <h1 class="page-title">Gerenciar Tipos de Pausa</h1>

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

    <?php if (!empty($_SESSION['msg'])): ?>
        <p><?= htmlspecialchars($_SESSION['msg']); ?></p>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <form class="form-config" method="POST">
        <input type="hidden" name="pausa" value="criar">

        <section class="form-linha">
            <article>
                <label class="label">Descrição:</label>
                <input class="input" type="text" name="descricao"
                    value="<?= $old['descricao'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Tempo mínimo:</label>
                <input class="input" type="number" name="tempo_min"
                    value="<?= $old['tempo_min'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Tempo máximo:</label>
                <input class="input" type="number" name="tempo_max"
                    value="<?= $old['tempo_max'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Limite diário:</label>
                <input class="input" type="number" name="limite_pausa_diario"
                    value="<?= $old['limite_pausa_diario'] ?? '' ?>" required>
            </article>
        </section>

        <section class="form-linha">
            <article>
                <p class="label">Setores:</p>

                <?php foreach ($setor as $s): ?>
                    <label class="label box-config">
                        <input type="checkbox" name="setor[]" value="<?= $s['id_setor'] ?>"
                            <?= (isset($old['setor']) && in_array($s['id_setor'], $old['setor'])) ? 'checked' : '' ?>>
                        <span><?= ucfirst($s['nome_setor']) ?></span>
                    </label>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-padrao">Criar pausa</button>
            </article>
        </section>
    </form>

    <section class="filtro-status-config">
        <select id="filtro-pausa" class="select-padrao">
            <option value="ativos" selected>Ativos</option>
            <option value="inativos">Inativos</option>
            <option value="todos">Todos</option>
        </select>
    </section>

    <section class="tabela-padrao tabela-config">
        <table id="tabela-pausa">
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
                    <tr data-status="<?= $row['ativo'] ? 'ativo' : 'inativo' ?>">
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

                        <td class="acoes-config">
                            <form method="POST">
                                <input type="hidden" name="id_config" value="<?= $row['id_config'] ?>">
                                <button
                                    class="btn <?= $row['ativo'] ? 'btn-desativar' : 'btn-ativar' ?>"
                                    name="acao"
                                    value="<?= $row['ativo'] ? 'desativar' : 'ativar' ?>">
                                    <?= $row['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>

                            <form method="POST">
                                <input type="hidden" name="id_config" value="<?= $row['id_config'] ?>">
                                <button class="btn btn-excluir" name="acao" value="excluir">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <tr id="linha-vazia-pausa" style="display: none;">
                    <td colspan="6">Nenhuma pausa encontrada.</td>
                </tr>
            </tbody>
        </table>
    </section>
</section>