<?php
// Conecta ao banco de dados
require_once "../BD/conexao.php";

// Recebe parâmetros opcionais via GET
$mes = $_GET["mes"] ?? null;           // Mês no formato YYYY-MM
$id_usuario = $_GET["id_usuario"] ?? null; // ID do usuário opcional

// Base da consulta: busca folhas + nome do usuário
$sql = "
    SELECT f.id_folha, f.mes_competencia, f.salario_liquido,
           u.nome_usuario
    FROM folhas f
    LEFT JOIN usuario u ON u.id_usuario = f.id_usuario
    WHERE 1
";
// "WHERE 1" é usado para facilitar adicionar filtros dinamicamente depois

// ------------------------
// Filtro pelo mês (opcional)
// ------------------------
if ($mes) {
    // Adiciona "-01" para transformar YYYY-MM em YYYY-MM-01
    // Usa real_escape_string para evitar SQL Injection
    $sql .= " AND f.mes_competencia = '" . $conn->real_escape_string($mes . "-01") . "'";
}

// ----------------------------
// Filtro pelo usuário (opcional)
// ----------------------------
if ($id_usuario) {
    // Converte para inteiro para evitar injeção
    $sql .= " AND f.id_usuario = " . intval($id_usuario);
}

// Ordenação: mais recente primeiro
$sql .= " ORDER BY f.mes_competencia DESC";

// Executa a consulta
$result = $conn->query($sql);

// Array que receberá todas as folhas
$folhas = [];

// Percorre resultados e coloca no array
while ($row = $result->fetch_assoc()) {
    $folhas[] = $row;
}

// Retorna o JSON com as folhas filtradas
echo json_encode($folhas);
