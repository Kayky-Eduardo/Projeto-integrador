<?php
session_start(); 
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';
$id_config = $_POST['id_config'] ?? '';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Tipos de Pausa</title>
</head>
<body>
    <a href="pausa_config.php">Voltar</a>

    <h2>Editar Pausa <?= $id_config ?></h2>

    <!-- Formulário para criar novo tipo de pausa -->
    <form method="POST">
        <input type="hidden" name="pausa" value="editar">

        <p>
            <label>Descrição:</label><br>
            <input type="text" name="descricao" required value="<?php $descricao ?>">
            
        </p>

        <p>
            <label>Tempo mínimo (min):</label><br>
            <input type="number" name="tempo_min" required value="<?php $tempo_min ?>">
        </p>

        <p>
            <label>Tempo máximo (min):</label><br>
            <input type="number" name="tempo_max" required value="<?php $tempo_max ?>">
        </p>
        <!-- novo codigin ↓ -->
        <p>
            <label>Limite diário(0 = ilimitado):</label><br>
            <input type="number" name="limite_pausa_diario" required value="<?php $limite_pausa_diario ?>">
        </p>

        <button type="submit">Editar</button>
    </form>

    <hr>

    <!-- Tabela listando as pausas cadastradas -->
    <table border="1" cellpadding="5" hidden>
        <thead>
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Min</th>
                <th>Max</th>
                <th>Limite diário</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>

        <!-- Loop que mostra cada registro ATIVO da tabela -->
        <?php while ($row = $listRes->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_config'] ?></td>
                <td><?= htmlspecialchars($row['descricao']) ?></td>
                <td><?= $row['tempo_min'] ?></td>
                <td><?= $row['tempo_max'] ?></td>
                <td><?php echo (intval($row['limite_pausa_diario']) == 0 ? "ilimitado" : intval($row['limite_pausa_diario'])) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="id_config" value="<?= $row['id_config'] ?>">
                        <button type="submit" name="acao" value="desativar" style="background:none; border:none; color:red; cursor:pointer;">
                            desativar
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        <!-- INATIVOS ↓ -->
        <?php while ($rowInative = $listInative->fetch_assoc()): ?>
            <tr>
                <td><?= $rowInative['id_config'] ?></td>
                <td><?= htmlspecialchars($rowInative['descricao']) ?></td>
                <td><?= $rowInative['tempo_min'] ?></td>
                <td><?= $rowInative['tempo_max'] ?></td>
                <td><?php echo (intval($rowInative['limite_pausa_diario']) == 0 ? "ilimitado" : intval($rowInative['limite_pausa_diario'])) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="id_config" value="<?= $rowInative['id_config'] ?>">
                        <button type="submit" name="acao" value="ativar" style="background:none; border:none; color:green; cursor:pointer;">
                            ativar
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile?>

        </tbody>
    </table>
</body>
</html>