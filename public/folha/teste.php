<?php
function gerarFolhaUsuario(array $usuario, string $mes_padrao, $conn) {
    $id_usuario = $usuario['id_usuario'];

    // 1. Verificar se a folha já existe e se está revisada
    $stmtCheck = $conn->prepare("SELECT id_folha, revisado FROM folhas WHERE id_usuario = ? AND mes_competencia = ?");
    $stmtCheck->bind_param("is", $id_usuario, $mes_padrao);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $folhaExistente = $resCheck->fetch_assoc();
    $stmtCheck->close();

    // CRÍTICO: Se a folha já foi revisada, interrompe a execução para este usuário.
    // Isso impede que um recálculo acidental altere dados já validados.
    if ($folhaExistente && $folhaExistente['revisado'] == 1) {
        return [
            'id_usuario' => $id_usuario,
            'nome_usuario' => $usuario['nome_usuario'],
            'salario_liquido' => $folhaExistente['salario_liquido'], // Ou o valor que já estava lá, se preferir buscar
            'revisado' => 1,
            'status' => 'bloqueado'
        ];
    }

    // 2. Buscar dados de salário (Sempre do cadastro de cargos para garantir o valor atual)
    $stmt = $conn->prepare("
        SELECT c.salario_bruto 
        FROM usuario u
        JOIN cargo c ON c.id_cargo = u.id_cargo
        WHERE u.id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    if (!$userData) return null;

    $salario_bruto = floatval($userData['salario_bruto']);

    // 3. Consolidação de Eventos (Proventos e Descontos variáveis)
    $stmt2 = $conn->prepare("SELECT tipo, valor FROM eventos WHERE id_usuario = ? AND mes_competencia = ?");
    $stmt2->bind_param("is", $id_usuario, $mes_padrao);
    $stmt2->execute();
    $resEventos = $stmt2->get_result();

    $total_proventos = 0;
    $total_descontos = 0;
    while ($evt = $resEventos->fetch_assoc()) {
        if ($evt["tipo"] === "provento") $total_proventos += floatval($evt["valor"]);
        else $total_descontos += floatval($evt["valor"]);
    }

    // 4. Cálculos Legais (Baseados no Salário Bruto)
    $fgts = calcularFGTS($salario_bruto);
    $inss = calcularINSS($salario_bruto);
    $irrf = calcularIRRF($salario_bruto, $inss, 0);
    $vt   = calcularVT($salario_bruto, 300);
    
    // O total de descontos soma os eventos variáveis + os descontos legais compulsórios
    $descontos_totais_calculados = $total_descontos + $inss + $irrf + $vt;
    $salario_liquido = calcularSalarioLiquido($salario_bruto, $total_proventos, $descontos_totais_calculados);

    // 5. Persistência dos Dados (Update ou Insert)
    if ($folhaExistente) {
        $stmtUpdate = $conn->prepare("
            UPDATE folhas SET
                salario_bruto = ?, total_proventos = ?, total_descontos = ?, 
                fgts = ?, inss = ?, irrf = ?, vt = ?, salario_liquido = ?
            WHERE id_folha = ?
        ");
        $stmtUpdate->bind_param(
            "dddddddi",
            $salario_bruto, $total_proventos, $descontos_totais_calculados,
            $fgts, $inss, $irrf, $vt, $salario_liquido,
            $folhaExistente['id_folha']
        );
        $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        $stmtInsert = $conn->prepare("
            INSERT INTO folhas 
            (id_usuario, mes_competencia, salario_bruto, total_proventos, total_descontos, fgts, inss, irrf, vt, salario_liquido, revisado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmtInsert->bind_param(
            "issddddddd",
            $id_usuario, $mes_padrao, $salario_bruto, $total_proventos, $descontos_totais_calculados,
            $fgts, $inss, $irrf, $vt, $salario_liquido
        );
        $stmtInsert->execute();
        $stmtInsert->close();
    }

    return [
        'id_usuario' => $id_usuario,
        'nome_usuario' => $usuario['nome_usuario'],
        'salario_liquido' => $salario_liquido,
        'revisado' => 0
    ];
}