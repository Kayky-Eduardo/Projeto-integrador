<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");


// realizando as pesquisas do status do funcionário
// no banco de dados
function dados_grafico ($conn) {
    $pesquisa_trabalhando = $conn->prepare("
        SELECT COUNT(*) AS total_trabalhando
        FROM ponto
        WHERE hora_entrada IS NOT NULL AND (hora_saida IS NULL OR hora_saida = '00:00:00')"
    );
    
    $pesquisa_trabalhando->execute();
    $result = $pesquisa_trabalhando->get_result();
    if ($linha = $result->fetch_assoc()) {
        $numero_presente = $linha['total_trabalhando'];
    } else {
        $numero_presente = 0;
    }
    
    $pesquisa_ausentes = $conn->prepare("
        SELECT COUNT(*) AS total_usuarios
        FROM usuario
    ");
    $pesquisa_ausentes->execute();
    $result = $pesquisa_ausentes->get_result();
    if ($linha = $result->fetch_assoc()) {
        $numero_ausentes = (int)$linha['total_usuarios'] - (int)$numero_presente;
    } else {
        $numero_ausentes = 0;
    }
    
    // Pesquisa de pausa( incompleto porque depende de outro código),
    // irei retornar aqui assim que o código de ponto/pausas estiverem feito
    $pesquisa_pausa = $conn->prepare("
        SELECT COUNT(*) AS total_pausa
        FROM ponto
        WHERE hora_almoco_saida IS NOT NULL AND (hora_almoco_retorno IS NULL or hora_almoco_retorno = '' or hora_almoco_retorno = '00:00:00');
    ");
    $pesquisa_pausa->execute();
    $result = $pesquisa_pausa->get_result();
    if ($linha = $result->fetch_assoc()) {
        $numero_pausa = (int)$linha['total_pausa'];
    } else {
        $numero_pausa = 0;
    }
    
    // pesquisa horario completo
    $pesquisa_horario_completo = $conn->prepare("
        SELECT COUNT(*) AS total_completo
        FROM ponto
        WHERE hora_entrada IS NOT NULL AND (hora_saida IS NOT NULL AND hora_saida != '00:00:00');
    ");
    $pesquisa_horario_completo->execute();

    $result = $pesquisa_horario_completo->get_result();

    if ($linha = $result->fetch_assoc()) {
        $numero_horario_completo = (int)$linha['total_completo'];
    } else {
        $numero_horario_completo = 0;
    }
    
    // entregando uma array com os valores da pesquisas
    $valores = [
        $numero_presente, $numero_ausentes,
        $numero_pausa, $numero_horario_completo
    ];
    return $valores;
}

// realizando o filtro para trazer as informações
function filtrar($conn, $tipo) {
    if($tipo == 'presentes') {
        $presentes = [];
        $filtro_presente = $conn->prepare("
            select usuario.email_usuario, ponto.*
            from ponto join usuario on ponto.id_usuario = usuario.id_usuario
            WHERE hora_entrada IS NOT NULL AND (hora_saida IS NULL or hora_saida = '00:00:00');
        ");
        $filtro_presente->execute();
        $result = $filtro_presente->get_result();
        while($linha = $result->fetch_assoc()){
            $presentes[] = $linha;
        }
        return $presentes;
    }

    if($tipo == 'ausentes') {
        $ausentes = [];
        $filtro_ausente = $conn->prepare("
        select usuario.email_usuario, usuario.id_usuario
        from usuario
        left join ponto on usuario.id_usuario = ponto.id_usuario
        where ponto.id_ponto is null or ponto.hora_entrada = '00:00:00'
        ");
        $filtro_ausente->execute();
        $result = $filtro_ausente->get_result();
        while($linha = $result->fetch_assoc()){
            $ausentes[] = $linha;
        }
        return $ausentes;
    }
    if($tipo == 'pausa') {
        $pausas = [];
        $filtro_pausa = $conn->prepare("
        select usuario.email_usuario, ponto.*
        from ponto join usuario on ponto.id_usuario = usuario.id_usuario
        WHERE hora_almoco_saida IS NOT NULL AND (hora_almoco_retorno IS NULL or hora_almoco_retorno = '' or hora_almoco_retorno = '00:00:00')
        ");
        $filtro_pausa->execute();
        $result = $filtro_pausa->get_result();
        while($linha = $result->fetch_assoc()){
            $pausas[] = $linha;
        }
        return $pausas;
    }
    if($tipo == 'horario') {
        $horario_completo = [];
        $filtro_horario_completo = $conn->prepare("
        select usuario.email_usuario, ponto.*
        from ponto join usuario on ponto.id_usuario = usuario.id_usuario
        WHERE hora_entrada IS NOT NULL AND (hora_saida IS NOT NULL AND hora_saida != '00:00:00');
    ");
    $filtro_horario_completo->execute();
    $result = $filtro_horario_completo->get_result();
    
    while($linha = $result->fetch_assoc()){
        $horario_completo[] = $linha;
    }

    return $horario_completo; 
    }
}

// pegando o tipo e entregando o resultado
$acao = $_GET['acao'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);
$white_list = ['ausentes', 'pausa', 'horario', 'presentes'];

if ($acao) {
    $acao_formatada = strtolower($acao);
    if (in_array($acao_formatada, $white_list)) {
        if($acao != null) {
            echo json_encode(filtrar($conn, $acao_formatada));
            exit;
        }
    }
}

// padrão entregar dados
echo json_encode(dados_grafico($conn));
exit;
?>