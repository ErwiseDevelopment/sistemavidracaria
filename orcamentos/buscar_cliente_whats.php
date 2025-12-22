<?php
require_once "../config/database.php";

$whats = $_GET['whatsapp'] ?? '';
$whats = preg_replace('/\D/', '', $whats); // Remove parênteses e traços

if (empty($whats)) {
    echo json_encode(['sucesso' => false]);
    exit;
}

// Busca o cliente pelo número de WhatsApp
$sql = "SELECT * FROM clientes WHERE REPLACE(REPLACE(REPLACE(clientewhatsapp, '(', ''), ')', ''), '-', '') LIKE '%$whats%' LIMIT 1";
$res = $pdo->query($sql);
$cliente = $res->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
    echo json_encode([
        'sucesso'    => true,
        'id'         => $cliente['clientecodigo'],
        'nome'       => $cliente['clientenomecompleto'],
        'cep'        => $cliente['clientecep'],
        'logradouro' => $cliente['clientelogradouro'],
        'bairro'     => $cliente['clientebairro'],
        'numero'     => $cliente['clientenumero'],
        'cidade'     => $cliente['clientecidade'],
        'cpl'        => $cliente['clientecpl'],
        'obs'        => $cliente['clienteobs']
    ]);
} else {
    echo json_encode(['sucesso' => false]);
}