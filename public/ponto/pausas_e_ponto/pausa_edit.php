<?php
session_start(); 
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';
$id_config = $_POST['id_config'] ?? '';
echo $id_config;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    // ----------------------------------------------
    // EDITA UM TIPO DE PAUSA
    // ----------------------------------------------
    if ($_POST['acao'] === 'salvar') {
        $id = intval($_POST['id_config']);
        $descricao = trim($_POST['descricao']);
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
        $limite_pausa_diario = intval($_POST['limite_pausa_diario']);

        // Validação simples
        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['msg'] = 'Preencha os campos corretamente.';
        }else if ($tempo_min > $tempo_max){
            $_SESSION['msg'] = 'Tempo Máximo deve ser maior que Tempo Mínimo.';
        } else {
            try {
                // Atualiza no banco
                $sql = "UPDATE pausa_config
                        SET descricao_pausa = ?, tempo_min = ?, tempo_max = ?
                        WHERE id_config = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siii", $descricao, $tempo_min, $tempo_max, $id);
                $stmt->execute();
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = 'Tipo de pausa editado.';
                }
            } catch (mysqli_sql_exception $e) {
                // Código 1062 é o erro de Duplicate Entry no MySQL
                if ($e->getCode() === 1062) {
                    $_SESSION['msg'] = 'Erro: Este nome já está registrado.';
                } else {
                    $_SESSION['msg'] = 'Erro inesperado ao salvar.';
                }
            }
        }
        // Atualiza a página para limpar o POST
        header("Location: pausa_edit.php");
        exit;
    }

    // altera o estado da pausa em vez de excluir(soft delete por questão do histórico)
    if ($_POST['acao'] === 'desativar' || $_POST['acao'] === 'ativar') {
        $ativo = ($_POST['acao'] == 'desativar') ? 0 : 1;
        $id = intval($_POST['id_config']);

        if ($id > 0) {
            $sql = "UPDATE pausa_config SET ativo = ? WHERE id_config = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $ativo, $id);
            
            if (!$stmt->execute()) {
                $_SESSION['msg'] = 'Erro ao desativar: ' . $conn->error;
            }
        }

        echo "<script>Alert('Pausa Atualizada com Sucesso.')</script>";
        header("Location: pausa_edit.php");
        exit;
    }
}
// pega os dados da pausa escolhida para editar
$row = null;

if (!empty($id_config)) {
    $sql = "SELECT * FROM pausa_config WHERE id_config = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_config);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
}

if (!$row) {
    // não existe pausa válida → redireciona
    header("Location: pausa_edit.php");
    exit;
}

$ativo = ($row['ativo'] == 0 ? 'ativar' : 'desativar');

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
    <form method="POST" name="acao">
        <input type="hidden" name="acao" value="salvar">

        <p>
            <label>Descrição:</label><br>
            <input type="text" name="descricao" required value="<?= $row['descricao_pausa'] ?>">
            
        </p>

        <p>
            <label>Tempo mínimo (min):</label><br>
            <input type="number" name="tempo_min" required value="<?= $row['tempo_min'] ?>">
        </p>

        <p>
            <label>Tempo máximo (min):</label><br>
            <input type="number" name="tempo_max" required value="<?= $row['tempo_max'] ?>">
        </p>
        <!-- novo codigin ↓ -->
        <p>
            <label>Limite diário(0 = ilimitado):</label><br>
            <input type="number" name="limite_pausa_diario" required value="<?= $row['limite_pausa_diario'] ?>">
        </p>

        <button type="submit" name="acao" value="salvar">Salvar</button>
        <button type="submit" name="acao" value="<?php $ativo ?>"><?= ucfirst($ativo) ?></button>
        <button type="submit" name="acao" value="excluir">Excluir</button>
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
                        <button type="submit" name="acao" value="desativar">
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
                        <button type="submit" name="acao" value="ativar">
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