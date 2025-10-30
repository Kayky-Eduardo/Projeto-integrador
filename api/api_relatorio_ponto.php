<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");


// realizando as pesquisas do status do funcionário
// no banco de dados
function dados_grafico ($conn) {
    $pesquisa_trabalhando = $conn->prepare("
        SELECT COUNT(*) AS total_trabalhando
        FROM ponto
        WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL)"
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
        WHERE inicio_almoco IS NOT NULL AND (fim_almoco IS NULL or fim_almoco = '' or fim_almoco = '00:00:00');
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
        WHERE inicio_ponto IS NOT NULL AND fim_ponto IS NOT NULL;
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

function filtrar($conn, $tipo) {
    if($tipo == 'presente') {
        $filtro_presente = $conn->query("
            select
        ");
    }

    if($tipo == 'ausentes') {
        
    }
    if($tipo == 'pausa') {
        
    }
    if($tipo == 'horario_completo') {
        
}

}
// pegando o tipo e entregando o resultado
$acao = $_GET['acao'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

if($acao == 'presente') {

}

if($acao == 'ausentes') {
    
}
if($acao == 'pausa') {
    
}
if($acao == 'horario_completo') {
    
}

// padrão entregar dados
echo json_encode(dados_grafico($conn));
?>