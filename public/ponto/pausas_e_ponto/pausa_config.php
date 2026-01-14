<?php
session_start(); 
include __DIR__ . '/../../../BD/conexao.php';
require __DIR__ . '/../../../include/verificacao.php';
// Se o formulário for enviado (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ----------------------------------------------
    // CRIA UM NOVO TIPO DE PAUSA
    // ----------------------------------------------
    if ($_POST['pausa'] === 'criar') {
        // Pega os dados do formulário
        $descricao = strtolower(trim($_POST['descricao']));
        $tempo_min = intval($_POST['tempo_min']);
        $tempo_max = intval($_POST['tempo_max']);
        $limite_pausa_diario = intval($_POST['limite_pausa_diario'] ?? 0);

        // Validação simples
        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['msg'] = 'Preencha os campos corretamente.';
        }else if ($tempo_min > $tempo_max){
            $_SESSION['msg'] = 'Tempo Máximo deve ser maior que Tempo Mínimo.';
        } else {
            try {
                $sql = "INSERT INTO pausa_config (descricao_pausa, tempo_min, tempo_max, limite_pausa_diario)
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siii", $descricao, $tempo_min, $tempo_max, $limite_pausa_diario);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = 'Tipo de pausa criado.';
                }
            } catch (mysqli_sql_exception $e) {
                // Código 1062 é o erro de Duplicate Entry no MySQL
                if ($e->getCode() === 1062) {
                    $_SESSION['msg'] = '⚠Erro: Este nome já está registrado.';
                } else {
                    $_SESSION['msg'] = 'Erro inesperado ao salvar.';
                }
            }
        }
        // Atualiza a página para limpar o POST
        header("Location: pausa_config.php");
        exit;
    }

    // altera o estado da pausa em vez de excluir(soft delete por questão do histórico)
    if ($_POST['acao']) {
        $ativo = ($_POST['acao'] == 'desativar') ? 0 : 1;
        $id = intval($_POST['id_config']);

        if ($id > 0) {
            // Mudamos de DELETE para UPDATE
            $sql = "UPDATE pausa_config SET ativo = ? WHERE id_config = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $ativo, $id);
            
            if (!$stmt->execute()) {
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

// lista as pausas inativas
$listSql = "SELECT * FROM pausa_config WHERE ativo = 0 ORDER BY id_config ASC ";
$listInative = $conn->query($listSql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Tipos de Pausa</title>
</head>
<body>
    <a href="../../index.php">Voltar</a>
    <a href="teste.php">Teste</a>

    <h2>Gerenciar Tipo de Pausas</h2>

    <!-- Mostra mensagem de sucesso/erro (se existir) -->
    <?php if (!empty($_SESSION['msg'])): ?>
        <p><?= htmlspecialchars($_SESSION['msg']); ?></p>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>


    <!-- Formulário para criar novo tipo de pausa -->
    <form method="POST">
        <input type="hidden" name="pausa" value="criar">

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
                <th>Limite diário</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>

        <!-- Loop que mostra cada registro ATIVO da tabela -->
        <?php while ($row = $listRes->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_config'] ?></td>
                <td><?= htmlspecialchars($row['descricao_pausa']) ?></td>
                <td><?= $row['tempo_min'] ?></td>
                <td><?= $row['tempo_max'] ?></td>
                <td><?php echo (intval($row['limite_pausa_diario']) == 0 ? "ilimitado" : intval($row['limite_pausa_diario'])) ?></td>
                <td>
                    <form method="POST" action="pausa_edit.php">
                        <input type="hidden" name="id_config" value="<?= $row['id_config'] ?>">
                        <button style="background:none; border:none; color:blue; cursor:pointer;">
                            editar
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>

        </tbody>
    </table>
</body>
</html>
