<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require "../../include/funcoes/funcoes_calculo.php";

// Correção da lógica de erro de acesso
$msg_acesso = $_GET['erro'] ?? '';

// Recupera as mensagens da sessão APENAS se elas existirem
$mensagem_evento = $_SESSION['msg'] ?? '';
$cor_evento = $_SESSION['cor'] ?? '';

// Limpa a sessão para que a mensagem não reapareça em futuros refreshes manuais
unset($_SESSION['msg'], $_SESSION['cor']);

// --------------------------
// 1. Recebe mês do formulário
// --------------------------
$mes = date('Y-m'); // default: mês atual
$mes_padrao = $mes . "-01";

// --------------------------
// 2. Buscar todos os usuários ativos
// --------------------------
$sql = "SELECT u.id_usuario,
        u.nome_usuario,
        u.id_cargo,
        c.id_cargo,
        c.nome_cargo
        FROM usuario u
        JOIN cargo c ON c.id_cargo = u.id_cargo
        WHERE conta_ativa = 1";
$result = $conn->query($sql);
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

// --------------------------
// 3. Adicionar evento (form separado)
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === "POST"){
    if (isset($_POST['add_evento'])) {
        $id_usuario_evento = $_POST['id_usuario_evento'] ?? null;
        $tipo = $_POST['tipo'] ?? '';
        $descricao = $_POST['descricao'] ?? 'Não Informado';
        $valor = floatval($_POST['valor'] ?? 0);
        $data = $_POST['mesEvento'] ?? $mes;

        if($id_usuario_evento === 'todos'){
            $stmt = $conn->prepare("
                SELECT * FROM folhas WHERE mes_competencia = ? AND revisado = 0
            ");
            $stmt->bind_param("s", $mes_padrao);
            $stmt->execute();
            $verificacao = $stmt->get_result();

            // se folha não for revisada
            if(($verificacao->num_rows > 0)){
                foreach($usuarios as $u){
                    $stmtInsert = $conn->prepare("
                    INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
                    VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->bind_param("issds", $u['id_usuario'], $tipo, $descricao, $valor, $mes_padrao);
                    $stmtInsert->execute();
                }
                $_SESSION['msg'] = "Evento adicionado com sucesso!";
                $_SESSION['cor'] = "green";

                // limpa o post
                header("Location: " . $_SERVER['PHP_SELF']);
                exit; // exit obrigatório: Mata o script para não renderizar o resto
            } else{
                $_SESSION['msg'] = "Todas as folhas já foram revisadas!";
                $_SESSION['cor'] = "red";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit; // exit obrigatório: Mata o script para não renderizar o resto
            }
        }

        if ($id_usuario_evento && $tipo && $valor > 0 && $data == date('Y-m')) {
            // busca retornar true caso a folha não tenha sido revisada
            $stmt = $conn->prepare("
                SELECT * FROM folhas WHERE id_usuario = ? AND mes_competencia = ? AND revisado = 0
            ");
            $stmt->bind_param("is", $id_usuario_evento, $mes_padrao);
            $stmt->execute();
            $verificacao = $stmt->get_result();

            // se folha não for revisada
            if(($verificacao->num_rows > 0)){
                $stmtInsert = $conn->prepare("
                INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
                VALUES (?, ?, ?, ?, ?)
                ");
                $stmtInsert->bind_param("issds", $id_usuario_evento, $tipo, $descricao, $valor, $mes_padrao);
                $stmtInsert->execute();
                $_SESSION['msg'] = "Evento adicionado com sucesso!";
                $_SESSION['cor'] = "green";

                // limpa o post
                header("Location: " . $_SERVER['PHP_SELF']);
                exit; // exit obrigatório: Mata o script para não renderizar o resto
            } else{
                $_SESSION['msg'] = "Folha do Evento já foi revisada!";
                $_SESSION['cor'] = "red";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit; // exit obrigatório: Mata o script para não renderizar o resto
            }
            
        } else {
            $_SESSION['msg'] = "Preencha todos os campos corretamente.";
            $_SESSION['cor'] = 'red';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit; // exit obrigatório: Mata o script para não renderizar o resto
        }
    }
}

// --------------------------
// 4. Função para gerar folha de um usuário
// --------------------------
function gerarFolhaUsuario(array $usuario, string $mes_padrao, $conn) {
    $id_usuario = $usuario['id_usuario'];

    // Verifica se a folha já existe e se está revisada
    $stmtCheck = $conn->prepare("SELECT id_folha, salario_liquido, revisado FROM folhas WHERE id_usuario = ? AND mes_competencia = ?");
    $stmtCheck->bind_param("is", $id_usuario, $mes_padrao);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $folhaExistente = $resCheck->fetch_assoc();
    $stmtCheck->close();

    // Se folha revisada, interrompe execução para este usuário.
    if ($folhaExistente && $folhaExistente['revisado'] == 1) {
        return [
            'id_usuario' => $id_usuario,
            'nome_usuario' => $usuario['nome_usuario'],
            'salario_liquido' => $folhaExistente['salario_liquido'],
            'revisado' => 1,
        ];
    }

    // --- Buscar salário ---
    $stmt = $conn->prepare("
        SELECT c.salario_bruto 
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
    $stmt2 = $conn->prepare("SELECT tipo, valor FROM eventos WHERE id_usuario = ? AND mes_competencia = ?");
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
    $vt   = calcularVT($salario_bruto, 300);
    
    // O total de descontos soma os eventos variáveis + os descontos legais compulsórios
    $descontos_totais_calculados = $total_descontos + $inss + $irrf + $vt;
    $salario_liquido = calcularSalarioLiquido($salario_bruto, $total_proventos, $descontos_totais_calculados);

    // --- Salvar no banco ---
    // se folha existe mas revisado = 0, da update
    if ($folhaExistente && $folhaExistente['revisado'] == 0) {
        $stmtUpdate = $conn->prepare("
            UPDATE folhas SET
                salario_bruto = ?, total_proventos = ?, total_descontos = ?, 
                fgts = ?, inss = ?, irrf = ?, vt = ?, salario_liquido = ?
            WHERE id_folha = ?
        ");
        $stmtUpdate->bind_param(
            "dddddddii",
            $salario_bruto, $total_proventos, $descontos_totais_calculados,
            $fgts, $inss, $irrf, $vt, $salario_liquido,
            $folhaExistente['id_folha']
        );
        $stmtUpdate->execute();
        $stmtUpdate->close();
    } else { // caso folha não exista 
        $stmtInsert = $conn->prepare("
            INSERT INTO folhas 
            (id_usuario, mes_competencia, salario_bruto, total_proventos, total_descontos, fgts, inss, irrf, vt, salario_liquido, revisado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmtInsert->bind_param(
            "issddddddd",
            $id_usuario, $mes_padrao, $salario_bruto, $total_proventos, $descontos_totais_calculados,
            $fgts, $inss, $irrf, $vt, $salario_liquido
        );
        $stmtInsert->execute();
        $stmtInsert->close();
    }

    return [
        'id_usuario' => $id_usuario,
        'nome_usuario' => $usuario['nome_usuario'],
        'revisado' => 0
    ];
}

// --------------------------
// 5. Gerar folhas se botão foi clicado
// --------------------------
$folhas_geradas = [];
if ($_SERVER['REQUEST_METHOD'] === "POST"){
    $msg_acesso = '';
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'gerar') {
        foreach ($usuarios as $usuario) {
            $folha = gerarFolhaUsuario($usuario, $mes_padrao, $conn);
            if ($folha) $folhas_geradas[] = $folha;
        }
    }
    if ($acao === 'revisar') {
        $stmtRevisar = $conn->prepare('SELECT revisado FROM folhas WHERE revisado = 0 AND mes_competencia = ?');
        $stmtRevisar->bind_param('s', $mes_padrao);
        $stmtRevisar->execute();
        $totalLinhas = $stmtRevisar->get_result();
        if ($totalLinhas->num_rows > 0){
            $stmt5 = $conn->prepare('UPDATE folhas SET revisado = 1 WHERE mes_competencia = ?');
            $stmt5->bind_param('s', $mes_padrao);
            $stmt5->execute();
        } else{
            $msg_acesso = 'Todas as Folhas já foram Revisadas.';
        }
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
        <input type="month" name="mesEvento" id="mesEvento" value='<?= $mes ?>' readonly><br>

        <label>Usuário:</label>
        <select name="id_usuario_evento" required>
            <option value="">-- Selecione --</option>
            <option value="todos">Todos</option>
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id_usuario'] ?>"><?= $u['nome_usuario']?> (<?= $u['nome_cargo'] ?>)</option>
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
    <br>
    <?php if ($mensagem_evento): ?>
    <p style="color:<?= $cor_evento ?>;"><?= $mensagem_evento ?></p>
<?php endif; ?>
</form>

<!-- Form para gerar folhas -->
<h2 id="h2">Folhas Geradas <?= $mes ?></h2>
<form method="POST" id="Form">
    <label>Mês:</label>
    <!-- o php só consegue acessar o value e o name de um input -->
    <input type="hidden" name="acao" id="inputAcao" value="">
    <input type="month" id="mesGerar" name="mesGerar" value="<?= $mes ?>" readonly>
    <button type="button" id="gerar_folhas" name="gerar_folhas">
        Gerar Todas as Folhas
    </button>
    <?php if (!empty($folhas_geradas)): ?>
        <button type="button" class="btn-revisar">
            Revisar todos
        </button>
    <?php endif; ?>
    <br>
    <p style='color: red'><?= $msg_acesso ?></p>
</form>
<?php if (!empty($folhas_geradas)): ?>
    <table id="tabela">
        <thead>
            <tr>
                <th>Nome</th>

                <th>Ações</th>
            </tr>
        </thead>
        <?php foreach ($folhas_geradas as $f): ?>
            <tr>
                <td><?= $f['nome_usuario'] ?></td>

                    <td>
                        <?php if($f['revisado'] == 1):?>
                            <a>Nenhuma ação disponível</a>
                        <?php else:?>
                            <a href="revisar_folha.php?mes=<?= $mes ?>&id_usuario=<?= $f['id_usuario'] ?>">
                            | Revisar PDF
                            </a>
                            <a href="editar_folha.php?mes=<?= $mes ?>&id_usuario=<?= $f['id_usuario'] ?>">
                            | Editar Eventos|
                            </a>
                        <?php endif?>
                    </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<script>
    const mesGerar = document.getElementById('mesGerar');
    const mesEvento = document.getElementById('mesEvento');
    const btnEvento = document.getElementById('add_evento');
    const btnGerar = document.getElementById('gerar_folhas');
    const btnRevisao = document.querySelector('.btn-revisar');
    const inputAcao = document.getElementById('inputAcao');
    const form = document.getElementById('Form');

    function ouvirEventos(mes, btn, btn2 = undefined){
        const dataFixa = mes.value;
        mes.addEventListener('input', (event)=>{
            const data = event.target.value
            if (dataFixa === data){
                btn.removeAttribute('disabled', 'true');
                btn2.removeAttribute('disabled', 'true');
            } else{
                btn.setAttribute('disabled', 'true');
                btn2.setAttribute('disabled', 'true');
            }
        })
    }

    ouvirEventos(mesGerar, btnGerar, btnRevisao);
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