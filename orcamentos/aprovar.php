<?php
require_once "../config/database.php";

$id = $_GET['id'] ?? 0;

// Buscar orçamento
$sql = $pdo->prepare("SELECT * FROM orcamento WHERE orcamendocodigo=?");
$sql->execute([$id]);
$orcamento = $sql->fetch();

if (!$orcamento) {
    die("Orçamento não encontrado.");
}

// Verificar se já está aprovado
if ($orcamento['orcamentosituacao'] == 'Aprovado') {
    die("Orçamento já aprovado.");
}

// Iniciar transação
$pdo->beginTransaction();

try {
    // Atualizar status do orçamento
    $sql = $pdo->prepare("UPDATE orcamento SET orcamentosituacao='Aprovado' WHERE orcamendocodigo=?");
    $sql->execute([$id]);

    // Calcular valor total do pedido
    $sql = $pdo->prepare("SELECT SUM(orcamentovalortotal) AS total, SUM(orcamentodesconto) AS desconto FROM orcamentoitem WHERE orcamentocodigo=?");
    $sql->execute([$id]);
    $totais = $sql->fetch();
    $valor_total = $totais['total'];
    $desconto_total = $totais['desconto'];

    // Criar pedido
    $sql = $pdo->prepare("
        INSERT INTO pedido (pedidodatacriacao, pedidosituacao, pedidoprevisaoentrega, pedidoformapagamento, pedidodesconto, pedidovlrtotal)
        VALUES (NOW(), 'Aguardando fabrica', ?, ?, ?, ?)
    ");
    $sql->execute([
        $orcamento['orcamentoprevisaoentrega'],
        $orcamento['orcamentoformapagamento'],
        $desconto_total,
        $valor_total
    ]);

    $pedido_id = $pdo->lastInsertId();

    // Inserir itens no pedido
    $sqlItens = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=?");
    $sqlItens->execute([$id]);
    $itens = $sqlItens->fetchAll();

    $sqlInsertItem = $pdo->prepare("
        INSERT INTO pedidoitem (pedidocodigo, produtocodigo, produtodescricao, pedidoqnt, pedidovalor, pedidodesconto)
        VALUES (?,?,?,?,?,?)
    ");

    foreach($itens as $i) {
        $sqlInsertItem->execute([
            $pedido_id,
            $i['produtocodigo'],
            $i['produtodescricao'],
            $i['orcamentoqnt'],
            $i['orcamentovalor'],
            $i['orcamentodesconto']
        ]);
    }

    $pdo->commit();
    header("Location: visualizar.php?id=$id");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao aprovar orçamento: ".$e->getMessage());
}
