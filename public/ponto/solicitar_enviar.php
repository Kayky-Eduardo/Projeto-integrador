<?php
session_start();
include(__DIR__ . "/../../BD/conexao.php");
date_default_timezone_set('America/Sao_Paulo');

/* ==========
VALIDAR LOGIN
========== */
// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    die("Acesso negado.");
}

// ID do usuário logado
$id_usuario = $_SESSION['id_usuario'];
$nivel = $_SESSION['nivel'];

/* =====================
DEFINIR PARA ONDE VOLTAR
===================== */
// Define a página de retorno com base no nível do usuário
$pagina_voltar = ($nivel >= 2) ? "../ponto/gerenciar.php" : "../ponto/historico.php";

/* ======================
DADOS RECEBIDOS DO FORMULÁRIO (NOVA ESTRUTURA)
====================== */
$id_ponto       = intval($_POST['id_ponto'] ?? 0);
$motivo         = trim($_POST['justificativa'] ?? '');
$tipo_ajuste    = $_POST['tipo_ajuste'] ?? ''; // 'ponto' ou 'pausa'

// Variáveis específicas para AJUSTE DE PONTO
$campo_ponto    = $_POST['campo_ponto'] ?? '';      // inicio_ponto ou fim_ponto
$valor_novo_ponto = $_POST['valor_novo_ponto'] ?? '';

// Variáveis específicas para AJUSTE DE PAUSA
$id_pausa       = intval($_POST['id_pausa'] ?? 0);
$inicio_pausa_novo = $_POST['inicio_pausa_novo'] ?? '';
$fim_pausa_novo = $_POST['fim_pausa_novo'] ?? '';


/* =================
VALIDAÇÃO INICIAL
================= */
if ($id_ponto <= 0 || !$motivo || !in_array($tipo_ajuste, ['ponto', 'pausa'])) {
    die("Dados obrigatórios incompletos.");
}

// ===============================================
// BUSCAR DADOS BÁSICOS DO PONTO PARA SEGURANÇA
// ===============================================
// Garante que o ponto existe e pega a data para cálculos
$busca_ponto = $conn->prepare("SELECT data_ponto, id_usuario FROM ponto_dia WHERE id_ponto = ?");
$busca_ponto->bind_param("i", $id_ponto);
$busca_ponto->execute();
$reg_ponto = $busca_ponto->get_result()->fetch_assoc();

if (!$reg_ponto) {
    die("Ponto não encontrado ou acesso negado.");
}

$data = $reg_ponto['data_ponto']; // Data do ponto (YYYY-MM-DD)

// Inicializa a variável de sucesso do ajuste
$ajuste_executado = false;

