<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include("../../BD/conexao.php");
require("../../include/verificacao.php");
verificar_login($conn);

if ($_SESSION['nivel'] < 2) {
    die("Acesso restrito.");
}

$sql = "
    SELECT 
        a.*, 
        u.nome_usuario AS funcionario,
        s.nome_usuario AS solicitante,
        p.data_ponto
    FROM ajustes_ponto a
    INNER JOIN ponto_dia p ON p.id_ponto = a.id_ponto
    INNER JOIN usuario u ON u.id_usuario = p.id_usuario
    INNER JOIN usuario s ON s.id_usuario = a.id_usuario
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
                            <th>Solicitado por</th>
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
                                <td colspan="8" class="tabela-vazia">
                                    Nenhum ajuste solicitado e/ou em revisão.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($r = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['funcionario']) ?></td>
                                    <td><?= htmlspecialchars($r['solicitante']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($r['data_ponto'])) ?></td>
                                    <td><?= nomeCampoAjuste($r['campo']) ?></td>
                                    <td><?= date('d-m-Y H:i', strtotime($r['valor_antigo'])) ?></td>
                                    <td><?= date('d-m-Y H:i', strtotime($r['valor_novo'])) ?></td>
                                    <td><?= htmlspecialchars($r['motivo']) ?></td>
                                    <td>
                                        <a class="btn-link btn-padrao" href="editar.php?id=<?= htmlspecialchars($r['id_ajuste']) ?>">Editar</a>
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