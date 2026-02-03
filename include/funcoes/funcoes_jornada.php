<?php
// Busca a jornada de trabalho do usuário em uma data específica

function buscar_jornada_usuario($conn, $usuario_id, $data = null) {
    $data = $data ?? date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT 
            tempo_jornada.jornada AS horas_diarias,
            tempo_jornada.dias_semana
        FROM grupo_setor
        JOIN setor 
            ON setor.id_setor = grupo_setor.id_setor
        JOIN tempo_jornada 
            ON tempo_jornada.id_tempo = setor.id_tempo
        WHERE grupo_setor.id_usuario = ?
        LIMIT 1;
    ");

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $jornada = $result->fetch_assoc();
    $stmt->close();

    // Retorna jornada padrão se não encontrar
    if (!$jornada) {
        return [
            'horas_diarias' => '08:00:00',
            'dias_semana' => json_encode([1, 2, 3, 4, 5])
        ];
    }

    return $jornada;
}

function verificar_jornada_todos_usuarios($conn) {
    $data_inicio = date('Y-m-01'); // Primeiro dia do mes
    $data_fim = date('Y-m-d'); // hoje
    
    $stmt_usuarios = $conn->prepare("
        SELECT id_usuario, nome_usuario 
        FROM usuario 
        WHERE conta_ativa = 1
        ORDER BY nome_usuario
    ");
    $stmt_usuarios->execute();
    $result_usuarios = $stmt_usuarios->get_result();
    
    $dados_grafico = [];
    
    while ($usuario = $result_usuarios->fetch_assoc()) {
        $usuario_id = $usuario['id_usuario'];
        $nome = $usuario['nome_usuario'];
        
        // Calcula jornada para este usuário
        $jornada = verificar_jornada($conn, $usuario_id, $data_inicio, $data_fim);
        
        $dados_grafico[] = [
            'id_usuario' => $usuario_id,
            'nome' => $nome,
            'horas_trabalhadas' => $jornada['horas_trabalhadas'],
            'horas_esperadas' => $jornada['horas_esperadas'],
            'taxa_presenca' => $jornada['taxa_presenca'],
            'percentual' => $jornada['percentual']
        ];
    }
    
    $stmt_usuarios->close();
    
    return [
        'periodo' => [
            'inicio' => $data_inicio,
            'fim' => $data_fim
        ],
        'usuarios' => $dados_grafico
    ];
}

// Soma todas as horas que o usuário trabalhou em um período
// (baseado nos registros da tabela ponto).
function calcular_horas_trabalhadas($conn, $usuario_id, $data_inicio, $data_fim) { 
    // Busca todos os pontos aprovados no período   
    $stmt = $conn->prepare("
    SELECT data_ponto,
    inicio_ponto,
    fim_ponto
    FROM ponto_dia
    WHERE id_usuario = ?
    AND data_ponto BETWEEN ? AND ?
    AND status = 'aprovado'
    ORDER BY data_ponto
    ");
    $stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_minutos = 0;
    $detalhes = [];
    
    while ($ponto = $result->fetch_assoc()) {
        $minutos_dia = calcular_minutos_dia($ponto);
        $total_minutos += $minutos_dia;
        
        $detalhes[] = [
            'data' => $ponto['data_ponto'],
            'minutos' => $minutos_dia,
            'horas' => round($minutos_dia / 60, 2)
        ];
    }
    
    $stmt->close();
    
    return [
        'total_horas' => round($total_minutos / 60, 2),
        'total_minutos' => $total_minutos,
        'detalhes' => $detalhes
    ];
}

// Calcula quantos minutos o usuário trabalhou em um dia específico, descontando o almoço.
function calcular_minutos_dia($ponto) {
    if (!$ponto['inicio_ponto'] || !$ponto['fim_ponto']) {
        return 0;
    }
    
    // Combina a data do ponto com a hora de início
    $data_inicio_completa = $ponto['data_ponto'] . ' ' . $ponto['inicio_ponto'];
    $entrada = new DateTime($data_inicio_completa);
    
    $data_fim_completa = $ponto['data_ponto'] . ' ' . $ponto['fim_ponto'];
    $saida = new DateTime($data_fim_completa);
    
    // virada de noite
    if ($saida < $entrada) {
        $saida->modify('+1 day');
    }
    
    // pegando a diferença
    $intervalo = $saida->diff($entrada);

    // se a diferença for de dias faz o calculo para transformar em minutos
    $minutos_total = $intervalo->days * 24 * 60; 

    // mesma coisa
    $minutos_total += $intervalo->h * 60;        

    // caso for minutos só recebe o valor mesmo
    $minutos_total += $intervalo->i;             
    
    
    return max(0, $minutos_total);
}

// Calcula quantas horas o usuário DEVERIA ter trabalhado no período,
// baseado na jornada configurada.

function calcular_horas_esperadas($conn, $usuario_id, $data_inicio, $data_fim) {
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $current = clone $inicio;
    
    $total_horas = 0;
    $detalhes = [];
    
    while ($current <= $fim) {
        $data_atual = $current->format('Y-m-d');
        $dia_semana = (int)$current->format('N'); // 1=segunda, 7=domingo
        
        // Busca jornada válida para esta data
        $jornada = buscar_jornada_usuario($conn, $usuario_id, $data_atual);
        $dias_semana = json_decode($jornada['dias_semana'], true);
        
        // Verifica se é dia útil (não é feriado e está nos dias de trabalho)
        $dia_util = in_array($dia_semana, $dias_semana);
        $feriado = verificar_feriado($conn, $data_atual);
        
        if ($dia_util && !$feriado) {
            $horas_dia = floatval($jornada['horas_diarias']);
            $total_horas += $horas_dia;
            
            $detalhes[] = [
                'data' => $data_atual,
                'horas' => $horas_dia,
            ];
        }
        
        $current->modify('+1 day');
    }
    
    return [
        'total_horas' => round($total_horas, 2),
        'detalhes' => $detalhes
    ];
}

// simples, preciso explicar isto também?
function verificar_feriado($conn, $data) {
    $stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM feriados WHERE data = ?
    ");
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $linha = $result->fetch_assoc();
    $stmt->close();
    
    return $linha['total'] > 0;
}

// Verifica a jornada de trabalho
function verificar_jornada($conn, $usuario_id, $data_inicio, $data_fim) {
    // calcula horas trabalhadas (baseado nos pontos aprovados)
    $trabalhadas = calcular_horas_trabalhadas($conn, $usuario_id, $data_inicio, $data_fim);
    
    // calcula horas esperadas (baseado na jornada configurada)
    $esperadas = calcular_horas_esperadas($conn, $usuario_id, $data_inicio, $data_fim);
    
    $diferenca = $trabalhadas['total_horas'] - $esperadas['total_horas'];
    
    $percentual = $esperadas['total_horas'] > 0 ?
    ($trabalhadas['total_horas'] / $esperadas['total_horas']) * 100 : 0;
    
    $taxa_presenca = $esperadas['total_horas'] > 0 ? 
    $trabalhadas['total_horas'] / $esperadas['total_horas'] : 0;
    
    return [
        'usuario_id' => $usuario_id,
        'horas_trabalhadas' => $trabalhadas['total_horas'],
        'horas_esperadas' => $esperadas['total_horas'],
        'taxa_presenca' => $taxa_presenca,
        'diferenca' => round($diferenca, 2),
        'percentual' => round($percentual, 2),
    ];
}

// Atualiza a assiduidae do usuário

function atualizar_assiduidade($conn, $usuario_id, $percentual) {
    $stmt = $conn->prepare("UPDATE usuario SET assiduidade = ? WHERE id_usuario = ?");
    $stmt->bind_param("di", $percentual, $usuario_id);
    $sucesso = $stmt->execute();
    $stmt->close();
    
    return $sucesso;
}

function set_jornada($conn, $jornada, $hora_extra) {
    $conn->begin_transaction();

    try {
        $conn->query("TRUNCATE TABLE tempo_jornada");

        $jornada_formatada = $jornada . ':00';
        $hora_extra_formatada = $hora_extra . ':00';

        $stmt = $conn->prepare("
            INSERT INTO tempo_jornada (jornada, maximo_hora_extra)
            VALUES (?, ?);
        ");

        $stmt->bind_param('ss', $jornada_formatada, $hora_extra_formatada);
        $sucesso = $stmt->execute();

        $stmt->close();
        $conn->commit();

        return $sucesso;
    } catch(Exception $e) {
        $conn->rollback();
        throw new Exception("Erro ao configurar jornada: " . $e->getMessage());
    }
}

function get_pessoas_setor($conn, $id_setor) {
    $stmt = $conn->prepare("
        SELECT grupo_setor.id_usuario, u.nome_usuario 
        FROM grupo_setor
        INNER JOIN usuario u ON grupo_setor.id_usuario = u.id_usuario
        WHERE grupo_setor.id_setor = ?
        ORDER BY u.nome_usuario
    ");
    
    $stmt->bind_param("i", $id_setor);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = [
            'id_usuario' => $row['id_usuario'],
            'nome_usuario' => $row['nome_usuario']
        ];
    }
    
    return $dados;
}

function set_setor($conn, $usuarios_selecionados, $nome_setor, $id_setor, $id_tempo = null) {
    // Atualiza nome do setor e id_tempo
    $stmt = $conn->prepare("UPDATE setor SET nome_setor = ?, id_tempo = ? WHERE id_setor = ?");
    $stmt->bind_param("sii", $nome_setor, $id_tempo, $id_setor);
    $stmt->execute();

    // Remove vínculos antigos
    $stmt = $conn->prepare("DELETE FROM grupo_setor WHERE id_setor = ?");
    $stmt->bind_param("i", $id_setor);
    $stmt->execute();

    // Insere novos vínculos
    $stmt = $conn->prepare("INSERT INTO grupo_setor (id_setor, id_usuario) VALUES (?, ?)");

    foreach ($usuarios_selecionados as $id_usuario) {
        $stmt->bind_param("ii", $id_setor, $id_usuario);
        $stmt->execute();
    }
}

function cadastrar_setor($conn, $nome_setor, $id_tempo = null, array $usuarios_selecionado) {
    $stmt = $conn->prepare("INSERT INTO setor (nome_setor, id_tempo) VALUES (?, ?)");
    $stmt->bind_param("si", $nome_setor, $id_tempo);
    
    $existente = $conn->prepare("SELECT id_setor FROM setor WHERE nome_setor = ?");
    $existente->bind_param('s', $nome_setor);
    $existente->execute();
    $resultado_existente = $existente->get_result();

    if ($resultado_existente->num_rows != 0) {
        return [
            'sucesso' => false,
            'mensagem' => 'já existe um setor com este nome'
        ];
    }

    if (!$stmt->execute()) {
        throw new Exception("Erro ao cadastrar setor: " . $stmt->error);
    }
    
    // Obter ID do setor recém-criado
    $id_setor = $conn->insert_id;
    
    // Inserir vínculos com usuários (se houver)
    if (count($usuarios_selecionado) > 0) {
        $stmt = $conn->prepare("INSERT INTO grupo_setor (id_setor, id_usuario) VALUES (?, ?)");
        
        foreach ($usuarios_selecionado as $id_usuario) {
            $stmt->bind_param("ii", $id_setor, $id_usuario);
            $stmt->execute();
        }
    }

    return [
        'sucesso' => true,
        'mensagem' => 'Setor cadastrado!',
        'id_setor' => $id_setor
    ];
}

function get_tempo($conn, $tipo = null) {
    $tempo = $conn->query("SELECT * FROM tempo_jornada ORDER BY id_tempo");
    $dados = [];
    while ($t = $tempo->fetch_assoc()) {
        $dados[] = [
            "id_tempo" => $t['id_tempo'],
            "descricao" => $t['descricao'],
            "tempo_jornada" => $t['jornada'],
            "max_hora_extra" => $t['maximo_hora_extra']
        ];
    }
    return $dados;
}
?>