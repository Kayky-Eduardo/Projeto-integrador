<?php
function evolucao_presenca($conn)
{
    $data_inicio = date('Y-m-01');
    $data_fim = date('Y-m-d');
    $total_usuarios = calculo_total_usuarios($conn);

    $stmt = $conn->prepare("
        SELECT data_ponto,
        COUNT(DISTINCT id_usuario) 
        AS presentes
        FROM ponto_dia
        WHERE status = 'aprovado'
        AND data_ponto 
        BETWEEN ? 
        AND ?
        GROUP BY data_ponto
        ORDER BY data_ponto 
        ASC
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
        $ultima_data = end($dados)['data'];
        $primeira_data = $dados[0]['data'];
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

function calculo_total_usuarios($conn)
{
    $stmt_total = $conn->prepare("
        SELECT COUNT(*) 
        AS total_usuarios
        FROM usuario
    ");

    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $total_usuarios = 0;

    if ($linha_total = $result_total->fetch_assoc()) {
        return $total_usuarios = (int)$linha_total['total_usuarios'];
    }

    $stmt_total->close();
    return $total_usuarios;
}

function dados_grafico($conn)
{
    $pesquisa_trabalhando = $conn->prepare("
        SELECT COUNT(*) 
        AS total_trabalhando
        FROM ponto_dia
        WHERE inicio_ponto IS NOT NULL 
        AND (fim_ponto IS NULL OR fim_ponto = '00:00:00')
        AND data_ponto = CURDATE()
    ");

    $pesquisa_trabalhando->execute();
    $result = $pesquisa_trabalhando->get_result();

    if ($linha = $result->fetch_assoc()) {
        $numero_presente = $linha['total_trabalhando'];
    } else {
        $numero_presente = 0;
    }

    $total_usuarios = calculo_total_usuarios($conn);
    $numero_ausentes = $total_usuarios - (int)$numero_presente;
    $numero_pausa = 0;

    $pesquisa_horario_completo = $conn->prepare("
        SELECT COUNT(*) AS total_completo
        FROM ponto_dia
        WHERE inicio_ponto IS NOT NULL 
        AND (fim_ponto IS NOT NULL AND fim_ponto != '00:00:00')
        AND data_ponto = CURDATE();
    ");

    $pesquisa_horario_completo->execute();
    $result = $pesquisa_horario_completo->get_result();

    if ($linha = $result->fetch_assoc()) {
        $numero_horario_completo = (int)$linha['total_completo'];
    } else {
        $numero_horario_completo = 0;
    }

    $valores = [
        $numero_presente,
        $numero_ausentes,
        $numero_pausa,
        $numero_horario_completo
    ];

    return $valores;
}

function filtrar($conn, $tipo)
{
    if ($tipo == 'presentes') {
        $presentes = [];
        $filtro_presente = $conn->prepare("
            SELECT usuario.email_usuario, ponto_dia.*, TIMESTAMPDIFF(MINUTE, inicio_ponto, NOW()) 
            AS tempo_logado
            FROM ponto_dia 
            JOIN usuario 
            ON ponto_dia.id_usuario = usuario.id_usuario
            WHERE inicio_ponto IS NOT NULL 
            AND (fim_ponto IS NULL or fim_ponto = '00:00:00')
            AND data_ponto = CURDATE();
        ");

        $filtro_presente->execute();
        $result = $filtro_presente->get_result();

        while ($linha = $result->fetch_assoc()) {
            $presentes[] = $linha;
        }

        return $presentes;
    }

    if ($tipo == 'ausentes') {
        $ausentes = [];
        $filtro_ausente = $conn->prepare("
            SELECT usuario.email_usuario, usuario.id_usuario, IFNULL(TIMESTAMPDIFF(MINUTE, inicio_ponto, NOW()), 0) 
            AS tempo_logado 
            FROM usuario
            LEFT JOIN ponto_dia 
            ON usuario.id_usuario = ponto_dia.id_usuario
            WHERE ponto_dia.id_ponto is null 
            OR ponto_dia.inicio_ponto = '00:00:00'
        ");

        $filtro_ausente->execute();
        $result = $filtro_ausente->get_result();

        while ($linha = $result->fetch_assoc()) {
            $ausentes[] = $linha;
        }

        return $ausentes;
    }

    if ($tipo == 'horario') {
        $horario_completo = [];

        $filtro_horario_completo = $conn->prepare("
            SELECT usuario.email_usuario, ponto_dia.*, TIMESTAMPDIFF(MINUTE, inicio_ponto, fim_ponto) 
            AS tempo_logado 
            FROM ponto_dia 
            JOIN usuario 
            ON ponto_dia.id_usuario = usuario.id_usuario
            WHERE inicio_ponto IS NOT NULL 
            AND (fim_ponto IS NOT NULL AND fim_ponto != '00:00:00')
            AND ponto_dia.data_ponto = CURDATE()
            ;
        ");

        $filtro_horario_completo->execute();
        $result = $filtro_horario_completo->get_result();

        while ($linha = $result->fetch_assoc()) {
            $horario_completo[] = $linha;
        }

        return $horario_completo;
    }
}

function coleta_usuarios($conn)
{
    $coleta_usuario = $conn->prepare("
        SELECT id_usuario, nome_usuario
        FROM usuario
        WHERE conta_ativa = 1;
    ");

    $coleta_usuario->execute();
    $result = $coleta_usuario->get_result();

    while ($linha = $result->fetch_assoc()) {
        $usuarios[] = $linha;
    }

    return $usuarios;
}

function filtrar_usuario($conn)
{
    $data_inicio = date('Y-m-01');
    $data_fim = date('Y-m-d 23:59:59');

    $coleta_usuario = $conn->prepare("
        SELECT saldo_minutos, nome_usuario, ultima_atualizacao
        FROM banco_horas
        JOIN usuario 
        ON banco_horas.id_usuario = usuario.id_usuario
        WHERE ultima_atualizacao between ? 
        AND ?;
    ");

    $coleta_usuario->bind_param("ss", $data_inicio, $data_fim);
    $coleta_usuario->execute();
    $result = $coleta_usuario->get_result();
    $dados_grafico = [];

    while ($usuario = $result->fetch_assoc()) {
        $dados_grafico[] = [
            'nome_usuario' => $usuario['nome_usuario'],
            'saldo_horas' => $usuario['saldo_minutos'] / 60,
            'data' => $usuario['ultima_atualizacao']
        ];
    }

    $coleta_usuario->close();

    return [
        'periodo' => [
            'inicio' => $data_inicio,
            'fim' => $data_fim
        ],

        'usuarios' => $dados_grafico
    ];
}

function relatorio_ponto_filtrado($conn, $id_usuario)
{
    $usuario = [];

    $coleta_usuario_tabela = $conn->prepare("
        SELECT id_login, email_login, data_inicio, data_fim, TIMESTAMPDIFF(MINUTE, data_inicio, data_fim) 
        AS tempo_logado
        FROM login
        WHERE MONTH(data_inicio) = MONTH(CURDATE())
        AND id_usuario = ?;
    ");

    $coleta_usuario_tabela->bind_param("i", $id_usuario);
    $coleta_usuario_tabela->execute();
    $result = $coleta_usuario_tabela->get_result();

    while ($linha = $result->fetch_assoc()) {
        $usuario[] = $linha;
    }

    return $usuario;
}

function get_logados($conn)
{
    $coleta_usuario_tabela = $conn->prepare("
        SELECT usuario.id_usuario, usuario.email_usuario, id_login, email_login, data_inicio, TIMESTAMPDIFF(MINUTE, data_inicio, NOW()) 
        AS tempo_logado
        FROM login 
        LEFT JOIN usuario on login.id_usuario = usuario.id_usuario
        WHERE MONTH(data_inicio) = MONTH(CURDATE())
        AND data_fim IS NULL;
    ");

    $coleta_usuario_tabela->execute();
    $result = $coleta_usuario_tabela->get_result();

    while ($linha = $result->fetch_assoc()) {
        $usuario[] = $linha;
    }

    return $usuario;
}

function deslogar_usuario($conn, $id_login)
{
    $stmt = $conn->prepare("
        UPDATE login 
        SET data_fim = NOW() 
        WHERE id_login = ? 
        AND data_fim IS NULL
    ");

    $stmt->bind_param("i", $id_login);
    $sucesso = $stmt->execute();

    if ($sucesso && $stmt->affected_rows > 0) {
        return ['success' => true, 'message' => 'Usuário deslogado com sucesso'];
    } else {
        return ['success' => false, 'message' => 'Nenhum registro foi atualizado'];
    }
}
