<?php
function buscar_jornada_usuario($conn, $usuario_id, $data = null)
{
    $data = $data ?? date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT horas_diarias, dias_semana 
        FROM jornadas_trabalho 
        WHERE usuario_id = ? 
        AND data_inicio <= ?
        AND (data_fim IS NULL OR data_fim >= ?)
        ORDER BY data_inicio DESC 
        LIMIT 1
    ");

    $stmt->bind_param("iss", $usuario_id, $data, $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $jornada = $result->fetch_assoc();
    $stmt->close();

    if (!$jornada) {
        return [
            'horas_diarias' => 8.0,
            'dias_semana' => json_encode([1, 2, 3, 4, 5])
        ];
    }

    return $jornada;
}

function verificar_jornada_todos_usuarios($conn)
{
    $data_inicio = date('Y-m-01');
    $data_fim = date('Y-m-d');

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
        'periodo' => ['inicio' => $data_inicio, 'fim' => $data_fim],
        'usuarios' => $dados_grafico
    ];
}

function calcular_horas_trabalhadas($conn, $usuario_id, $data_inicio, $data_fim)
{
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

function calcular_minutos_dia($ponto)
{
    if (!$ponto['inicio_ponto'] || !$ponto['fim_ponto']) {
        return 0;
    }

    $data_inicio_completa = $ponto['data_ponto'] . ' ' . $ponto['inicio_ponto'];
    $entrada = new DateTime($data_inicio_completa);
    $data_fim_completa = $ponto['data_ponto'] . ' ' . $ponto['fim_ponto'];
    $saida = new DateTime($data_fim_completa);

    if ($saida < $entrada) {
        $saida->modify('+1 day');
    }

    $intervalo = $saida->diff($entrada);
    $minutos_total = $intervalo->days * 24 * 60;
    $minutos_total += $intervalo->h * 60;
    $minutos_total += $intervalo->i;
    return max(0, $minutos_total);
}

function calcular_horas_esperadas($conn, $usuario_id, $data_inicio, $data_fim)
{
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $current = clone $inicio;
    $total_horas = 0;
    $detalhes = [];

    while ($current <= $fim) {
        $data_atual = $current->format('Y-m-d');
        $dia_semana = (int)$current->format('N');
        $jornada = buscar_jornada_usuario($conn, $usuario_id, $data_atual);
        $dias_semana = json_decode($jornada['dias_semana'], true);
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

function verificar_feriado($conn, $data)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM feriados 
        WHERE data = ?
    ");

    $stmt->bind_param("s", $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $linha = $result->fetch_assoc();
    $stmt->close();
    return $linha['total'] > 0;
}

function verificar_jornada($conn, $usuario_id, $data_inicio, $data_fim)
{
    $trabalhadas = calcular_horas_trabalhadas($conn, $usuario_id, $data_inicio, $data_fim);
    $esperadas = calcular_horas_esperadas($conn, $usuario_id, $data_inicio, $data_fim);
    $diferenca = $trabalhadas['total_horas'] - $esperadas['total_horas'];
    $percentual = $esperadas['total_horas'] > 0 ? ($trabalhadas['total_horas'] / $esperadas['total_horas']) * 100 : 0;
    $taxa_presenca = $trabalhadas['total_horas'] / $esperadas['total_horas'];

    return [
        'usuario_id' => $usuario_id,
        'horas_trabalhadas' => $trabalhadas['total_horas'],
        'horas_esperadas' => $esperadas['total_horas'],
        'taxa_presenca' => $taxa_presenca,
        'diferenca' => round($diferenca, 2),
        'percentual' => round($percentual, 2),
    ];
}

function atualizar_assiduidade($conn, $usuario_id, $percentual)
{
    $stmt = $conn->prepare("UPDATE usuario SET assiduidade = ? WHERE id_usuario = ?");
    $stmt->bind_param("di", $percentual, $usuario_id);
    $sucesso = $stmt->execute();
    $stmt->close();
    return $sucesso;
}

function set_jornada($conn, $jornada, $hora_extra)
{
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
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Erro ao configurar jornada: " . $e->getMessage());
    }
}
