<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
require_once "../../include/verificacao.php";
require_once "../../include/funcoes/funcoes_jornada.php";
verificar_login($conn);

$setores = $conn->query("SELECT id_setor, nome_setor FROM setor ORDER BY nome_setor");
$jornadas = $conn->query("SELECT * FROM tempo_jornada ORDER BY descricao");
$setores_jornadas = $conn->query("
    SELECT 
        s.id_setor,
        s.nome_setor,
        tempo_jornada.descricao AS jornada_descricao,
        tempo_jornada.jornada,
        tempo_jornada.maximo_hora_extra,
        tempo_jornada.dias_semana
    FROM setor s
    LEFT JOIN tempo_jornada tempo_jornada ON s.id_tempo = tempo_jornada.id_tempo
    ORDER BY s.nome_setor
");

// funções para formatar os números em dias
function formatar_dias($numeros) {
    $dias = [
        1 => 'Seg',
        2 => 'Ter',
        3 => 'Qua',
        4 => 'Qui',
        5 => 'Sex',
        6 => 'Sáb',
        7 => 'Dom'
    ];
    
    if ($numeros === null || $numeros === '' || $numeros === false) {
        return '-';
    }
    
    // Se for string, tenta decodificar como JSON
    if (is_string($numeros)) {
        $numeros = trim($numeros);
        
        if (strpos($numeros, '[') === 0) {

            $decoded = json_decode($numeros, true);
            $numeros = $decoded;
            
        } else {
            return '-';
        }
    }
    
    // Se não for array retorna traço
    if (!is_array($numeros)) {
        return '-';
    }
    
    if (empty($numeros)) {
        return '-';
    }
    
    $dias_formatados = [];
    
    // Converte cada número em dia abreviado
    foreach ($numeros as $num) {
        $num = (int)$num;
        if (isset($dias[$num])) {
            $dias_formatados[] = $dias[$num];
        }
    }
    
    // Retorna os dias separados por vírgula, ou - se vazio
    return !empty($dias_formatados) ? implode(', ', $dias_formatados) : '-';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['setor'], $_POST['set-setor-jornada'])) {
        
        $id_setor = $_POST['setor'];
        $id_jornada = $_POST['set-setor-jornada'];
        
        $resposta = atualizar_jornada($conn, $id_jornada, $id_setor);
        
        // Armazena a resposta na sessão para exibir após o redirect
        $_SESSION['resposta_jornada'] = $resposta;
        
        // pra evitar reenvio do formulário
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Recupera a mensagem da sessão, se existir
$resposta_exibir = null;
if (isset($_SESSION['resposta_jornada'])) {
    $resposta_exibir = $_SESSION['resposta_jornada'];
    unset($_SESSION['resposta_jornada']); // Remove da sessão após recuperar
}


?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Configurações</title>
    <?php include("../../include/link.html"); ?>
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

     <main class="configuracoes">
        <aside aria-label="Menu de configurações">
            <ul class="menu-config">
                <li><a href="#" class="ativo">Definir Jornadas de Trabalho</a></li>
                <li><a href="#">#</a></li>
                <li><a href="#">#</a></li>
                <li><a href="#">#</a></li>
            </ul>
        </aside>
        <section class="container" aria-label="Definição de jornada">
            <h1>Definir jornada por setor:</h1>
            
            <!-- Exibe a mensagem de resposta, se houver -->
            <?php if ($resposta_exibir): ?>
                <p id="resposta" 
                   class="<?= $resposta_exibir['sucesso'] ? 'msg-sucesso' : 'msg-erro' ?>" 
                   role="alert" 
                   aria-live="polite" 
                   tabindex="0">
                    <?= htmlspecialchars($resposta_exibir['mensagem']) ?>
                </p>
            <?php endif; ?>
            
            <form class="form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <label class="label" for="set-setores">Selecione um setor:</label>
                <select class="select-padrao" name="setor" id="set-setores" required>
                    <option value="">Selecione um setor</option>
                    <?php while ($s = $setores->fetch_assoc()): ?>
                        <option value="<?= (int)$s['id_setor'] ?>">
                            <?= htmlspecialchars($s['nome_setor']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <label class="label" for="set-setor-jornada">Selecione uma jornada:</label>
                <select class="select-padrao" name="set-setor-jornada" id="set-setor-jornada" required>
                    <option value="">Selecione uma jornada</option>
                    <?php 
                    // Reseta o ponteiro do resultado para reutilizar   
                    $jornadas->data_seek(0);
                    while ($j = $jornadas->fetch_assoc()): 
                        // Formata os dias da semana para exibição compacta
                        $dias_formatados = formatar_dias($j['dias_semana'] ?? '');
                    ?>
                        <option value="<?= (int)$j['id_tempo'] ?>">
                            <?= htmlspecialchars($j['descricao'] ?? 'Sem descrição') ?> | 
                            <?= htmlspecialchars($j['jornada']) ?> | 
                            he: <?= htmlspecialchars($j['maximo_hora_extra']) ?> | 
                            <?= htmlspecialchars($dias_formatados) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit" class="btn btn-padrao">Aplicar</button>
            </form>
            <h2>Setores e suas Jornadas</h2>
            
            <table class="tabela-padrao" aria-label="Tabela de setores e jornadas">
                <thead>
                    <tr>
                        <th>Nome do Setor</th>
                        <th>Descrição da Jornada</th>
                        <th>Jornada</th>
                        <th>Hora Extra</th>
                        <th>Dias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($setores_jornadas->num_rows > 0):
                        while ($sj = $setores_jornadas->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sj['nome_setor']) ?></strong></td>
                            <td><?= htmlspecialchars($sj['jornada_descricao']) ?></td>
                            <td><?= htmlspecialchars(substr($sj['jornada'], 0, 5)) ?></td>
                            <td><?= htmlspecialchars(substr($sj['maximo_hora_extra'], 0, 5)) ?></td>
                            <td><?php echo htmlspecialchars(formatar_dias($sj['dias_semana']))?></td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5">
                                Nenhum setor cadastrado no sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
     <script type="text/javascript">

     </script>
</body>

</html>