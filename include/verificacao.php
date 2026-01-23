<?php
/* 
 * Não sei quem fez...
 * Ta funcionando certinho.
 * Não vou mexer! :) 
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificar_login($conn)
{
    $id_login = $_SESSION['id_login'];
    $id_usuario = $_SESSION['id_usuario'];

    if (!isset($id_login) || !isset($id_usuario)) {
        header("Location: /projeto-integrador/public/logout.php");
        exit;
    }

    $stmt = $conn->prepare("
        SELECT data_fim 
        FROM login
        WHERE id_login = ? 
        AND id_usuario = ? 
        LIMIT 1
    ");

    $stmt->bind_param("ii", $id_login, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $ativo = $conn->prepare("
        SELECT conta_ativa
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ");

    $ativo->bind_param("i", $id_usuario);
    $ativo->execute();
    $result_ativo = $ativo->get_result();

    if ($result->num_rows === 0 || $result_ativo->num_rows === 0) {
        header("Location: /projeto-integrador/public/logout.php");
        exit;
    }

    $resposta = $result_ativo->fetch_assoc();
    $row = $result->fetch_assoc();

    if ($row['data_fim'] !== null || $resposta['conta_ativa'] == 0) {
        header("Location: /projeto-integrador/public/logout.php");
        exit;
    }

    verificar_tempo_logado($conn, $id_usuario, $id_login);
}

function verificar_tempo_logado($conn, $id_usuario, $id_login)
{
    $stmt = $conn->prepare("
        SELECT 
            TIME_TO_SEC(tempo_jornada.jornada) as segundos_jornada,
            TIME_TO_SEC(ADDTIME(tempo_jornada.jornada, tempo_jornada.maximo_hora_extra)) as segundos_maximos,
            TIMESTAMPDIFF(SECOND, login.data_inicio, NOW()) as segundos_logado
        FROM tempo_jornada
        CROSS JOIN login
        WHERE login.id_login = ? 
        AND login.id_usuario = ?
        AND login.data_fim IS NULL
        LIMIT 1
    ");

    $stmt->bind_param("ii", $id_login, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return 0;
    }

    $dados = $result->fetch_assoc();
    $stmt->close();

    $minutos_extra = 0;

    if ($dados['segundos_logado'] >= $dados['segundos_jornada']) {
        $segundos_extra = $dados['segundos_logado'] - $dados['segundos_jornada'];
        if ($segundos_extra > 0) {
            $minutos_extra = round($segundos_extra / 60);

            return $minutos_extra;
        }
    }

    if ($dados['segundos_logado'] < $dados['segundos_jornada']) {
        $segundos_faltantes = $dados['segundos_logado'] - $dados['segundos_jornada'];

        if ($segundos_faltantes < 0) {
            $minutos_faltantes = round($segundos_faltantes / 60);

            return $minutos_faltantes;
        }
    }
    return $minutos_extra;
}
