<?php
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