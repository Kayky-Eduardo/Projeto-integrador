<?php
session_start(); 
include __DIR__ . '/../../BD/conexao.php';
require __DIR__ . '/../../include/verificacao.php';
verificar_login($conn);
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
        }else if ($tempo_min > $tempo_max  || $tempo_max === $tempo_min){
            $_SESSION['msg'] = 'Tempo Máximo deve ser maior que Tempo Mínimo.';
        } else if (!isset($_POST['setor'])){
            $_SESSION['msg'] = 'Selecione um Setor.';
        } else {
            try {
                $sql = "INSERT INTO pausa_config (descricao_pausa, tempo_min, tempo_max, limite_pausa_diario)
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siii", $descricao, $tempo_min, $tempo_max, $limite_pausa_diario);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = 'Tipo de pausa criado.';
                    $id_config = $conn->insert_id; // insert_id pega o último id INSERT(update ou delete não funciona)

                    // Setor
                    if (isset($_POST['setor']) && is_array($_POST['setor'])) {
                        foreach ($_POST['setor'] as $s) {
                            $id_setor = intval($s);
                            $sql_setor = "INSERT INTO grupo_setor_pausa (id_setor, id_config) VALUES (?, ?)";
                            $stmt_setor = $conn->prepare($sql_setor);
                            $stmt_setor->bind_param("ii", $id_setor, $id_config);
                            $stmt_setor->execute();
                        }
                    }
                    $_SESSION['msg'] = 'Tipo de pausa e setores configurados com sucesso.';
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
        header("Location: pausa_config.php");
        exit;
    }
}


// ----------------------------------------------
// LISTA TODOS OS TIPOS DE PAUSA CADASTRADOS
// ----------------------------------------------
$listSql = "SELECT * FROM pausa_config ORDER BY ativo DESC, id_config ASC";
$listRes = $conn->query($listSql);

$listSetor = "SELECT * FROM setor";
$setor = $conn->query($listSetor);

function acharGrupo($conn, $id_config){
    $listGrupoSetor = "
    SELECT *,
    s.nome_setor,
    s.id_setor
    FROM grupo_setor_pausa gs
    LEFT JOIN setor s on s.id_setor = gs.id_setor
    WHERE gs.id_config = ?";
    $listGrupoSetor = $conn->prepare($listGrupoSetor);
    $listGrupoSetor->bind_param("i", $id_config);
    $listGrupoSetor->execute();
    $result = $listGrupoSetor->get_result();
    return $result;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Tipos de Pausa</title>
</head>
<body>
    <a href="../index.php">Voltar</a>

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

        <p>
            <!-- name="setor[]" o "[]" serve para que o php identifique que se trata de valores múltiplos -->
            Selecione Setor:<br>
            <?php foreach($setor as $s): ?>
                <input type="checkbox" id="<?= $s['nome_setor'] ?>" name="setor[]" value="<?= $s['id_setor'] ?>">
                <label for="<?= $s['nome_setor'] ?>"><?= $s['nome_setor'] ?></label><br>
            <?php endforeach;?>
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
                <th>Setores</th>
                <th>Min</th>
                <th>Max</th>
                <th>Limite diário</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>

        <!-- Loop que mostra cada registro da tabela -->
        <?php while ($row = $listRes->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_config'] ?></td>
                <td><?= htmlspecialchars($row['descricao_pausa']) ?></td>
                <td>
                    <?php $setor = acharGrupo($conn, $row['id_config']); foreach($setor as $gs):?>
                    <p><?= $gs['nome_setor'] ?></p>
                <?php endforeach;?>
                </td>
                <td><?= $row['tempo_min'] ?></td>
                <td><?= $row['tempo_max'] ?></td>
                <td><?php echo (intval($row['limite_pausa_diario']) == 0 ? "ilimitado" : intval($row['limite_pausa_diario'])) ?></td>
                <td>
                    <form method="POST" action="config_pausa/pausa_edit.php">
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
