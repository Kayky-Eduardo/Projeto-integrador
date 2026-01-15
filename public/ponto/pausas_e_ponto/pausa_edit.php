<?php
session_start(); 
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';

// 1. Definição do ID (prioriza POST para ações, ou GET se você mudar o link na tabela)
$id_config = intval($_POST['id_config'] ?? 0);

if ($id_config <= 0) {
    header("Location: pausa_config.php");
    exit;
}

// 2. Processamento das Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'salvar') {
        $descricao = trim($_POST['descricao']);
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
        $limite_pausa_diario = intval($_POST['limite_pausa_diario']);

        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['msg'] = 'Preencha os campos corretamente.';
        } else if ($tempo_min >= $tempo_max) {
            $_SESSION['msg'] = 'Tempo Máximo deve ser maior que Tempo Mínimo.';
        } else {
            try {
                $sql = "UPDATE pausa_config SET descricao_pausa = ?, tempo_min = ?, tempo_max = ?, limite_pausa_diario = ? WHERE id_config = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siiii", $descricao, $tempo_min, $tempo_max, $limite_pausa_diario, $id_config);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = 'Pausa atualizada com sucesso.';
                    header("Location: pausa_config.php"); // Volta para a lista após salvar
                    exit;
                }
            } catch (mysqli_sql_exception $e) {
                $_SESSION['msg'] = ($e->getCode() === 1062) ? 'Erro: Nome já existe.' : 'Erro ao salvar.';
            }
        }
    } else if($acao === 'excluir'){

        $sql = "SELECT 1 FROM pausa WHERE id_config = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_config);
        if ($stmt->execute()){
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $_SESSION['msg'] = 'Erro: Não é possível excluir esta pausa, pois possui registros.';
                header("Location: pausa_edit.php?id_config=" . $id_config);
                exit;
            } else {
                $sql = "DELETE FROM pausa_config WHERE id_config = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_config);
                if ($stmt->execute()) {
                    $_SESSION['msg'] = 'Pausa excluída com sucesso.';
                    header("Location: pausa_config.php");
                    exit;
                } else {
                    $_SESSION['msg'] = 'Erro ao excluir a pausa.';
                    header("Location: pausa_edit.php?id_config=" . $id_config);
                    exit;
                }
            }
        }
    }

    if ($acao === 'ativar' || $acao === 'desativar') {
        $novo_estado = ($acao === 'ativar') ? 1 : 0;
        $sql = "UPDATE pausa_config SET ativo = ? WHERE id_config = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $novo_estado, $id_config);
        $stmt->execute();
        header("Location: pausa_config.php");
        exit;
    }

}

// 3. Busca de dados para preencher o formulário
$sql = "SELECT * FROM pausa_config WHERE id_config = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_config);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header("Location: pausa_config.php");
    exit;
}

$status_acao = ($row['ativo'] == 0 ? 'ativar' : 'desativar');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Editar Pausa</title>
</head>
<body>
    <a href="pausa_config.php">Voltar</a>
    <h2>Editar Pausa</h2>

    <?php if (!empty($_SESSION['msg'])): ?>
        <p><strong><?= htmlspecialchars($_SESSION['msg']); ?></strong></p>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id_config" value="<?= $id_config ?>">

        <p>
            <label>Descrição:</label><br>
            <input type="text" name="descricao" required value="<?= htmlspecialchars($row['descricao_pausa']) ?>">
        </p>

        <p>
            <label>Tempo mínimo (min):</label><br>
            <input type="number" name="tempo_min" required value="<?= $row['tempo_min'] ?>">
        </p>

        <p>
            <label>Tempo máximo (min):</label><br>
            <input type="number" name="tempo_max" required value="<?= $row['tempo_max'] ?>">
        </p>
        
        <p>
            <label>Limite diário:</label><br>
            <input type="number" name="limite_pausa_diario" required value="<?= $row['limite_pausa_diario'] ?>">
        </p>

        <button type="submit" name="acao" value="salvar">Salvar Alterações</button>
        <button type="submit" name="acao" value="<?= $status_acao ?>"><?= ucfirst($status_acao) ?></button>
        <button type="submit" name="acao" value="excluir">Excluir</button>
    </form>
</body>
</html>