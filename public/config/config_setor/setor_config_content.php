<?php
$result = $conn->query("SELECT * FROM setor");
?>

<section class="container form">
    <h1 class="page-title">Setores</h1>

    <a href="config_setor/cadastro_setor.php" class="btn-link btn-padrao">Novo Setor</a>

    <section class="tabela-padrao tabela-config">
        <table>
            <thead>
                <tr>
                    <th>Nome do Setor</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row["nome_setor"] ?></td>
                            <td>
                                <a class="btn-link btn-padrao" href="config_setor/editar_setor.php?id=<?= $row['id_setor'] ?>">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Nenhum setor cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>