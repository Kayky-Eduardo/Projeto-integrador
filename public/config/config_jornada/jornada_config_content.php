<?php
$sql = "SELECT * FROM tempo_jornada ORDER BY id_tempo DESC";
$result = $conn->query($sql);
?>

<section class="container">
    <h1 class="page-title">Definir Jornadas de Trabalho</h1>

    <?php if (!empty($_SESSION['msg'])): ?>
        <p><?= htmlspecialchars($_SESSION['msg']); ?></p>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <form class="form-linha form-config" method="POST">
        <section class="form-linha">
            <article>
                <input type="hidden" name="acao_jornada" value="criar">
            </article>

            <article>
                <label class="label">Descrição:</label>
                <input class="input" type="text" name="descricao" required>
            </article>

            <article>
                <label class="label">Jornada diária:</label>
                <input class="input" type="time" name="jornada" required>
            </article>

            <article>
                <label class="label">Limite hora extra:</label>
                <input class="input" type="time" name="hora_extra" required>
            </article>
        </section>

        <section class="form-linha">
            <article>
                <p class="label">Dias da semana:</p>
            </article>

            <article>
                <?php
                $dias = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
                foreach ($dias as $i => $dia):
                ?>
                    <label class="label box-config">
                        <input class="input" type="checkbox" name="dias[]" value="<?= $i + 1 ?>">
                        <span><?= ucfirst($dia) ?></span>
                    </label>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-padrao">
                    Salvar configuração
                </button>
            </article>
        </section>
    </form>

    <section class="tabela-padrao tabela-config">
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Jornada</th>
                    <th>Hora Extra</th>
                    <th>Dias</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
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
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>