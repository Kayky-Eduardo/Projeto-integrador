<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/funcoes/funcoes_calculo.php";

// --------------------------
// 1. Recebe mês do formulário
// --------------------------
$mes = date('Y-m'); // default: mês atual
$mes_padrao = $mes . "-01";

// --------------------------
// 2. Buscar todos os usuários ativos
// --------------------------
$sql = "SELECT id_usuario, nome_usuario 
        FROM usuario 
        WHERE conta_ativa = 1";
$result = $conn->query($sql);
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

// --------------------------
// 3. Adicionar evento (form separado)
// --------------------------
$mensagem_evento = '';
if (isset($_POST['add_evento'])) {
    $id_usuario_evento = $_POST['id_usuario_evento'] ?? null;
    $tipo = $_POST['tipo'] ?? '';
    $descricao = $_POST['descricao'] ?? 'Não Informado';
    $valor = floatval($_POST['valor'] ?? 0);

    if ($id_usuario_evento && $tipo && $valor > 0) {
        $stmt = $conn->prepare("
            INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issds", $id_usuario_evento, $tipo, $descricao, $valor, $mes_padrao);
        $stmt->execute();
        $mensagem_evento = "Evento adicionado com sucesso!";
    } else {
        $mensagem_evento = "Preencha todos os campos corretamente.";
    }
}

// --------------------------
// 4. Função para gerar folha de um usuário
// --------------------------
function gerarFolhaUsuario(array $usuario, string $mes_padrao, $conn) {
    $id_usuario = $usuario['id_usuario'];

    // --- Buscar salário ---
    $stmt = $conn->prepare("
        SELECT u.nome_usuario, c.salario_bruto 
        FROM usuario u
        JOIN cargo c ON c.id_cargo = u.id_cargo
        WHERE u.id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    if (!$userData) return null;

    $salario_bruto = floatval($userData['salario_bruto']);

    // --- Buscar eventos ---
    $stmt2 = $conn->prepare("
        SELECT tipo, valor 
        FROM eventos 
        WHERE id_usuario = ? AND mes_competencia = ?
    ");
    $stmt2->bind_param("is", $id_usuario, $mes_padrao);
    $stmt2->execute();
    $resEventos = $stmt2->get_result();

    $total_proventos = 0;
    $total_descontos = 0;
    while ($evt = $resEventos->fetch_assoc()) {
        if ($evt["tipo"] === "provento") $total_proventos += floatval($evt["valor"]);
        else $total_descontos += floatval($evt["valor"]);
    }

    // --- Cálculos legais ---
    $fgts = calcularFGTS($salario_bruto);
    $inss = calcularINSS($salario_bruto);
    $irrf = calcularIRRF($salario_bruto, $inss, 0);
    $vt = calcularVT($salario_bruto, 300);
    $total_descontos += $inss + $irrf + $vt;

    $salario_liquido = calcularSalarioLiquido($salario_bruto, $total_proventos, $total_descontos);

    // --- Salvar no banco ---
    // Dentro da função gerarFolhaUsuario
    $stmtCheck = $conn->prepare("SELECT id_folha, revisado FROM folhas WHERE id_usuario = ? AND mes_competencia = ?");
    $stmtCheck->bind_param("is", $id_usuario, $mes_padrao);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $dadosExistentes = $resCheck->fetch_assoc();

    if ($dadosExistentes) {
        if ($dadosExistentes['revisado'] == 1) {
            return null; // NÃO ALTERA se já foi revisado
        }
        // Procede com o UPDATE
        $stmtUpdate = $conn->prepare("
            UPDATE folhas SET
                salario_bruto = ?, total_proventos = ?, total_descontos = ?, 
                fgts = ?, inss = ?, irrf = ?, vt = ?, salario_liquido = ?
        ");
        $stmtUpdate->bind_param(
            "dddddddi",
            $salario_bruto, $total_proventos, $total_descontos,
            $fgts, $inss, $irrf, $vt, $salario_liquido
        );
        $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        // Procede com o INSERT
        $stmtInsert = $conn->prepare("
            INSERT INTO folhas 
            (id_usuario, mes_competencia, salario_bruto, total_proventos, total_descontos, fgts, inss, irrf, vt, salario_liquido)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->bind_param(
            "issddddddd",
            $id_usuario, $mes_padrao, $salario_bruto, $total_proventos, $total_descontos,
            $fgts, $inss, $irrf, $vt, $salario_liquido
        );
        $stmtInsert->execute();
        $stmtInsert->close();
    }

    $stmt4 = $conn->prepare("
    SELECT
    revisado
    FROM folhas
    WHERE id_usuario = ? AND mes_competencia = ? LIMIT 1");

    $stmt4->bind_param('is', $id_usuario, $mes_padrao);
    $stmt4->execute();
    $resultado = $stmt4->get_result();
    if ($row = $resultado->fetch_assoc()){
        $revisado = $row['revisado'];
    }

    return [
        'id_usuario' => $id_usuario,
        'nome_usuario' => $usuario['nome_usuario'],
        'salario_liquido' => $salario_liquido,
        'revisado' => $revisado
    ];
}

// --------------------------
// 5. Gerar folhas se botão foi clicado
// --------------------------
$folhas_geradas = [];
if ($_SERVER['REQUEST_METHOD'] === "POST"){
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'gerar') {
        foreach ($usuarios as $usuario) {
            $folha = gerarFolhaUsuario($usuario, $mes_padrao, $conn);
            if ($folha) $folhas_geradas[] = $folha;
        }
    }
    if ($acao === 'revisar') {
            $stmt5 = $conn->prepare('UPDATE folhas SET revisado = 1 WHERE mes_competencia = ?');
            $stmt5->bind_param('s', $mes_padrao);
            $stmt5->execute();
            echo "<script>console.log(penis)</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerar Folhas de Pagamento</title>
<style> 
table {border-collapse: collapse; width: 25%; margin-top: 20px;}
td, th {border: 1px solid #1b1b1b; padding: 8px;}
</style>
</head>
<body>

<a href="../">voltar</a>
<h1>Gerar Folhas de Pagamento</h1>

<!-- Form para adicionar evento -->
<form method="POST">
    <fieldset>
        <legend>Adicionar Provento/Desconto</legend>

        <label>Mês:</label>
        <input type="month" name="mes" id="mesEvento" value="<?= $mes ?>"><br>

        <label>Usuário:</label>
        <select name="id_usuario_evento" required>
            <option value="">-- Selecione --</option>
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id_usuario'] ?>"><?= $u['nome_usuario'] ?></option>
            <?php endforeach; ?>
        </select><br>

        <label>Tipo:</label>
        <select name="tipo" required>
            <option value="provento">Provento</option>
            <option value="desconto">Desconto</option>
        </select><br>

        <label>Descrição:</label>
        <input type="text" name="descricao" placeholder="Descrição do evento"><br>

        <label>Valor:</label>
        <input type="number" step="0.01" name="valor" placeholder="0.00"><br>

        <button type="submit" name="add_evento" id="add_evento">Adicionar Evento</button>
    </fieldset>
</form>

<?php if ($mensagem_evento): ?>
    <p style="color:green;"><?= $mensagem_evento ?></p>
<?php endif; ?>

<!-- Form para gerar folhas -->
<h2 id="h2">Folhas Geradas <?= $mes ?></h2>
<form method="POST" id="Form">
    <label>Mês:</label>
    <!-- o php só consegue acessar o value e o name de um input -->
    <input type="hidden" name="acao" id="inputAcao" value="">
    <input type="month" id="mesGerar" name="mesGerar" value="<?= $mes ?>">
    <button type="button" id="gerar_folhas" name="gerar_folhas">
        Gerar Todas as Folhas
    </button>
    <button type="button" class="btn-revisar">
        Revisar todos
    </button>
</form>
<?php if (!empty($folhas_geradas)): ?>
    
    <table id="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Salário Líquido</th>
                <th>Ações</th>
            </tr>
        </thead>
        <?php foreach ($folhas_geradas as $f): ?>
            <tr>
                <td><?= $f['nome_usuario'] ?></td>
                <td>R$ <?= number_format($f['salario_liquido'], 2, ',', '.') ?></td>
                    <td>
                        <?php if($f['revisado'] == 1):?>
                            <a>Nenhuma ação disponível</a>
                        <?php else:?>
                            <a href="revisar_folha.php?mes=<?= urlencode($mes) ?>&id_usuario=<?= urlencode($f['id_usuario']) ?>">
                            | Revisar PDF
                            </a>
                            <a href="editar_folha.php?mes=<?= urlencode($mes) ?>&id_usuario=<?= urlencode($f['id_usuario']) ?>">
                            | Editar Eventos|
                            </a>
                        <?php endif?>
                    </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<script>
    const h2 = document.getElementById('h2');
    const mesGerar = document.getElementById('mesGerar');
    const mesEvento = document.getElementById('mesEvento');
    const btnEvento = document.getElementById('add_evento');
    const btnGerar = document.getElementById('gerar_folhas');
    const btnRevisao = document.querySelector('.btn-revisar');
    const inputAcao = document.getElementById('inputAcao');
    const form = document.getElementById('Form');

    function ouvirEventos(mes, btn){
        const dataFixa = mes.value;
        mes.addEventListener('input', (event)=>{
            const data = event.target.value
            h2.textContent = `Folhas Geradas ${data}`;
            if (dataFixa === data){
                btn.removeAttribute('disabled', 'true');
            } else{
                btn.setAttribute('disabled', 'true');
            }
        })
    }

    ouvirEventos(mesGerar, btnGerar);
    ouvirEventos(mesEvento, btnEvento);

    btnGerar.addEventListener('click', function(){
        inputAcao.value = 'gerar';
        form.submit();
    })

    btnRevisao.addEventListener('click', function(){
        const confirmacao = confirm(`Deseja marcar todas as folhas como revisado?
        as folhas não poderão ser modificada depois`);

        if (confirmacao){
            inputAcao.value = 'revisar'; // Define a ação antes do submit
            form.submit();
        }
    })
</script>

</body>
</html>