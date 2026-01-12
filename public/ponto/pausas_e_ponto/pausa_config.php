<?php
session_start(); 
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';
// Se o formulário for enviado (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ----------------------------------------------
    // CRIA UM NOVO TIPO DE PAUSA
    // ----------------------------------------------
    if ($_POST['acao'] === 'criar') {
        // Pega os dados do formulário
        $descricao = trim($_POST['descricao']);
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
            $limite_pausa_diario = intval($_POST['limite_pausa_diario'] ?? 0);

        // Validação simples
        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['msg'] = 'Preencha os campos corretamente.';
        } else {
            // Insere no banco
                $sql = "INSERT INTO pausa_config (descricao_pausa, tempo_min, tempo_max, limite_pausa_diario)
                    VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siii", $descricao, $tempo_min, $tempo_max, $limite_pausa_diario);
                $stmt->execute();

            $_SESSION['msg'] = 'Tipo de pausa criado.';
        }

        // Atualiza a página para limpar o POST
        header("Location: pausa_config.php");
        exit;
    }

    // ----------------------------------------------
    // EDITA UM TIPO DE PAUSA EXISTENTE
    // ----------------------------------------------
    if ($_POST['acao'] === 'editar') {
        $id = intval($_POST['id_config']);
        $descricao = trim($_POST['descricao']);
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
            $limite_pausa_diario = intval($_POST['limite_pausa_diario'] ?? 0);

        // Atualiza no banco
        $sql = "UPDATE pausa_config 
            SET descricao_pausa = ?, tempo_min = ?, tempo_max = ?, limite_pausa_diario = ?
            WHERE id_config = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiii", $descricao, $tempo_min, $tempo_max, $limite_pausa_diario, $id);
        $stmt->execute();

        $_SESSION['msg'] = 'Tipo de pausa atualizado.';

        header("Location: pausa_config.php");
        exit;
    }
    // altera o estado da pausa para inativo em vez de excluir(soft delete por questão do histórico)
    if ($_POST['acao'] === 'excluir') {
        $id = intval($_POST['id_config']);

        if ($id > 0) {
            // Mudamos de DELETE para UPDATE
            $sql = "UPDATE pausa_config SET ativo = 0 WHERE id_config = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = 'Pausa desativada com sucesso.';
            } else {
                $_SESSION['msg'] = 'Erro ao desativar: ' . $conn->error;
            }
        }

        header("Location: pausa_config.php");
        exit;
    }
}


// ----------------------------------------------
// LISTA TODOS OS TIPOS DE PAUSA CADASTRADOS
// ----------------------------------------------
$listSql = "SELECT * FROM pausa_config WHERE ativo = 1 ORDER BY id_config ASC ";
$listRes = $conn->query($listSql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Tipos de Pausa</title>
</head>
<body>
    <a href="../../index.php">Voltar</a>

    <h2>Gerenciar Tipos de Pausa</h2>

    <!-- Mostra mensagem de sucesso/erro (se existir) -->
    <?php if (!empty($_SESSION['msg'])): ?>
        <p><?= htmlspecialchars($_SESSION['msg']); ?></p>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>


    <!-- Formulário para criar novo tipo de pausa -->
    <form method="POST">
        <input type="hidden" name="acao" value="criar">

        <p>
            <label>Descrição:</label><br>
            <input type="text" name="descricao" required>
        </p>

        <p>
            <label>Tempo mínimo (min):</label><br>
            <input type="number" name="tempo_min" required>
        </p>

        <p>
            <label>Tempo máximo (min):</label><br>
            <input type="number" name="tempo_max" required>
        </p>
        <!-- novo codigin ↓ -->
        <p>
            <label>Limite diário(0 = ilimitado):</label><br>
            <input type="number" name="limite_pausa_diario" required>
        </p>

        <button type="submit">Criar pausa</button>
    </form>

    <hr>

    <!-- Tabela listando as pausas cadastradas -->
    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Min</th>
                <th>Max</th>
                <th>Limite diário</th><!-- novo codigin -->
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>

        <!-- Loop que mostra cada registro da tabela -->
        <?php while ($row = $listRes->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_config'] ?></td>
                <td><?= htmlspecialchars($row['descricao_pausa']) ?></td>
                <td><?= $row['tempo_min'] ?></td>
                <td><?= $row['tempo_max'] ?></td>
                <td><?php echo (intval($row['limite_pausa_diario']) == 0 ? "ilimitado" : intval($row['limite_pausa_diario'])) ?></td><!-- novo codigin -->
                <td>
                    <form method="POST" onsubmit="return confirm('ATENÇÃO: Isso apagará a pausa e seus dados permanentemente. Deseja Continuar?');">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_config" value="<?= $row['id_config'] ?>">
                        <button type="submit" style="background:none; border:none; color:red; cursor:pointer;">
                            deletar
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>

        </tbody>
    </table>
</body>
</html>
