<?php

// O FGTS corresponde a 8% do salário bruto
function calcularFGTS(float $salario_bruto): float {
    return round($salario_bruto * 0.08, 2);
}

// O cálculo é progressivo por faixas salariais, sem usar arrays
function calcularINSS(float $salario_bruto): float {
    if ($salario_bruto <= 1518.00) {
        // Faixa 1: até R$1518,00, alíquota 7,5%
        return round($salario_bruto * 0.075, 2);
    } elseif ($salario_bruto <= 2793.88) {
        // Faixa 2: de R$1518,01 a R$2793,88, alíquota 9% sobre o valor acima de 1518
        $parte1 = 1518.00 * 0.075;
        $parte2 = ($salario_bruto - 1518.00) * 0.09;
        return round($parte1 + $parte2, 2);
    } elseif ($salario_bruto <= 4190.83) {
        // Faixa 3: de R$2793,89 a R$4190,83, alíquota 12% sobre o valor acima de 2793,88
        $parte1 = 1518.00 * 0.075;
        $parte2 = (2793.88 - 1518.00) * 0.09;
        $parte3 = ($salario_bruto - 2793.88) * 0.12;
        return round($parte1 + $parte2 + $parte3, 2);
    } elseif ($salario_bruto <= 8157.41) {
        // Faixa 4: de R$4190,84 a R$8157,41, alíquota 14% sobre o valor acima de 4190,83
        $parte1 = 1518.00 * 0.075;
        $parte2 = (2793.88 - 1518.00) * 0.09;
        $parte3 = (4190.83 - 2793.88) * 0.12;
        $parte4 = ($salario_bruto - 4190.83) * 0.14;
        return round($parte1 + $parte2 + $parte3 + $parte4, 2);
    } else {
        // Salário acima do teto do INSS 2025
        // Calcula o máximo possível de desconto (soma das faixas até R$8157,41)
        $parte1 = 1518.00 * 0.075;
        $parte2 = (2793.88 - 1518.00) * 0.09;
        $parte3 = (4190.83 - 2793.88) * 0.12;
        $parte4 = (8157.41 - 4190.83) * 0.14;
        return round($parte1 + $parte2 + $parte3 + $parte4, 2);
    }
}


// Recebe o salário bruto, INSS e número de dependentes
// Aplica dedução por dependente (R$189,59) e usa tabela progressiva
function calcularIRRF(float $salario_bruto, float $inss, int $dependentes = 0): float {
    $deducao_dependente = 189.59; // dedução por dependente
    $base_calculo = $salario_bruto - $inss - ($dependentes * $deducao_dependente);

    if ($base_calculo <= 1903.98) {
        return 0.0; // isento
    } elseif ($base_calculo <= 2826.65) {
        return round(($base_calculo * 0.075) - 142.80, 2);
    } elseif ($base_calculo <= 3751.05) {
        return round(($base_calculo * 0.15) - 354.80, 2);
    } elseif ($base_calculo <= 4664.68) {
        return round(($base_calculo * 0.225) - 636.13, 2);
    } else {
        return round(($base_calculo * 0.275) - 869.36, 2);
    }
}


// O desconto máximo permitido é 6% do salário bruto
function calcularVT(float $salario_bruto, float $valor_vt = 0): float {
    $max_desconto = $salario_bruto * 0.06;
    if ($valor_vt <= 0) {
        return 0;
    }
    return min($valor_vt, $max_desconto);
}


// Fórmula: salário líquido = salário bruto + total de proventos - total de descontos
function calcularSalarioLiquido(float $salario_bruto, float $total_proventos, float $total_descontos): float {
    return round($salario_bruto + $total_proventos - $total_descontos, 2);
}

?>
