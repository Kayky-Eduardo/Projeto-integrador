<?php
// Fragmento que lista pausas ativas (só div) para inclusão.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../../../BD/conexao.php';
require_once __DIR__ . '/../../../include/verificacao.php';

$id_usuario = $_SESSION['id_usuario'] ?? null;

// Mostra apenas pausas ativas do usuário logado
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
    WHERE p.fim IS NULL AND p.id_usuario = ?
    ORDER BY p.inicio DESC
";
//mudei para prepared statement por "segurança"
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
?>

<!-- mudei o html para uma div para exibir pausas ativas -->
<div>
    <h2>Pausa Ativa</h2>
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
                    $t1 = strtotime($row['inicio']);
                    $t2 = time();
                    $minutos = ($t2 - $t1);
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
                    <td class="minutos" data-inicio="<?= $row['data'] .'T'. $row['inicio'] ?>"></td>

                    <!-- tempo máximo definido na config -->
                    <td><?= $row['tempo_max'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <script>
        function atualizarCronometros() {
            document.querySelectorAll("[data-inicio]").forEach(el => {
                const inicio = new Date(el.dataset.inicio).getTime();
                const atual = Date.now();
                const minutos = Math.floor((atual - inicio) / 60000);
                const segundos = Math.floor(((atual - inicio) % 60000) / 1000);
                el.textContent = minutos.toString().padStart(2, '0') + ":" + segundos.toString().padStart(2, '0');
            });
        }

        setInterval(atualizarCronometros, 1000);
        atualizarCronometros();
    </script>
</div>