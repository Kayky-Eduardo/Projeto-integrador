<?php
$sql = "SELECT * FROM tempo_jornada ORDER BY ativo DESC, descricao ASC";
$result = $conn->query($sql);
$old = $_SESSION['old_jornada'] ?? [];

$erros = $_SESSION['erros'] ?? [];
unset($_SESSION['erros']);
?>

<section class="container">
    <h1 class="page-title">Definir Jornadas de Trabalho</h1>

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

    <form class="form-linha form-config" method="POST">
        <section class="form-linha">
            <input type="hidden" name="acao_jornada" value="criar">

            <article>
                <label class="label">Descrição:</label>
                <input class="input" type="text" name="descricao"
                    value="<?= $old['descricao'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Jornada diária:</label>
                <input class="input" type="time" name="jornada"
                    value="<?= $old['jornada'] ?? '' ?>" required>
            </article>

            <article>
                <label class="label">Limite hora extra:</label>
                <input class="input" type="time" name="hora_extra"
                    value="<?= $old['hora_extra'] ?? '' ?>" required>
            </article>
        </section>

        <section class="form-linha">
            <article>
                <label class="label">Dias da semana:</label>

                <?php
                $dias = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
                foreach ($dias as $i => $dia):
                ?>
                    <label class="label box-config">
                        <input class="input" type="checkbox" name="dias[]" value="<?= $i + 1 ?>"
                            <?= (isset($old['dias']) && in_array($i + 1, $old['dias'])) ? 'checked' : '' ?>>
                        <span><?= ucfirst($dia) ?></span>
                    </label>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-padrao">
                    Salvar configuração
                </button>
            </article>
        </section>
    </form>

    <section class="form-config">
        <select id="filtro-jornada" class="select-padrao">
            <option value="ativos" selected>Ativos</option>
            <option value="inativos">Inativos</option>
            <option value="todos">Todos</option>
        </select>
    </section>

    <section class="tabela-padrao tabela-config">
        <table id="tabela-jornada">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Jornada</th>
                    <th>Hora Extra</th>
                    <th>Dias</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-status="<?= $row['ativo'] ? 'ativo' : 'inativo' ?>">
                        <td><?= htmlspecialchars(ucfirst($row['descricao'])) ?></td>
                        <td><?= $row['jornada'] ?></td>
                        <td><?= $row['maximo_hora_extra'] ?></td>
                        <td>
                            <?php
                            if (!empty($row['dias_semana'])) {

                                $diasMap = [
                                    1 => 'Seg',
                                    2 => 'Ter',
                                    3 => 'Qua',
                                    4 => 'Qui',
                                    5 => 'Sex',
                                    6 => 'Sáb',
                                    7 => 'Dom'
                                ];

                                $dias = json_decode($row['dias_semana'], true);

                                if (is_array($dias)) {
                                    foreach ($dias as $d) {
                                        echo "<span class='badge-dia'>{$diasMap[$d]}</span> ";
                                    }
                                } else {
                                    echo '-';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>

                        <td class="acoes-config">
                            <form method="POST">
                                <input type="hidden" name="id_tempo" value="<?= $row['id_tempo'] ?>">
                                <button class="btn <?= $row['ativo'] ? 'btn-desativar' : 'btn-ativar' ?>" name="acao_jornada_btn" value="<?= $row['ativo'] ? 'desativar' : 'ativar' ?>">
                                    <?= $row['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>

                            <form method="POST">
                                <input type="hidden" name="id_tempo" value="<?= $row['id_tempo'] ?>">
                                <button class="btn btn-excluir" name="acao_jornada_btn" value="excluir">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <tr id="linha-vazia-jornada" style="display: none;">
                    <td colspan="5">Nenhuma jornada encontrada.</td>
                </tr>
            </tbody>
        </table>
    </section>