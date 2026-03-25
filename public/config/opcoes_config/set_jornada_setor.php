<?php
require_once __DIR__ . "/../../../include/funcoes/funcoes_jornada.php";
$setores = $conn->query("SELECT id_setor, nome_setor FROM setor ORDER BY nome_setor");
$jornadas = $conn->query("SELECT * FROM tempo_jornada ORDER BY descricao");

$setores_jornadas = $conn->query("
    SELECT 
        s.id_setor,
        s.nome_setor,
        t.descricao AS jornada_descricao,
        t.jornada,
        t.maximo_hora_extra,
        t.dias_semana
    FROM setor s
    LEFT JOIN tempo_jornada t ON s.id_tempo = t.id_tempo
    ORDER BY s.nome_setor
");

function formatar_dias($numeros)
{
    $dias = [
        1 => 'Seg',
        2 => 'Ter',
        3 => 'Qua',
        4 => 'Qui',
        5 => 'Sex',
        6 => 'Sáb',
        7 => 'Dom'
    ];

    if (!$numeros) return '-';

    if (is_string($numeros)) {
        $numeros = json_decode($numeros, true);
    }

    if (!is_array($numeros)) return '-';
    $html = '';

    foreach ($numeros as $num) {
        if (isset($dias[(int)$num])) {
            $html .= "<span class='badge-dia'>{$dias[(int)$num]}</span> ";
        }
    }

    return $html ?: '-';
}

$resposta_exibir = $_SESSION['resposta_jornada'] ?? null;
unset($_SESSION['resposta_jornada']);
?>

<section class="container">
    <h1 class="page-title">Definir Jornada por Setor</h1>

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

    <form class="form-linha form-config" method="POST">
        <section class="form-linha">
            <article>
                <label class="label">Selecione um setor:</label>
                <select class="select-padrao" name="setor" required>
                    <option value="">Selecione</option>
                    <?php while ($s = $setores->fetch_assoc()): ?>
                        <option value="<?= $s['id_setor'] ?>">
                            <?= htmlspecialchars($s['nome_setor']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </article>

            <article>
                <label class="label">Selecione uma jornada:</label>
                <select class="select-padrao" name="set-setor-jornada" required>
                    <option value="">Selecione</option>
                    <?php
                    $jornadas->data_seek(0);
                    while ($j = $jornadas->fetch_assoc()):
                        $dias_formatados = formatar_dias($j['dias_semana']);
                    ?>
                        <option value="<?= $j['id_tempo'] ?>">
                            <?= htmlspecialchars($j['descricao']) ?> |
                            <?= $j['jornada'] ?> |
                            HE: <?= $j['maximo_hora_extra'] ?> |
                            <?= $dias_formatados ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </article>

            <article>
                <button type="submit" class="btn btn-padrao">
                    Aplicar
                </button>
            </article>
        </section>
    </form>

    <section class="tabela-padrao tabela-config">
        <table>
            <thead>
                <tr>
                    <th>Setor</th>
                    <th>Descrição</th>
                    <th>Jornada</th>
                    <th>Hora Extra</th>
                    <th>Dias</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($setores_jornadas->num_rows > 0): ?>
                    <?php while ($sj = $setores_jornadas->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sj['nome_setor']) ?></strong></td>
                            <td><?= ucfirst(htmlspecialchars($sj['jornada_descricao'])) ?></td>
                            <td><?= substr($sj['jornada'], 0, 5) ?></td>
                            <td><?= substr($sj['maximo_hora_extra'], 0, 5) ?></td>
                            <td><?= formatar_dias($sj['dias_semana']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Nenhum setor cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>