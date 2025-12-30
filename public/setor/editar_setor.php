<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

/* ===============================
   BUSCA DO SETOR
=================================*/
if (!isset($_GET['id'])) {
    die("Setor não informado.");
}

$id_setor = intval($_GET['id']);

$sqlSetor = "SELECT * FROM setor WHERE id_setor = ?";
$stmt = $conn->prepare($sqlSetor);
$stmt->bind_param("i", $id_setor);
$stmt->execute();
$setor = $stmt->get_result()->fetch_assoc();

if (!$setor) {
    die("Setor não encontrado.");
}

/* ===============================
   BUSCA TODOS OS USUÁRIOS
=================================*/
$sqlUsuarios = "SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario";
$usuarios = $conn->query($sqlUsuarios);

/* ===============================
   BUSCA USUÁRIOS JÁ NO SETOR
=================================*/
$sqlVinculos = "SELECT id_usuario FROM grupo_setor WHERE id_setor = ?";
$stmt = $conn->prepare($sqlVinculos);
$stmt->bind_param("i", $id_setor);
$stmt->execute();
$res = $stmt->get_result();

$usuarios_no_setor = [];
while ($row = $res->fetch_assoc()) {
    $usuarios_no_setor[] = $row['id_usuario'];
}

/* ===============================
   SALVAR ALTERAÇÕES
=================================*/
if (isset($_POST['salvar_setor'])) {

    $nome_setor = $_POST['nome_setor'];
    $usuarios_selecionados = $_POST['usuarios'] ?? [];

    // Atualiza nome do setor
    $sqlUpdate = "UPDATE setor SET nome_setor = ? WHERE id_setor = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("si", $nome_setor, $id_setor);
    $stmt->execute();

    // Remove vínculos antigos
    $sqlDelete = "DELETE FROM grupo_setor WHERE id_setor = ?";
    $stmt = $conn->prepare($sqlDelete);
    $stmt->bind_param("i", $id_setor);
    $stmt->execute();

    // Insere novos vínculos
    $sqlInsert = "INSERT INTO grupo_setor (id_setor, id_usuario) VALUES (?, ?)";
    $stmt = $conn->prepare($sqlInsert);

    foreach ($usuarios_selecionados as $id_usuario) {
        $stmt->bind_param("ii", $id_setor, $id_usuario);
        $stmt->execute();
    }

    echo "<p>Setor atualizado com sucesso!</p>";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Setor</title>
</head>
<body>
    <header>
        <?php include("../../include/navbar.php");?>
    </header>

    <h2>Editar Setor</h2>

    <form method="POST">

        <label>Nome do Setor:</label><br>
        <input type="text" name="nome_setor"
            value="<?= htmlspecialchars($setor['nome_setor']) ?>"
            required>
        <br><br>


        <div class="select-box">
            <div class="select-header" onclick="toggleSelect()">
                <span id="contador">
                    <?= count($usuarios_no_setor) ?>
                </span> pessoas
            </div>

            <div class="select-options" id="selectOptions">

                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <label>
                        <input type="checkbox"
                            name="usuarios[]"
                            value="<?= $u['id_usuario'] ?>"
                            <?= in_array($u['id_usuario'], $usuarios_no_setor) ? 'checked' : '' ?>
                            onchange="atualizarContador()">
                        <?= htmlspecialchars($u['nome_usuario']) ?>
                    </label>
                <?php endwhile; ?>

            </div>
        </div>

        <br>
        <button type="submit" name="salvar_setor">Salvar</button>
        <a href="setores.php">Voltar</a>

    </form>
<script>
function toggleSelect() {
    const box = document.getElementById('selectOptions');
    box.style.display = box.style.display === 'block' ? 'none' : 'block';
}

function atualizarContador() {
    const checkboxes = document.querySelectorAll(
        'input[name="usuarios[]"]:checked'
    );
    document.getElementById('contador').innerText = checkboxes.length;
}
</script>
</body>
</html>
