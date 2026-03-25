<?php
require_once "funcoes/funcoes_banco_horas.php";
require_once 'funcoes/funcoes_coleta_dados.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificar_login($conn) {
    $id_login = $_SESSION['id_login'];
    $id_usuario = $_SESSION['id_usuario'];

    if (!isset($id_login) || !isset($id_usuario)) {
        header("Location: /projeto-integrador/public/logout.php");
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT data_fim FROM login
        WHERE id_login = ? AND id_usuario = ? LIMIT 1
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

    $resposta_id_ponto = coleta_id_ponto($conn, $id_usuario);

    if ($resposta_id_ponto['sucesso']) {
        $id_ponto = $resposta_id_ponto['id_ponto'];
        $resultado_tempo = verificar_tempo_por_ponto($conn, $id_ponto, $id_usuario);
        verificar_tipo($conn, $id_usuario, $resultado_tempo);
    }
}

function verificar_tipo($conn, $id_usuario, $resultado_tempo, $tipo_dado = null) {
    $tipo     = $tipo_dado ?? $resultado_tempo['tipo'];
    $tempo    = $resultado_tempo['resultado']; // segundos (positivo=extra, negativo=faltou, 0=exato)
    $minutos = (int) round(abs($tempo) / 60);
    $mensagem = $resultado_tempo['mensagem'] ?? "";

    if ($tipo === "finalizar") {
        if ($tempo > 0) {
            adicionar_horas($conn, $id_usuario, $minutos);
        } elseif ($tempo === 0) {
            adicionar_horas($conn, $id_usuario, 0, "horario_completo");
        } else {
            retirar_horas($conn, $id_usuario, abs($minutos));
        }
        return;
    }
}

function fechar_pontos_pendentes($conn, $id_usuario) {
    $verificar_pausa = $conn->prepare("
        SELECT id_pausa
        FROM pausa
        WHERE id_usuario = ? AND fim IS NULL
        LIMIT 1;
    ");
    $verificar_pausa->bind_param("i", $id_usuario);
    $verificar_pausa->execute();
    $resultado_pausa = $verificar_pausa->get_result();

    $verificar_ponto = $conn->prepare("
        SELECT id_ponto
        FROM ponto_dia
        WHERE id_usuario = ? AND fim_ponto IS NULL
        LIMIT 1;
    ");
    $verificar_ponto->bind_param("i", $id_usuario);
    $verificar_ponto->execute();
    $resultado_ponto = $verificar_ponto->get_result();

    if ($resultado_pausa->num_rows > 0) {
        $id_pausa = $resultado_pausa->fetch_assoc()['id_pausa'];
        $update_pausa = $conn->prepare("
            UPDATE pausa
            SET fim = CURTIME()
            WHERE id_usuario = ? AND id_pausa = ?;
        ");
        $update_pausa->bind_param("ii", $id_usuario, $id_pausa);
        $update_pausa->execute();
    }
    
    if ($resultado_ponto->num_rows > 0) {
        $id_ponto = $resultado_ponto->fetch_assoc()['id_ponto'];
        $update_ponto = $conn->prepare("
            UPDATE ponto_dia
            SET fim_ponto = CURTIME()
            WHERE id_usuario = ? AND id_ponto = ?;
        ");
        $update_ponto->bind_param("ii", $id_usuario, $id_ponto);
        $update_ponto->execute();
    }
    
}

function verificar_tempo_logado($conn, $id_usuario, $id_login) {
    $verificar_historico_ponto = $conn->prepare("
        SELECT id_ponto
        FROM ponto_dia
        WHERE data_ponto = CURDATE() AND fim_ponto IS NOT NULL
        AND id_usuario = ?;
    ");
    $verificar_historico_ponto->bind_param("i", $id_usuario);
    $verificar_historico_ponto->execute();
    $resultado = $verificar_historico_ponto->get_result();

    if ($resultado->num_rows > 0) {
        $id_ponto = $resultado->fetch_assoc()['id_ponto'];

        return [
            'tipo' => 'bloqueado',
            'resultado' => 0
        ];
    }

    // Busca tudo de uma vez: jornada, hora extra máxima e tempo logado
    // Por login
    $stmt = $conn->prepare("
    SELECT 
        TIME_TO_SEC(tempo_jornada.maximo_hora_extra) as hora_extra,
        TIME_TO_SEC(tempo_jornada.jornada) AS segundos_jornada,
        TIME_TO_SEC(ADDTIME(tempo_jornada.jornada, tempo_jornada.maximo_hora_extra)) AS segundos_maximos,
        TIMESTAMPDIFF(SECOND, login.data_inicio, NOW()) AS segundos_logado
    FROM login
    JOIN grupo_setor
        ON grupo_setor.id_usuario = login.id_usuario
    JOIN setor
		ON setor.id_setor = grupo_setor.id_setor
    JOIN tempo_jornada
        ON tempo_jornada.id_tempo = setor.id_tempo
    WHERE login.id_login = ?
        AND login.id_usuario = ?
        AND login.data_fim IS NULL
    LIMIT 1;
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

    if ($dados['segundos_logado'] >= $dados['segundos_maximos']) {
        $minutos = (int) $dados['hora_extra'];
        return [
            "tipo" => "excedido",
            "resultado" => $minutos,
            "mensagem" => "",
        ];
    }

    if ($dados['segundos_logado'] >= $dados['segundos_jornada']) {
        $segundos_extra = $dados['segundos_logado'] - $dados['segundos_jornada'];
        if ($segundos_extra > 0) {
            $minutos = round($segundos_extra / 60);
        
            return [
                "tipo" => 'tempo_extra',
                "resultado" => $minutos,
                "mensagem" => "",
            ];
        }
    }
    
    if ($dados['segundos_logado'] < $dados['segundos_jornada']) {
        $segundos_faltantes = $dados['segundos_logado'] - $dados['segundos_jornada'];
        if ($segundos_faltantes < 0) {
            $minutos = round($segundos_faltantes / 60);

            return [
                "tipo" => "tempo_faltante",
                "resultado" => $minutos,
                "mensagem" => "",
            ];
        }
    }
    return $minutos_extra;
}

?>