/* ========================
ROUTINE 1: AJUSTE DE PONTO
======================== */
if ($tipo_ajuste === 'ponto') {

    $camposPermitidos = ['inicio_ponto', 'fim_ponto'];

    if (!in_array($campo_ponto, $camposPermitidos) || !$valor_novo_ponto) {
        die("Dados de ajuste de ponto inválidos.");
    }

    // 1. BUSCAR VALOR ANTIGO
    $busca_antigo = $conn->prepare("SELECT `$campo_ponto` FROM ponto_dia WHERE id_ponto = ?");
    $busca_antigo->bind_param("i", $id_ponto);
    $busca_antigo->execute();
    $valor_antigo_raw = $busca_antigo->get_result()->fetch_assoc()[$campo_ponto];

    // Formata o valor antigo (DateTime ou NULL)
    $valor_antigo = $valor_antigo_raw 
        ? date('Y-m-d H:i:s', strtotime($valor_antigo_raw)) 
        : null;

    // Monta o novo valor (DateTime)
    $valor_novo = $data . ' ' . $valor_novo_ponto . ':00';

    // 2. INSERIR SOLICITAÇÃO (Tabela: ajustes_ponto)
    $stmt = $conn->prepare("
        INSERT INTO ajustes_ponto 
            (id_ponto, id_usuario, campo, valor_antigo, valor_novo, motivo, status, data_solicitacao)
        VALUES 
            (?, ?, ?, ?, ?, ?, 'Pendente', NOW())
    ");
    $stmt->bind_param(
        "iissss",
        $id_ponto,
        $id_usuario,
        $campo_ponto, 
        $valor_antigo,
        $valor_novo,
        $motivo
    );
    
    $ajuste_executado = $stmt->execute();

}

// ajuste de pausa caso seja selecionado ↓

elseif ($tipo_ajuste === 'pausa') {
    
    // 1. Validação de dados de pausa
    if ($id_pausa <= 0 || (empty($inicio_pausa_novo) && empty($fim_pausa_novo))) {
        die("Selecione a pausa e preencha o novo início ou fim.");
    }

    // 2. BUSCA VALOR ANTIGO DA PAUSA (Segurança: Garante que a pausa pertence ao ponto)
    $busca_pausa = $conn->prepare("
        SELECT id_pausa, inicio, fim, id_usuario, data
        FROM pausa 
        WHERE id_pausa = ? AND id_usuario = ? AND data = ?
    ");
    // O id_usuario e a data são recuperados do ponto já validado
    $busca_pausa->bind_param("iss", $id_pausa, $reg_ponto['id_usuario'], $data);
    $busca_pausa->execute();
    $reg_pausa = $busca_pausa->get_result()->fetch_assoc();

    if (!$reg_pausa) {
        die("Pausa não encontrada ou não pertence ao ponto selecionado.");
    }

    // 3. Processar e Inserir Ajustes para INÍCIO e/ou FIM
    
    // Sub-rotina para INÍCIO da Pausa
    if (!empty($inicio_pausa_novo)) {
        $campo_pausa_inicio = 'inicio_pausa'; 
        $valor_antigo_inicio = $reg_pausa['inicio'] 
            ? date('Y-m-d H:i:s', strtotime($reg_pausa['inicio'])) 
            : null;
        $valor_novo_inicio = $data . ' ' . $inicio_pausa_novo . ':00';

        // NOTE: ESTA QUERY REQUER A COLUNA id_pausa NA TABELA ajustes_ponto
        $stmt_inicio = $conn->prepare("
            INSERT INTO ajustes_ponto 
                (id_ponto, id_pausa, id_usuario, campo, valor_antigo, valor_novo, motivo, status, data_solicitacao)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, 'Pendente', NOW())
        ");
        $stmt_inicio->bind_param(
            "iiissss",
            $id_ponto,
            $id_pausa,
            $id_usuario,
            $campo_pausa_inicio,
            $valor_antigo_inicio,
            $valor_novo_inicio,
            $motivo
        );
        $ajuste_executado = $stmt_inicio->execute();
    }

    // Sub-rotina para FIM da Pausa
    if (!empty($fim_pausa_novo)) {
        $campo_pausa_fim = 'fim_pausa'; 
        $valor_antigo_fim = $reg_pausa['fim'] 
            ? date('Y-m-d H:i:s', strtotime($reg_pausa['fim'])) 
            : null;
        $valor_novo_fim = $data . ' ' . $fim_pausa_novo . ':00';

        $stmt_fim = $conn->prepare("
            INSERT INTO ajustes_ponto 
                (id_ponto, id_pausa, id_usuario, campo, valor_antigo, valor_novo, motivo, status, data_solicitacao)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, 'Pendente', NOW())
        ");
        $stmt_fim->bind_param(
            "iiissss",
            $id_ponto,
            $id_pausa,
            $id_usuario,
            $campo_pausa_fim,
            $valor_antigo_fim,
            $valor_novo_fim,
            $motivo
        );
        // Usa OR lógico para manter a execução se o ajuste de início foi bem-sucedido
        $ajuste_executado = $stmt_fim->execute() || $ajuste_executado;
    }
}

/* =================
EXECUTAR E FINALIZAR
================= */
if ($ajuste_executado) {

    // MARCA O PONTO COMO "REVISAR"
    $up = $conn->prepare("UPDATE ponto_dia SET status = 'Revisar' WHERE id_ponto = ?");
    $up->bind_param("i", $id_ponto);
    $up->execute();

    // Mensagem de sucesso e redirecionamento
    echo "
        <script>
            alert('Solicitação enviada com sucesso! O ponto está em revisão.');
            window.location.href = '$pagina_voltar';
        </script>
    ";
} else {

    // Caso ocorra erro ao salvar o ajuste
    echo "
        <script>
            alert('Erro ao salvar ajuste. Verifique se preencheu todos os campos necessários.');
            window.history.back();
        </script>
    ";
}
