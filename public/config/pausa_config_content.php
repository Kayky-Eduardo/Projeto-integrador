<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['pausa'] === 'criar') {
        $descricao = strtolower(trim($_POST['descricao']));
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
        $limite_pausa_diario = intval($_POST['limite_pausa_diario'] ?? 0);

        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['msg'] = 'Preencha os campos corretamente.';
        } else if ($tempo_min >= $tempo_max) {
            $_SESSION['msg'] = 'Tempo máximo deve ser maior que o mínimo.';
        } else if (!isset($_POST['setor'])) {
            $_SESSION['msg'] = 'Selecione um setor.';
        } else {

            try {
                $sql = "INSERT INTO pausa_config 
                        (descricao_pausa, tempo_min, tempo_max, limite_pausa_diario)
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siii", $descricao, $tempo_min, $tempo_max, $limite_pausa_diario);

                if ($stmt->execute()) {
                    $id_config = $conn->insert_id;

                    foreach ($_POST['setor'] as $s) {
                        $id_setor = intval($s);
                        $sql_setor = "INSERT INTO grupo_setor_pausa (id_setor, id_config) VALUES (?, ?)";
                        $stmt_setor = $conn->prepare($sql_setor);
                        $stmt_setor->bind_param("ii", $id_setor, $id_config);
                        $stmt_setor->execute();
                    }

                    $_SESSION['msg'] = 'Tipo de pausa criado com sucesso.';
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $_SESSION['msg'] = 'Erro: Este nome já existe.';
                } else {
                    $_SESSION['msg'] = 'Erro ao salvar.';
                }
            }
        }

        header("Location: config.php?pagina=pausas");
        exit;
    }
}

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

<h1 class="page-title">Gerenciar Tipos de Pausa</h1>

<?php if (!empty($_SESSION['msg'])): ?>
    <p><?= htmlspecialchars($_SESSION['msg']); ?></p>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>

<form class="form-linha" method="POST">
    <input type="hidden" name="pausa" value="criar">

    <label class="label">Descrição:</label>
    <input class="input" type="text" name="descricao" required>

    <label>Tempo mínimo:</label>
    <input type="number" name="tempo_min" required>

    <label>Tempo máximo:</label>
    <input type="number" name="tempo_max" required>

    <label>Limite diário:</label>
    <input type="number" name="limite_pausa_diario" required>

    <p>Setores:</p>
    <?php foreach ($setor as $s): ?>
        <input type="checkbox" name="setor[]" value="<?= $s['id_setor'] ?>">
        <?= $s['nome_setor'] ?><br>
    <?php endforeach; ?>

    <button type="submit">Criar pausa</button>
</form>

<hr>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Descrição</th>
        <th>Setores</th>
        <th>Min</th>
        <th>Max</th>
        <th>Limite</th>
        <th>Ações</th>
    </tr>

    <?php while ($row = $listRes->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_config'] ?></td>
            <td><?= htmlspecialchars($row['descricao_pausa']) ?></td>

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
                <form method="POST" action="../ponto/pausas_e_ponto/pausa_edit.php">
                    <input type="hidden" name="id_config" value="<?= $row['id_config'] ?>">

                    <button class="btn-link btn-padrao" type="submit">
                        Editar
                    </button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>