<?php
session_start(); 
include '../../BD/conexao.php'; 
include '../../include/verificacao.php';


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

        // Validação simples
        if ($descricao === '' || $tempo_min < 0 || $tempo_max < 0) {
            $_SESSION['msg'] = 'Preencha os campos corretamente.';
        } else {
            // Insere no banco
            $sql = "INSERT INTO pausa_config (descricao_pausa, tempo_min, tempo_max)
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $descricao, $tempo_min, $tempo_max);
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

        // Atualiza no banco
        $sql = "UPDATE pausa_config 
                SET descricao_pausa = ?, tempo_min = ?, tempo_max = ?
                WHERE id_config = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siii", $descricao, $tempo_min, $tempo_max, $id);
        $stmt->execute();

        $_SESSION['msg'] = 'Tipo de pausa atualizado.';

        header("Location: pausa_config.php");
        exit;
    }
}


// ----------------------------------------------
// LISTA TODOS OS TIPOS DE PAUSA CADASTRADOS
// ----------------------------------------------
$listSql = "SELECT * FROM pausa_config ORDER BY id_config DESC";
$listRes = $conn->query($listSql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Tipos de Pausa</title>
</head>
<body>
    <a href="relatorio_ponto.php">Voltar</a>

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
        </tr>
    <?php endwhile; ?>

    </tbody>
</table>

</body>
</html>
