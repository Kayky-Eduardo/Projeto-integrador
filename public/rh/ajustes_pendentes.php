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

// =====================
// CONTROLE DE PERMISSÃO
// =====================

// Somente usuários de nível 2 ou maior (RH / Admin) podem acessar esta página
if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

// ==================
// CONSULTA PRINCIPAL
// ==================

/* 
Busca todos os ajustes com status 'Pendente'

Tabelas envolvidas:
- ajustes_ponto (a): solicitações de ajuste
- ponto_dia (p): registro original do ponto
- usuario (u): funcionário dono do ponto
- usuario (s): quem solicitou o ajuste

Importante:
Funcionário vem de p.id_usuario
Solicitante vem de a.id_usuario
*/

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
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Ajustes Pendentes (RH)</title>
</head>

<body>
    <h1>Ajustes Pendentes</h1>

    <!-- Volta para o painel geral do RH -->
    <a href="../index.php">Voltar</a>
    <br><br>

    <table border="1" cellpadding="8">

        <!-- Cabeçalho da tabela -->
        <tr>
            <th>Funcionário</th>
            <th>Dia</th>
            <th>Campo</th>
            <th>Antes</th>
            <th>Depois</th>
            <th>Motivo</th>
            <th>Ação</th>
        </tr>

        <!-- Loop que percorre cada ajuste pendente -->
        <?php while ($r = $res->fetch_assoc()): ?>
            <tr>

                <!-- Funcionário dono do ponto -->
                <td><?= htmlspecialchars($r['funcionario']) ?></td>

                <!-- Data do registro de ponto -->
                <td><?= htmlspecialchars($r['data_ponto']) ?></td>

                <!-- Campo que será alterado -->
                <td><?= htmlspecialchars($r['campo']) ?></td>

                <!-- Horário antigo -->
                <td><?= htmlspecialchars($r['valor_antigo']) ?></td>

                <!-- Novo horário solicitado -->
                <td><?= htmlspecialchars($r['valor_novo']) ?></td>

                <!-- Motivo do ajuste -->
                <td><?= htmlspecialchars($r['motivo']) ?></td>

                <!-- Ações do RH -->
                <td>
                    <!-- Aprova e aplica o ajuste -->
                    <a href="aprovar.php?id=<?= htmlspecialchars($r['id_ajuste']) ?>">Aprovar</a> |

                    <!-- Recusa o ajuste -->
                    <a href="recusar.php?id=<?= htmlspecialchars($r['id_ajuste']) ?>">Recusar</a>
                </td>

            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>