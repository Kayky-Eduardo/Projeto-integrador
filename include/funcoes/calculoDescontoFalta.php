<?php
// Importa funções auxiliares relacionadas a banco de horas
require_once 'funcoes_banco_horas.php';

function calcularEAplicarDescontoFalta($conn, $id_usuario, $mes_comp, $user, $folha) {

    /*
       1. BUSCAR MINUTOS TRABALHADOS NO MÊS
       Somamos o tempo entre início e fim do ponto diário,
       descontando as pausas feitas no mesmo dia.
    */
    $sql_trabalhado = $conn->prepare("
        SELECT 
            -- Soma o tempo trabalhado no mês (em minutos)
            SUM(
                TIMESTAMPDIFF(MINUTE, p.inicio_ponto, p.fim_ponto)  -- tempo bruto do dia
                - IFNULL(pa.duracao_minutos, 0)                    -- desconto das pausas
            ) AS minutos
        FROM ponto_dia p

        -- Soma as pausas feitas no mesmo dia
        LEFT JOIN (
            SELECT 
                id_usuario, 
                data, 
                SUM(duracao_minutos) AS duracao_minutos
            FROM pausa 
            GROUP BY id_usuario, data
        ) pa 
            ON pa.id_usuario = p.id_usuario
            AND pa.data = p.data_ponto

        WHERE 
            p.id_usuario = ?
            -- Considera apenas pontos finalizados
            AND p.fim_ponto IS NOT NULL
            -- Filtra registros do mês informado
            AND DATE_FORMAT(p.data_ponto, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
    ");

    $sql_trabalhado->bind_param("is", $id_usuario, $mes_comp);
    $sql_trabalhado->execute();
    $minutos_trabalhados = (int) ($sql_trabalhado->get_result()->fetch_assoc()['minutos'] ?? 0);

    /*
       2. CALCULAR MINUTOS ESPERADOS NO MÊS
       Considera jornada definida no setor do usuário, feriados e dias da semana permitidos
    */
    $sql_minutos_esperados = $conn->prepare("
        -- Lista todos os dias do mês informado
        WITH RECURSIVE dias_mes AS (
            SELECT DATE_FORMAT(?, '%Y-%m-01') AS dia
            UNION ALL
            SELECT dia + INTERVAL 1 DAY
            FROM dias_mes
            WHERE dia + INTERVAL 1 DAY <= LAST_DAY(?)
        )
        SELECT 
            -- Soma a jornada diária e o limite de hora extra (em minutos)
            SUM(TIME_TO_SEC(tj.jornada) / 60) AS minutos_esperados,
            SUM(TIME_TO_SEC(tj.maximo_hora_extra) / 60) AS minutos_max_extra
        FROM dias_mes dm
        JOIN grupo_setor gs ON gs.id_usuario = ?
        JOIN setor s ON s.id_setor = gs.id_setor
        JOIN tempo_jornada tj ON tj.id_tempo = s.id_tempo
        LEFT JOIN feriados f ON f.data = dm.dia
        WHERE 
            -- Remove feriados
            f.data IS NULL
            -- Considera apenas os dias da semana permitidos na jornada
            AND JSON_CONTAINS(
                tj.dias_semana,
                JSON_ARRAY(DAYOFWEEK(dm.dia) - 1)
            )
    ");

    $sql_minutos_esperados->bind_param("ssi", $mes_comp, $mes_comp, $id_usuario);
    $sql_minutos_esperados->execute();
    $result = $sql_minutos_esperados->get_result()->fetch_assoc();

    $minutos_esperados  = (int) ($result['minutos_esperados'] ?? 0);
    $minutos_max_extra  = (int) ($result['minutos_max_extra'] ?? 0);

    /*
       3. CALCULAR DIFERENÇA ENTRE MINUTOS TRABALHADOS E ESPERADOS
       Se negativo, significa falta (menos minutos trabalhados que o esperado).
       Se positivo, significa horas extras (mais minutos trabalhados que o esperado).
    */
    $diferenca_minutos = $minutos_trabalhados - $minutos_esperados;
    $minutos_extra_real = max(0, $diferenca_minutos); // total real feito

    if ($diferenca_minutos > 0 && $minutos_max_extra > 0) {
        $diferenca_minutos = min($diferenca_minutos, $minutos_max_extra);
    }

    $minutos_extra_excedente = max(0, $minutos_extra_real - $diferenca_minutos);

    /*
       4. CALCULAR VALOR DO MINUTO
       Baseado no salário bruto dividido pelos minutos esperados no mês.
       Evita divisão por zero.
    */
    $valor_minuto = ($minutos_esperados > 0)
        ? ($user['salario_bruto'] / $minutos_esperados)
        : 0;

    /*
       5. GERENCIAR EVENTOS DE DESCONTO E PROVENTO
       Define as descrições que serão usadas para nomear os eventos.
    */
    $descricao_desconto = "Desconto por falta";
    $descricao_provento = "Horas extras";

    // Verifica se já existe evento de desconto para este usuário, mês e descrição
    $sql_evento_desconto = $conn->prepare("
        SELECT id_evento 
        FROM eventos 
        WHERE id_usuario = ?
          AND mes_competencia = ?
          AND descricao = ?
    ");
    $sql_evento_desconto->bind_param("iss", $id_usuario, $mes_comp, $descricao_desconto);
    $sql_evento_desconto->execute();
    $evento_desconto = $sql_evento_desconto->get_result()->fetch_assoc();

    // Verifica se já existe evento de provento (horas extras)
    $sql_evento_provento = $conn->prepare("
        SELECT id_evento 
        FROM eventos 
        WHERE id_usuario = ?
          AND mes_competencia = ?
          AND descricao = ?
    ");
    $sql_evento_provento->bind_param("iss", $id_usuario, $mes_comp, $descricao_provento);
    $sql_evento_provento->execute();
    $evento_provento = $sql_evento_provento->get_result()->fetch_assoc();

    // Se há falta (diferença negativa)
    if ($diferenca_minutos < 0) {
        // Calcula valor do desconto multiplicando minutos em falta pelo valor do minuto
        $valor_desconto_falta = abs($diferenca_minutos) * $valor_minuto;

        if ($evento_desconto) {
            // Atualiza evento de desconto já existente
            $sql_upd = $conn->prepare("UPDATE eventos SET valor = ? WHERE id_evento = ?");
            $sql_upd->bind_param("di", $valor_desconto_falta, $evento_desconto['id_evento']);
            $sql_upd->execute();
        } else {
            // Cria novo evento de desconto
            $sql_ins = $conn->prepare("
                INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
                VALUES (?, 'desconto', ?, ?, ?)
            ");
            $sql_ins->bind_param("isds", $id_usuario, $descricao_desconto, $valor_desconto_falta, $mes_comp);
            $sql_ins->execute();
        }

        // Remove evento de horas extras, se existir, para evitar duplicidade
        if ($evento_provento) {
            $sql_del = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
            $sql_del->bind_param("i", $evento_provento['id_evento']);
            $sql_del->execute();
        }

    // Se há horas extras (diferença positiva)
    } elseif ($diferenca_minutos > 0) {
        // Calcula valor do provento multiplicando minutos extras pelo valor do minuto
        $valor_provento = $diferenca_minutos * $valor_minuto;

        if ($evento_provento) {
            // Atualiza evento de provento já existente
            $sql_upd = $conn->prepare("UPDATE eventos SET valor = ? WHERE id_evento = ?");
            $sql_upd->bind_param("di", $valor_provento, $evento_provento['id_evento']);
            $sql_upd->execute();
        } else {
            // Cria novo evento de provento
            $sql_ins = $conn->prepare("
                INSERT INTO eventos (id_usuario, tipo, descricao, valor, mes_competencia)
                VALUES (?, 'provento', ?, ?, ?)
            ");
            $sql_ins->bind_param("isds", $id_usuario, $descricao_provento, $valor_provento, $mes_comp);
            $sql_ins->execute();
        }

        // Remove evento de desconto por falta, se existir, para manter coerência
        if ($evento_desconto) {
            $sql_del = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
            $sql_del->bind_param("i", $evento_desconto['id_evento']);
            $sql_del->execute();
        }

    } else {
        // Caso não haja diferença, remove ambos eventos se existirem para limpar a folha
        if ($evento_desconto) {
            $sql_del = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
            $sql_del->bind_param("i", $evento_desconto['id_evento']);
            $sql_del->execute();
        }
        if ($evento_provento) {
            $sql_del = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
            $sql_del->bind_param("i", $evento_provento['id_evento']);
            $sql_del->execute();
        }
    }

    /*
       6. ATUALIZAR TOTAIS NA FOLHA DE PAGAMENTO
       Soma valores dos eventos para atualizar proventos e descontos,
       somando também descontos fixos (VT, INSS, IRRF).
       Atualiza salário líquido no banco.
    */
    $sql_totais = $conn->prepare("
    SELECT 
        -- Soma dos valores que são proventos
        SUM(CASE WHEN tipo = 'provento' THEN valor ELSE 0 END) AS proventos,
        -- Soma dos valores que são descontos
        SUM(CASE WHEN tipo = 'desconto' THEN valor ELSE 0 END) AS descontos
    FROM eventos
    WHERE 
        id_usuario = ?
        -- Filtra pelo mês de competência
        AND mes_competencia = ?
    ");
    $sql_totais->bind_param("is", $id_usuario, $mes_comp);
    $sql_totais->execute();
    $totais = $sql_totais->get_result()->fetch_assoc();

    // Valores totais de proventos e descontos encontrados na tabela eventos
    $total_proventos = $totais['proventos'] ?? 0;
    $total_descontos_eventos = $totais['descontos'] ?? 0;

    // Soma dos descontos fixos registrados na folha (vale transporte, INSS, IRRF)
    $descontos_fixos = ($folha['vt'] ?? 0) + ($folha['inss'] ?? 0) + ($folha['irrf'] ?? 0);
    // Total geral de descontos somando fixos + eventos
    $total_descontos = $descontos_fixos + $total_descontos_eventos;

    // Calcula salário líquido: bruto + proventos - descontos (nunca negativo)
    $salario_liquido = $folha['salario_bruto'] + $total_proventos - $total_descontos;
    if ($salario_liquido < 0) {
        $salario_liquido = 0;
    }

    // Atualiza os totais na tabela de folhas
    $sql_folha_upd = $conn->prepare("
        UPDATE folhas 
        SET total_proventos = ?, 
            total_descontos = ?, 
            salario_liquido = ?
        WHERE id_folha = ?
    ");
    $sql_folha_upd->bind_param("dddi", $total_proventos, $total_descontos, $salario_liquido, $folha['id_folha']);
    $sql_folha_upd->execute();

}
?> 