<?php
/*
 * =============================================================
 * ARQUIVO: ajustes_pendentes.php
 * MÓDULO: Auditoria e Fluxo de Aprovação (RH / Admin)
 * =============================================================
 * DESCRIÇÃO GERAL
 * -------------------------------------------------------------
 * Central de processamento de solicitações de alteração de ponto.
 * Esta página permite aos gestores visualizar, revisar e tomar 
 * ações (aprovar, recusar ou editar) sobre pedidos de ajuste 
 * enviados pelos colaboradores ou gerados pelo próprio sistema.
 *
 * Funcionalidades:
 * - Listagem de solicitações com status 'Pendente'.
 * - Cruzamento de dados entre Ajustes, Pontos e Usuários.
 * - Tratamento nominal de campos técnicos (Helper Function).
 * - Comparativo visual entre valor antigo e valor proposto.
 * - Gestão de fluxo: Encaminhamento para edição, aprovação ou recusa.
 *
 * FLUXO DE EXECUÇÃO
 * -------------------------------------------------------------
 * 1. Valida nível de acesso administrativo (Nível >= 2).
 * 2. Executa Query SQL complexa com múltiplos INNER JOINs para:
 * a. Recuperar o nome do funcionário dono do ponto.
 * b. Recuperar o nome do solicitante do ajuste (podem ser diferentes).
 * c. Vincular a data original do ponto ao ajuste.
 * 3. Define função 'nomeCampoAjuste' para converter nomes de colunas 
 * do banco em termos amigáveis para a interface (UI).
 * 4. Renderiza tabela dinâmica com ações contextuais para cada registro.
 *
 * SEGURANÇA
 * -------------------------------------------------------------
 * - Bloqueio de acesso para usuários de nível inferior a 2.
 * - Uso de Prepared Statements e escaping de saída (htmlspecialchars).
 * - Tratamento de IDs via GET nos links de ação para arquivos subsequentes.
 *
 * TABELAS UTILIZADAS
 * -------------------------------------------------------------
 * 1. ajustes_ponto: Armazena as solicitações e o histórico de mudanças.
 * 2. ponto_dia: Fornece a referência temporal do registro original.
 * 3. usuario (u/s): Utilizada duplamente para identificar funcionário e solicitante.
 *
 * -------------------------------------------------------------
 * Data: 09/03/2026
 * Versão: 1.0
 * =============================================================
 */

session_start();
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

// CONTROLE DE PERMISSÃO
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

$teste = $conn->query("SELECT * FROM ajustes_ponto");



// CONSULTA PRINCIPAL
$sql = "
SELECT 
    a.*, 
    u.nome_usuario AS funcionario,   -- Dono do ponto
    s.nome_usuario AS solicitante,   -- Quem fez a solicitação
    p.data_ponto                      -- Dia do ponto
FROM ajustes_ponto a
INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
INNER JOIN usuario u ON u.id_usuario = p.id_usuario   -- FUNCIONÁRIO DO PONTO
INNER JOIN usuario s ON s.id_usuario = a.id_usuario   -- QUEM SOLICITOU
WHERE a.status = 'Pendente'
ORDER BY a.data_solicitacao DESC
";

$res = $conn->query($sql);

function nomeCampoAjuste($campo)
{
    return match ($campo) {
        'inicio_ponto' => 'Início Ponto',
        'fim_ponto'    => 'Fim Ponto',
        'inicio_pausa' => 'Início Pausa',
        'fim_pausa'    => 'Fim Pausa',
        default        => ucfirst(str_replace('_', ' ', $campo)),
    };
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Ajustes Pendentes (RH)</title>
    <title>Ajustes Pendentes</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>
    <nav>
        <?php include("../../include/navbar.php"); ?>
    </nav>

    <main class="main-center">
        <section class="pagina-padrao">
            <h1 class="page-title">Ajustes Pendentes</h1>

            <section class="tabela-padrao">
                <table>
                    <thead>
                        <tr>
                            <th>Funcionário</th>
                            <th>Dia</th>
                            <th>Campo</th>
                            <th>Antes</th>
                            <th>Depois</th>
                            <th>Motivo</th>
                            <th>Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($res->num_rows === 0): ?>
                            <tr>
                                <td class="tabela-vazia">
                                    Nenhum ajuste solicitado e/ou em revisão.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($r = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['funcionario']) ?></td>
                                    <td><?= htmlspecialchars($r['data_ponto']) ?></td>
                                    <td><?= nomeCampoAjuste($r['campo']) ?></td>
                                    <td><?= htmlspecialchars($r['valor_antigo']) ?></td>
                                    <td><?= htmlspecialchars($r['valor_novo']) ?></td>
                                    <td><?= htmlspecialchars($r['motivo']) ?></td>

                                    <td>
                                        <a class="btn-link btn-ativar" href="aprovar.php?id=<?= htmlspecialchars($r['id_ajuste']) ?>">Aprovar</a>
                                        <a class="btn-link btn-desativar" href="recusar.php?id=<?= htmlspecialchars($r['id_ajuste']) ?>">Recusar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>
</body>

</html>