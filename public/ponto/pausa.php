<?php
session_start(); 
include '../../BD/conexao.php'; // Conexão com BD
include '../../include/verificacao.php'; // Verifica se o usuário está logado


// ----------------------------------------------------------
// BUSCA TODAS AS PAUSAS ATIVAS (aquelas que NÃO têm fim)
// ----------------------------------------------------------
$sql = "
    SELECT 
        p.id_pausa, 
        p.id_usuario, 
        p.inicio, 
        p.data, 
        p.fim,
        c.descricao_pausa, 
        c.tempo_max, 
        u.nome_usuario
    FROM pausa p
    LEFT JOIN pausa_config c ON c.id_config = p.id_config
    LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
    WHERE p.fim IS NULL      -- fim NULL = pausa ainda aberta
    ORDER BY p.inicio DESC   -- mais recente primeiro
";

// executa consulta
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Pausas Ativas</title>
</head>
<body>
    <a href="relatorio_ponto.php">Voltar</a>
    <h2>Pausas Ativas</h2>
    <p>Mostrando pausas abertas (ainda sem fim).</p>

    <!-- Se não tem registro nenhum -->
    <?php if (!$res || $res->num_rows === 0): ?>
        <div>Nenhuma pausa ativa no momento.</div>

    <?php else: ?>

        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Tipo</th>
                    <th>Início</th>
                    <th>Data</th>
                    <th>Minutos dec.</th>
                    <th>Tempo Máx</th>
                </tr>
            </thead>

            <tbody>

            <?php while ($row = $res->fetch_assoc()): 

                // ----------------------------------------------------------
                // CALCULA QUANTOS MINUTOS DE PAUSA JÁ SE PASSARAM
                // ----------------------------------------------------------
                $inicio = $row['inicio'];
                $minutos = 0;

                if ($inicio) {
                    // transforma data e hora em timestamp
                    $t1 = strtotime($row['inicio']);
                    $t2 = time(); // agora
                    $minutos = floor(($t2 - $t1) / 60) - 240; // diferença em minutos(NÃO REMOVA O -240 SE NÃO QUISER BUGS)
                }
            ?>

                <tr>
                    <td><?= $row['id_pausa'] ?></td>

                    <!-- nome do usuário -->
                    <td><?= htmlspecialchars($row['nome_usuario']) ?></td>

                    <!-- tipo de pausa -->
                    <td><?= htmlspecialchars($row['descricao_pausa']) ?></td>

                    <td><?= $row['inicio'] ?></td>
                    <td><?= $row['data'] ?></td>

                    <!-- minutos decorridos -->
                    <td><?= $minutos ?></td>

                    <!-- tempo máximo definido na config -->
                    <td><?= $row['tempo_max'] ?></td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>

    <?php endif; ?>
</body>
</html>
