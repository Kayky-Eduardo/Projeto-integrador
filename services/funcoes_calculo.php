<?php

// FGTS = 8% do salário bruto (não é descontado do funcionário, é depósito do empregador)
function calcularFGTS(float $salario_bruto): float {
    return round($salario_bruto * 0.08, 2);
}


// Cálculo progressivo do INSS atualizado (tabela 2024/2025)
function calcularINSS(float $salario_bruto): float {

    // Limites das faixas do INSS
    $faixa1_limite = 1412.00;
    $faixa2_limite = 2666.68;
    $faixa3_limite = 4000.03;
    $faixa4_limite = 7786.02; // teto previdenciário

    $total = 0.0;

    // Faixa 1 – 7,5% até R$ 1.412,00
    if ($salario_bruto > 0) {
        $base = min($salario_bruto, $faixa1_limite); // aplica até o limite
        $total += $base * 0.075;
    }

    // Faixa 2 – 9% entre R$ 1.412,01 e 2.666,68
    if ($salario_bruto > $faixa1_limite) {
        $base = min($salario_bruto, $faixa2_limite) - $faixa1_limite;
        $total += $base * 0.09;
    }

    // Faixa 3 – 12% entre R$ 2.666,69 e 4.000,03
    if ($salario_bruto > $faixa2_limite) {
        $base = min($salario_bruto, $faixa3_limite) - $faixa2_limite;
        $total += $base * 0.12;
    }

    // Faixa 4 – 14% entre R$ 4.000,04 e o teto (R$ 7.786,02)
    if ($salario_bruto > $faixa3_limite) {
        $base = min($salario_bruto, $faixa4_limite) - $faixa3_limite;
        $total += $base * 0.14;
    }

    return round($total, 2);
}


// IRRF atualizado com dedução por dependentes
function calcularIRRF(float $salario_bruto, float $inss, int $dependentes = 0): float {

    $deducao_dependente = 189.59;

    // Base de cálculo = salário - INSS - dedução dos dependentes
    $base_calculo = $salario_bruto - $inss - ($dependentes * $deducao_dependente);

    // Faixa 1 – isento
    if ($base_calculo <= 1903.98) {
        return 0.00;

    // Faixa 2 – 7,5%
    } elseif ($base_calculo <= 2826.65) {
        return round(($base_calculo * 0.075) - 142.80, 2);

    // Faixa 3 – 15%
    } elseif ($base_calculo <= 3751.05) {
        return round(($base_calculo * 0.15) - 354.80, 2);

    // Faixa 4 – 22,5%
    } elseif ($base_calculo <= 4664.68) {
        return round(($base_calculo * 0.225) - 636.13, 2);

    // Faixa 5 – 27,5%
    } else {
        return round(($base_calculo * 0.275) - 869.36, 2);
    }
}


// Cálculo do Vale-Transporte (desconto limitado a 6% do salário)
function calcularVT(float $salario_bruto, float $valor_vt = 0): float {

    // Valor máximo que pode ser descontado (6% do salário)
    $max_desconto = round($salario_bruto * 0.06, 2);

    // O funcionário paga o menor entre: valor real do VT ou 6% do salário
    return round(min($valor_vt, $max_desconto), 2);
}


// Cálculo do salário líquido final
function calcularSalarioLiquido(float $salario_bruto, float $total_proventos, float $total_descontos): float {

    // Salário líquido = salário base + proventos - descontos
    return round($salario_bruto + $total_proventos - $total_descontos, 2);
}

?>
