<?php

function evolucao_presenca($conn) {
    $data_inicio = date('Y-m-01'); // Primeiro dia do mês
    $data_fim = date('Y-m-d'); // Hoje
    
    $total_usuarios = calculo_total_usuarios($conn);

    $stmt = $conn->prepare("
        SELECT 
            data_ponto,
            COUNT(DISTINCT id_usuario) AS presentes
        FROM ponto_dia
        WHERE status = 'aprovado'
        AND data_ponto BETWEEN ? AND ?
        GROUP BY data_ponto
        ORDER BY data_ponto ASC
    ");
    
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dados = [];
    
    $primeira_data = null;
    $ultima_data = null;

    
    while ($row = $result->fetch_assoc()) {
        $presentes = (int)$row['presentes'];
        $ausentes = $total_usuarios - $presentes;
        $dados[] = [
            'data' => $row['data_ponto'],
            'presentes' => $presentes,
            'ausentes' => $ausentes,
            'total' => $total_usuarios
        ];
    }
    
    if (!empty($dados)) {
        $ultima_data = end($dados)['data']; // pega a data do último 
        $primeira_data = $dados[0]['data']; // inverso
    }
    
    $stmt->close();
    
    return [
        'periodo' => [
            'inicio' => $data_inicio,
            'fim' => $data_fim
        ],
        'primeira_data' => $primeira_data,
        'ultima_data' => $ultima_data,
        'dados' => $dados
    ];
}

function calculo_total_usuarios($conn) {
    // Total de usuários
    $stmt_total = $conn->prepare("
        SELECT COUNT(*) AS total_usuarios
        FROM usuario
    ");
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $total_usuarios = 0;
    
    if ($linha_total = $result_total->fetch_assoc()) {
       $total_usuarios = (int)$linha_total['total_usuarios'];
    }
    
    $stmt_total->close();
    return $total_usuarios;
}

// realizando as pesquisas do status do funcionário
// no banco de dados
function dados_grafico($conn) {
    // Presentes (excluindo quem está em pausa)
    $pesquisa_trabalhando = $conn->prepare("
        SELECT COUNT(*) AS total_trabalhando
        FROM ponto_dia
        WHERE inicio_ponto IS NOT NULL 
        AND (fim_ponto IS NULL OR fim_ponto = '00:00:00')
        AND data_ponto = CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM pausa
            WHERE pausa.id_usuario = ponto_dia.id_usuario
            AND (pausa.fim IS NULL OR pausa.fim = '' OR pausa.fim = '0000-00-00')
            AND pausa.data = CURDATE()
        )
    ");
    $pesquisa_trabalhando->execute();
    $result = $pesquisa_trabalhando->get_result();
    $numero_presente = ($linha = $result->fetch_assoc()) ? (int)$linha['total_trabalhando'] : 0;

    // Em pausa
    $pesquisa_pausa = $conn->prepare("
        SELECT COUNT(*) AS total_pausa
        FROM pausa
        WHERE inicio IS NOT NULL 
        AND (fim IS NULL OR fim = '' OR fim = '0000-00-00')
        AND data = CURDATE()
    ");
    $pesquisa_pausa->execute();
    $result = $pesquisa_pausa->get_result();
    $numero_pausa = ($linha = $result->fetch_assoc()) ? (int)$linha['total_pausa'] : 0;

    // Horário completo
    $pesquisa_horario_completo = $conn->prepare("
        SELECT COUNT(*) AS total_completo
        FROM ponto_dia
        WHERE inicio_ponto IS NOT NULL 
        AND (fim_ponto IS NOT NULL AND fim_ponto != '00:00:00')
        AND data_ponto = CURDATE()
    ");
    $pesquisa_horario_completo->execute();
    $result = $pesquisa_horario_completo->get_result();
    $numero_horario_completo = ($linha = $result->fetch_assoc()) ? (int)$linha['total_completo'] : 0;

    // Ausentes = total - todas as outras categorias
    $total_usuarios = calculo_total_usuarios($conn);
    $numero_ausentes = $total_usuarios - $numero_presente - $numero_pausa - $numero_horario_completo;

    return [
        $numero_presente,
        $numero_ausentes,
        $numero_pausa,
        $numero_horario_completo
    ];
}

// realizando o filtro para trazer as informações
function filtrar($conn, $tipo) {
    if($tipo == 'presentes') {
        $presentes = [];
        $filtro_presente = $conn->prepare("
        SELECT
            usuario.email_usuario,
            ponto_dia.*,
            TIMESTAMPDIFF(MINUTE, inicio_ponto, NOW()) AS tempo_logado
        FROM ponto_dia
        JOIN usuario ON ponto_dia.id_usuario = usuario.id_usuario
        WHERE inicio_ponto IS NOT NULL 
        AND (fim_ponto IS NULL or fim_ponto = '00:00:00')
        AND data_ponto = CURDATE();
        ");
        $filtro_presente->execute();
        $result = $filtro_presente->get_result();
        while($linha = $result->fetch_assoc()){

            // // verificando se não tem pausa em aberta com este id
            // só pode ser contado como presente se não estiver com pausa aberta
            // $linha_id_usuario = $linha['id_usuario'];

            // $verificar_em_pausa = $conn->prepare("
            //     SELECT
            //         id_pausa,
            //         inicio,
            //         fim
            //     FROM pausa
            //     WHERE id_usuario = ?
            // ");
            // $verificar_em_pausa->bind_param("i", $linha_id_usuario);
            // $verificar_em_pausa->execute();
            // $resultado_verificacao = $verificar_em_pausa->get_result();

            // if ($resultado_verificacao->fetch_assoc()['fim'] === null) {
            //     // continua para o proximo usuario
            // } else {
            //     $presentes[] = $linha;
            // }
            $presentes[] = $linha;
        }
        return $presentes;
    }

    if($tipo == 'ausentes') {
        $ausentes = [];
        $filtro_ausente = $conn->prepare("
        SELECT 
            usuario.email_usuario,
            usuario.id_usuario,
            IFNULL(TIMESTAMPDIFF(MINUTE, inicio_ponto, NOW()), 0) AS tempo_logado 
        FROM usuario
        LEFT JOIN ponto_dia ON usuario.id_usuario = ponto_dia.id_usuario
        WHERE ponto_dia.id_ponto IS NULL 
        OR ponto_dia.inicio_ponto = '00:00:00'
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
        SELECT 
			ponto_dia.id_ponto,
            pausa.id_usuario,
            ponto_dia.inicio_ponto,
            usuario.email_usuario,
            inicio,
            fim,
            ponto_dia.data_ponto,
            descricao_pausa,
			TIMESTAMPDIFF(MINUTE, inicio_ponto, NOW()) AS tempo_logado
        FROM pausa
        JOIN pausa_config on pausa_config.id_config = pausa.id_config 
        JOIN ponto_dia on ponto_dia.id_usuario = pausa.id_usuario and pausa.data = ponto_dia.data_ponto
        JOIN usuario on pausa.id_usuario = usuario.id_usuario
        WHERE inicio IS NOT NULL AND (fim is null or fim = '' or fim = '0000-00-00')
        AND pausa.data = CURDATE();
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
            usuario.email_usuario,
            ponto_dia.id_ponto,
            ponto_dia.inicio_ponto,
            ponto_dia.fim_ponto,
            ponto_dia.data_ponto,
            TIMESTAMPDIFF(MINUTE, inicio_ponto, fim_ponto) AS tempo_logado 
        FROM ponto_dia JOIN usuario ON ponto_dia.id_usuario = usuario.id_usuario
        WHERE inicio_ponto IS NOT NULL 
        AND (fim_ponto IS NOT NULL AND fim_ponto != '00:00:00')
        AND ponto_dia.data_ponto = CURDATE();
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
function filtrar_usuario($conn, $id_usuario = null) {

    $data_inicio = date('Y-m-01'); // Primeiro dia do mes
    $data_fim = date('Y-m-d 23:59:59'); // hoje
    
    $coleta_usuario = $conn->prepare("
    SELECT saldo_minutos, nome_usuario, ultima_atualizacao
    FROM banco_horas
    JOIN usuario ON banco_horas.id_usuario = usuario.id_usuario;
    ");
    $coleta_usuario->execute();
    
    $result = $coleta_usuario->get_result();
    $dados_grafico = [];

    while ($usuario = $result->fetch_assoc()) {
        $saldo_minutos = $usuario['saldo_minutos'] / 60;
        $dados_grafico[] = [
            'nome_usuario' => $usuario['nome_usuario'],
            'saldo_horas' => $saldo_minutos,
            'data' => $usuario['ultima_atualizacao']
        ];
    }

    $coleta_usuario->close();
    
    if (empty($dados_grafico)) {
        return [
            'sucesso' => false,
            'periodo' => [
                'inicio' => $data_inicio,
                'fim' => $data_fim
            ],
            'usuarios' => [],
            'mensagem' => 'Sem registros'
        ];
    }

    return [
        'sucesso' => true,
        'periodo' => [
            'inicio' => $data_inicio,
            'fim' => $data_fim
        ],
        'mensagem' => 'consulta concluida!',
        'usuarios' => $dados_grafico
        
    ];
}

function relatorio_ponto_filtrado($conn, $id_usuario) {
    $usuario = [];

    $coleta_usuario_tabela = $conn->prepare("
        SELECT 
			ponto_dia.id_ponto, 
            inicio_ponto, 
            fim_ponto,
			email_login,
			TIMESTAMPDIFF(MINUTE, data_inicio, data_fim) AS tempo_logado,
			TIMESTAMPDIFF(MINUTE, inicio_ponto, fim_ponto) AS tempo_trabalhado
        FROM login
        JOIN ponto_dia ON login.id_usuario = ponto_dia.id_usuario
        WHERE MONTH(data_inicio) = MONTH(CURDATE())
        AND ponto_dia.id_usuario = ?;
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
    SELECT usuario.nome_usuario, usuario.email_usuario, id_login, email_login, data_inicio,
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

// alterar para deslogar depois de um tempo
function deslogar_usuario_tempo($conn, $id_login) {
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