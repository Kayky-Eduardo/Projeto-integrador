<?php
$result = $conn->query("SELECT * FROM setor");
$usuarios = $conn->query("SELECT id_usuario, nome_usuario FROM usuario ORDER BY nome_usuario");
?>

<section class="container">
    <h1 class="page-title">Setores</h1>
    <p id="mensagem"></p>

    <form id="form-setor" class="form-config">
        <section class="form-linha">
            <article>
                <label class="label">Nome do setor</label>
                <input class="input" type="text" id="nome_setor" required>
            </article>

            <article>
                <label class="label">Jornada</label>
                <select class="select-padrao" id="filtro-jornada" required>
                    <option value="">Carregando...</option>
                </select>
            </article>
        </section>

        <section class="form-linha">
            <label class="label">Usuários</label>

            <article>
                <button type="button" class="btn btn-padrao" id="btn-ativar">
                    <span id="contador">0</span> selecionados
                </button>

                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <label class="label box-config">
                        <input type="checkbox" name="usuarios[]" value="<?= $u['id_usuario'] ?>">
                        <span><?= htmlspecialchars($u['nome_usuario']) ?></span>
                    </label>
                <?php endwhile; ?>
            </article>

            <article>
                <button type="submit" class="btn btn-padrao">Criar setor</button>
            </article>
        </section>
    </form>

    <section class="tabela-padrao tabela-config">
        <table>
            <thead>
                <tr>
                    <th>Nome setor</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row["nome_setor"] ?></td>

                            <td>
                                <button>Editar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">Nenhum setor cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</section>