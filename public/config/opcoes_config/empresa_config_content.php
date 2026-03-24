<?php
$sql_empresa = $conn->prepare("SELECT * FROM empresas");
$sql_empresa->execute();
$empresa = $sql_empresa->get_result()->fetch_assoc();
$modo_edicao = isset($_SESSION['modo_edicao_empresa'])
    ? true
    : empty($empresa);
?>

<section class="container">
    <h1 class="page-title">Dados da Empresa</h1>

    <form clas id="meuForm" method="POST">
        <section class="form-empresa">
            <article>
                <label class="label">Razão Social</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="razao_social"
                        value="<?= $empresa['razao_social'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['razao_social'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

            <article>
                <label class="label">Nome Fantasia</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="nome_fantasia"
                        value="<?= $empresa['nome_fantasia'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['nome_fantasia'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

            <article>
                <label class="label">CNPJ</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="cnpj"
                        value="<?= $empresa['cnpj'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['cnpj'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

            <article>
                <label class="label">UF</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="estado"
                        value="<?= $empresa['uf'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['uf'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

            <article>
                <label class="label">Cidade</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="cidade"
                        value="<?= $empresa['cidade'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['cidade'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

            <article>
                <label class="label">Bairro</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="bairro"
                        value="<?= $empresa['bairro'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['bairro'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

            <article>
                <label class="label">Número</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="numero"
                        value="<?= $empresa['numero'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['numero'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

            <article>
                <label class="label">CEP</label>
                <?php if ($modo_edicao): ?>
                    <input class="input" type="text" name="cep"
                        value="<?= $empresa['cep'] ?? '' ?>">
                <?php else: ?>
                    <p class="info-valor"><?= $empresa['cep'] ?? '-' ?></p>
                <?php endif; ?>
            </article>

        </section>

        <section class="form-linha">
            <button type="submit" class="btn btn-padrao btn-empresa-config" name="acao_empresa" value="<?= $modo_edicao ? 'salvar' : 'editar' ?>">
                <?= $modo_edicao ? 'Salvar' : 'Editar' ?>
            </button>
        </section>
    </form>
</section>