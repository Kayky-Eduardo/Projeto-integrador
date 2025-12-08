<?php


// realizando as pesquisas do status do funcionário
// no banco de dados
function dados_grafico ($conn) {
    $pesquisa_trabalhando = $conn->prepare("
        SELECT COUNT(*) AS total_trabalhando
        FROM ponto_dia
        WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL OR fim_ponto = '00:00:00')
        AND data_ponto = CURDATE()
    ");
    
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
    // validar
    // $pesquisa_pausa = $conn->prepare("
    //     SELECT COUNT(*) AS total_pausa
    //     FROM ponto_dia
    //     WHERE hora_almoco_saida IS NOT NULL AND (hora_almoco_retorno IS NULL or hora_almoco_retorno = '' or hora_almoco_retorno = '00:00:00')
    //     AND data_ponto = CURDATE()
    // ");
    // $pesquisa_pausa->execute();
    // $result = $pesquisa_pausa->get_result();
    // if ($linha = $result->fetch_assoc()) {
    //     $numero_pausa = (int)$linha['total_pausa'];
    // } else {
        $numero_pausa = 0;
    // }
    
    // pesquisa horario completo
    $pesquisa_horario_completo = $conn->prepare("
        SELECT COUNT(*) AS total_completo
        FROM ponto_dia
        WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NOT NULL AND fim_ponto != '00:00:00')
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
            usuario.email_usuario, ponto_dia.*,
            TIMESTAMPDIFF(MINUTE, inicio_ponto, NOW()) AS tempo_logado
        FROM ponto_dia JOIN usuario ON ponto_dia.id_usuario = usuario.id_usuario
        WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NULL or fim_ponto = '00:00:00')
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
            IFNULL(TIMESTAMPDIFF(MINUTE, inicio_ponto, NOW()), 0) AS tempo_logado 
        from usuario
        left join ponto_dia on usuario.id_usuario = ponto_dia.id_usuario
        where ponto_dia.id_ponto is null or ponto_dia.inicio_ponto = '00:00:00'
        ");
        $filtro_ausente->execute();
        $result = $filtro_ausente->get_result();
        while($linha = $result->fetch_assoc()){
            $ausentes[] = $linha;
        }
        return $ausentes;
    }
    // validar
    // if($tipo == 'pausa') {
    //     $pausas = [];
    //     $filtro_pausa = $conn->prepare("
    //     select usuario.email_usuario, ponto_dia.*
    //     from ponto_dia join usuario on ponto_dia.id_usuario = usuario.id_usuario
    //     WHERE hora_almoco_saida IS NOT NULL AND (hora_almoco_retorno IS NULL or hora_almoco_retorno = ''
    //     or hora_almoco_retorno = '00:00:00') AND ponto_dia.data_ponto = CURDATE()
    //     ");
    //     $filtro_pausa->execute();
    //     $result = $filtro_pausa->get_result();
    //     while($linha = $result->fetch_assoc()){
    //         $pausas[] = $linha;
    //     }
    //     return $pausas;
    // }
    if($tipo == 'horario') {
        $horario_completo = [];
        $filtro_horario_completo = $conn->prepare("
        SELECT 
            usuario.email_usuario, ponto_dia.*,
            TIMESTAMPDIFF(MINUTE, inicio_ponto, fim_ponto) AS tempo_logado 
        FROM ponto_dia JOIN usuario ON ponto_dia.id_usuario = usuario.id_usuario
        WHERE inicio_ponto IS NOT NULL AND (fim_ponto IS NOT NULL AND fim_ponto != '00:00:00')
        AND ponto_dia.data_ponto = CURDATE()
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
    $usuario = [];

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

?>