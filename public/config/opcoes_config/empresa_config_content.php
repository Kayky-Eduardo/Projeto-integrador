<?php
$sql_empresa = $conn->prepare("SELECT * FROM empresas");
$sql_empresa->execute();
$empresa = $sql_empresa->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if ($dados) {
        $id_usuario = $dados['id_usuario'] ?? null;

        if ($dados['acao'] === 'salvar') {
            $d = $dados['dicionario'];

            if ($empresa) {
                $sql = $conn->prepare("UPDATE empresas SET
                    id_modificador = ?,
                    data_modificacao = CURDATE(),
                    razao_social = ?,
                    nome_fantasia = ?,
                    cnpj = ?,
                    uf = ?,
                    cidade = ?,
                    bairro = ?,
                    numero = ?,
                    cep = ?
                    WHERE id_modificador = ?");

                $sql->bind_param(
                    'issssssssi',
                    $id_usuario,
                    $d['razao_social'],
                    $d['nome_fantasia'],
                    $d['cnpj'],
                    $d['estado'],
                    $d['cidade'],
                    $d['bairro'],
                    $d['numero'],
                    $d['cep'],
                    $id_usuario
                );
            } else {
                $sql = $conn->prepare("INSERT INTO empresas (
                    id_modificador, data_modificacao,
                    razao_social, nome_fantasia, cnpj,
                    uf, cidade, bairro, numero, cep
                ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)");

                $sql->bind_param(
                    'issssssss',
                    $id_usuario,
                    $d['razao_social'],
                    $d['nome_fantasia'],
                    $d['cnpj'],
                    $d['estado'],
                    $d['cidade'],
                    $d['bairro'],
                    $d['numero'],
                    $d['cep']
                );
            }

            if ($sql->execute()) {
                echo json_encode(['status' => 'sucesso', 'msg' => 'Salvo com sucesso']);
            } else {
                echo json_encode(['status' => 'erro', 'msg' => 'Erro ao salvar']);
            }
            exit;
        }

        if ($dados['acao'] === 'excluir') {
            $conn->query("DELETE FROM empresas");
            echo json_encode(['status' => 'sucesso', 'msg' => 'Excluído']);
            exit;
        }
    }
}
?>

<section class="container">
    <h1 class="page-title">Dados da Empresa</h1>

    <form class="form" id="meuForm">
        <section class="form">
            <article>
                <label class="label">Razão Social</label>
                <input class="input" type="text" name="razao_social" value="<?= $empresa['razao_social'] ?? '' ?>" placeholder="Razão Social">
            </article>
            <article>
                <label class="label">Nome Fantasia</label>
                <input class="input" type="text" name="nome_fantasia" value="<?= $empresa['nome_fantasia'] ?? '' ?>" placeholder="Nome Fantasia">
            </article>
            <article>
                <label class="label">CNPJ</label>
                <input class="input" type="text" name="cnpj" value="<?= $empresa['cnpj'] ?? '' ?>" placeholder="CNPJ">
            </article>
            <article>
                <label class="label">UF</label>
                <input class="input" type="text" name="estado" value="<?= $empresa['uf'] ?? '' ?>" placeholder="UF">
            </article>

            <article>
                <label class="label">Cidade</label>
                <input class="input" type="text" name="cidade" value="<?= $empresa['cidade'] ?? '' ?>" placeholder="Cidade">
            </article>

            <article>
                <label class="label">Bairro</label>
                <input class="input" type="text" name="bairro" value="<?= $empresa['bairro'] ?? '' ?>" placeholder="Bairro">
            </article>

            <article>
                <label class="label">Número</label>
                <input class="input" type="text" name="numero" value="<?= $empresa['numero'] ?? '' ?>" placeholder="Número">
            </article>

            <article>
                <label class="label">CEP</label>
                <input class="input" type="text" name="cep" value="<?= $empresa['cep'] ?? '' ?>" placeholder="CEP">
            </article>
        </section>

        <section class="form-linha">
            <button class="btn btn-padrao btn-salvar" data-id="<?= $_SESSION['id_usuario'] ?>">Salvar</button>
            <button class="btn btn-excluir">Excluir</button>
        </section>
    </form>
</section>