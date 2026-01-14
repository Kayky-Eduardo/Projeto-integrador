<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/verificacao.php";
verificar_login($conn);

if (!isset($_GET['id'])) {
    die("Setor não informado.");
}

$id_setor = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM setor WHERE id_setor = ?");
$stmt->bind_param("i", $id_setor);
$stmt->execute();
$setor = $stmt->get_result()->fetch_assoc();

if (!$setor) {
    die("Setor não encontrado.");
}

$usuarios = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");

$stmt = $conn->prepare("SELECT id_usuario FROM grupo_setor WHERE id_setor = ?");
$stmt->bind_param("i", $id_setor);
$stmt->execute();
$res = $stmt->get_result();

$usuarios_no_setor = [];
while ($row = $res->fetch_assoc()) {
    $usuarios_no_setor[] = $row['id_usuario'];
}

if (isset($_POST['salvar_setor'])) {

    $nome_setor = $_POST['nome_setor'];
    $usuarios_selecionados = $_POST['usuarios'] ?? [];

    // Atualiza nome do setor
    $stmt = $conn->prepare("UPDATE setor SET nome_setor = ? WHERE id_setor = ?");
    $stmt->bind_param("si", $nome_setor, $id_setor);
    $stmt->execute();

    // Remove vínculos antigos
    $stmt = $conn->prepare("DELETE FROM grupo_setor WHERE id_setor = ?");
    $stmt->bind_param("i", $id_setor);
    $stmt->execute();

    // Insere novos vínculos
    $stmt = $conn->prepare("INSERT INTO grupo_setor (id_setor, id_usuario) VALUES (?, ?)");

    foreach ($usuarios_selecionados as $id_usuario) {
        $stmt->bind_param("ii", $id_setor, $id_usuario);
        $stmt->execute();
    }

    echo "<p>Setor atualizado com sucesso!</p>";

    function mostrar_usuarios() {
        while ($u = $usuarios->fetch_assoc())
            echo "<label>
                <input type='checkbox'
                    name='usuarios[]'
                    value=" . $u['id_usuario'] .
                    in_array($u['id_usuario'], $usuarios_no_setor) ? 'checked' : '' .
                    "onchange=" . atualizarContador() .">
                    " .htmlspecialchars($u['nome_usuario']) .
            "</label>";
    }
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
                <span id="contador"> <?= count($usuarios_no_setor) ?></span> pessoas
            </div>

            <div class="select-options" id="selectOptions">
                <!-- testando -->
            <button type="button" onclick="<?php mostrar_usuarios() ?>">clicar</button>
                <!-- pra funcionar só tirar o de cima -->
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
