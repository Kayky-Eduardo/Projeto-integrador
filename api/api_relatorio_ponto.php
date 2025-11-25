<?php
include(__DIR__ . "/../BD/conexao.php");
header("Content-Type: application/json");


// realizando as pesquisas do status do funcionário
// no banco de dados
function dados_grafico ($conn) {
    $pesquisa_trabalhando = $conn->prepare("
        SELECT COUNT(*) AS total_trabalhando
        FROM ponto
        WHERE hora_entrada IS NOT NULL AND (hora_saida IS NULL OR hora_saida = '00:00:00')
        AND data_ponto = CURDATE()"
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
        WHERE hora_almoco_saida IS NOT NULL AND (hora_almoco_retorno IS NULL or hora_almoco_retorno = '' or hora_almoco_retorno = '00:00:00')
        AND data_ponto = CURDATE()
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
        WHERE hora_entrada IS NOT NULL AND (hora_saida IS NOT NULL AND hora_saida != '00:00:00')
        AND data_ponto = CURDATE()
        ;
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
        SELECT
            usuario.email_usuario, ponto.*,
            TIMESTAMPDIFF(MINUTE, hora_entrada, NOW()) AS tempo_logado
        FROM ponto JOIN usuario ON ponto.id_usuario = usuario.id_usuario
        WHERE hora_entrada IS NOT NULL AND (hora_saida IS NULL or hora_saida = '00:00:00')
        AND data_ponto = CURDATE();
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
        select 
            usuario.email_usuario, usuario.id_usuario,
            IFNULL(TIMESTAMPDIFF(MINUTE, hora_entrada, NOW()), 0) AS tempo_logado 
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
        WHERE hora_almoco_saida IS NOT NULL AND (hora_almoco_retorno IS NULL or hora_almoco_retorno = ''
        or hora_almoco_retorno = '00:00:00') AND ponto.data_ponto = CURDATE()
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
        SELECT 
            usuario.email_usuario, ponto.*,
            TIMESTAMPDIFF(MINUTE, hora_entrada, hora_saida) AS tempo_logado 
        FROM ponto JOIN usuario ON ponto.id_usuario = usuario.id_usuario
        WHERE hora_entrada IS NOT NULL AND (hora_saida IS NOT NULL AND hora_saida != '00:00:00')
        AND ponto.data_ponto = CURDATE()
        ;
    ");
    $filtro_horario_completo->execute();
    $result = $filtro_horario_completo->get_result();
    
    while($linha = $result->fetch_assoc()){
        $horario_completo[] = $linha;
    }

    return $horario_completo; 
    }
}

function coleta_usuarios($conn) {
    $coleta_usuario = $conn->prepare("
    SELECT id_usuario, nome_usuario
    FROM usuario
    WHERE conta_ativa = 1;
    ");
    
    $coleta_usuario->execute();
    $result = $coleta_usuario->get_result();

    while($linha = $result->fetch_assoc()) {
        $usuarios[] = $linha;
    }
    return $usuarios;
}


// fazer o SUM de todos as horas extras independente de tipo de turno por enquanto
function filtrar_usuario($conn, $id_usuario) {
    $coleta_usuario = $conn->prepare("
    SELECT sum(minutos) as total_extra
    FROM horas_extras WHERE id_usuario = ?;
    ");
    $coleta_usuario->bind_param("i", $id_usuario);
    $coleta_usuario->execute();
    
    $result = $coleta_usuario->get_result();
    $linha = $result->fetch_assoc();

    return $linha['total_extra'] ?? 0;
}

function relatorio_ponto_filtrado($conn, $id_usuario) {
    $coleta_usuario_tabela = $conn->prepare("
        SELECT id_login, email_login, data_inicio, data_fim,
        TIMESTAMPDIFF(MINUTE, data_inicio, data_fim) AS tempo_logado
        FROM login
        WHERE MONTH(data_inicio) = MONTH(CURDATE())
        AND id_usuario = ?;
    ");
    $coleta_usuario_tabela->bind_param("i", $id_usuario);
    $coleta_usuario_tabela->execute();
    
    $result = $coleta_usuario_tabela->get_result();
    while($linha = $result->fetch_assoc()) {
        $usuario[] = $linha;
    }
    return $usuario;
}

function get_logados($conn) {
    $coleta_usuario_tabela = $conn->prepare("
    SELECT usuario.id_usuario, usuario.email_usuario, id_login, email_login, data_inicio,
    TIMESTAMPDIFF(MINUTE, data_inicio, NOW()) AS tempo_logado
    FROM login 
    LEFT JOIN usuario on login.id_usuario = usuario.id_usuario
    WHERE MONTH(data_inicio) = MONTH(CURDATE())
    AND data_fim IS NULL;
    ");
    $coleta_usuario_tabela->execute();
    
    $result = $coleta_usuario_tabela->get_result();
    while($linha = $result->fetch_assoc()) {
        $usuario[] = $linha;
    }
    return $usuario;
}

function deslogar_usuario($conn, $id_login) {
    $stmt = $conn->prepare("
        UPDATE login 
        SET data_fim = NOW() 
        WHERE id_login = ? AND data_fim IS NULL
    ");

    $stmt->bind_param("i", $id_login);
    $sucesso = $stmt->execute();

    if ($sucesso && $stmt->affected_rows > 0) {
        return ['success' => true, 'message' => 'Usuário deslogado com sucesso'];
    } else {
        return ['success' => false, 'message' => 'Nenhum registro foi atualizado'];
    }
}

// pegando o tipo e entregando o resultado
$acao = $_GET['acao'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

$white_list = [
    'ausentes', 'pausa', 'horario',
    'presentes', 'usuarios', 'filtrar_usuario',
    'filtrar_tabela_hora', 'get_logados', 'deslogar' // Adicione 'deslogar'
];

if ($acao) {
    $acao_formatada = strtolower($acao);

    if (in_array($acao_formatada, $white_list)) {
        if ($acao_formatada === 'usuarios') {
            echo json_encode(coleta_usuarios($conn));
            exit;
        } else if ($acao_formatada === 'filtrar_usuario') {
            echo json_encode(filtrar_usuario($conn, $input['id_usuario'] ?? 0));
            exit;
        } else if ($acao_formatada === 'filtrar_tabela_hora') {
            echo json_encode(relatorio_ponto_filtrado($conn, $input['id_usuario']));
            exit;
        } else if ($acao_formatada === 'get_logados') {
            echo json_encode(get_logados($conn));
            exit;
        } else if ($acao_formatada === 'deslogar') {
            echo json_encode(deslogar_usuario($conn, $input['id_login'] ?? 0));
            exit;
        } else {
            echo json_encode(filtrar($conn, $acao_formatada));
            exit;
        }
    }
}
// padrão entregar dados
echo json_encode(dados_grafico($conn));
exit;
?>