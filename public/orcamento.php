<?php
require_once "../config/database.php";

$codigo = $_GET['c'] ?? null;
if(!$codigo){
    die("Código do orçamento não informado.");
}

// Buscar orçamento e cliente
$sql = $pdo->prepare("SELECT o.*, c.* FROM orcamento o JOIN clientes c ON o.clienteid = c.clientecodigo WHERE orcamentolinkaprovacao=?");
$sql->execute([$codigo]);
$orcamento = $sql->fetch();
if(!$orcamento){
    die("Orçamento não encontrado.");
}

// Buscar itens
$itens = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=?");
$itens->execute([$orcamento['orcamentocodigo']]);
$itens = $itens->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Orçamento #<?= $orcamento['orcamentocodigo'] ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
    body { font-size: 0.95rem; }
    .header, .section-title { background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
    .table thead th { background-color: #e9ecef; }
    .badge-situacao { font-size: 1rem; padding: 0.5em 0.75em; }
    .info-box { margin-bottom: 15px; }
    .info-box p { margin-bottom: 0.25rem; }
</style>
</head>
<body>
<div class="container mt-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Orçamento #<?= $orcamento['orcamentocodigo'] ?></h3>
        <div class="text-end">
            <span class="badge bg-info badge-situacao"><?= $orcamento['orcamentosituacao'] ?></span><br>
            <small>Emissão: <?= date('d/m/Y', strtotime($orcamento['orcamentoemissao'] ?? date('Y-m-d'))) ?></small>
        </div>
    </div>

    <!-- DADOS EMPRESA -->
    <div class="header mb-3">
        <h5 class="section-title">Dados da Empresa</h5>
        <div class="row">
            <div class="col-md-6 info-box">
                <p><strong>CNPJ:</strong> 19.302.578/0001-20</p>
                <p><strong>Endereço:</strong> Rua Minotauro, 197</p>
                <p><strong>CEP:</strong> 09172-130</p>
                <p><strong>Bairro:</strong> Jd. Estádio</p>
            </div>
            <div class="col-md-6 info-box">
                <p><strong>Telefone:</strong> (11) 99450-7922</p>
                <p><strong>Email:</strong> contato@visavidroabc.com.br</p>
            </div>
        </div>
    </div>

    <!-- DADOS CLIENTE -->
    <div class="header mb-3">
        <h5 class="section-title">Dados do Cliente</h5>
        <div class="row">
            <div class="col-md-6 info-box">
                <p><strong>Nome:</strong> <?= htmlspecialchars($orcamento['clientenomecompleto']) ?></p>
                <p><strong>Telefone:</strong> <?= htmlspecialchars($orcamento['clientewhatsapp']) ?></p>
                <p><strong>CEP:</strong> <?= $orcamento['clientecep'] ?></p>
            </div>
            <div class="col-md-6 info-box">
                <p><strong>Endereço:</strong> <?= htmlspecialchars($orcamento['clientelogradouro']) ?>, <?= $orcamento['clientenumero'] ?>, <?= htmlspecialchars($orcamento['clientecpl']) ?></p>
                <p><strong>Bairro:</strong> <?= htmlspecialchars($orcamento['clientebairro']) ?></p>
                <p><strong>Cidade / UF:</strong> <?= htmlspecialchars($orcamento['clientecidade']) ?></p>
            </div>
            <div class="col-12 info-box">
                <p><strong>Observações:</strong> <?= htmlspecialchars($orcamento['clienteobs']) ?></p>
            </div>
        </div>
    </div>

    <!-- ITENS -->
    <div class="header mb-3">
        <h5 class="section-title">Itens do Orçamento</h5>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Quantidade</th>
                    <th>Valor Unitário</th>
                    <th>Desconto</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($itens as $i): ?>
                <tr>
                    <td><?= $i['produtocodigo'] ?></td>
                    <td><?= htmlspecialchars($i['produtodescricao']) ?></td>
                    <td><?= $i['orcamentoqnt'] ?></td>
                    <td>R$ <?= number_format($i['orcamentovalor'],2,",",".") ?></td>
                    <td>R$ <?= number_format($i['orcamentodesconto'],2,",",".") ?></td>
                    <td>R$ <?= number_format($i['orcamentovalortotal'],2,",",".") ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="text-end">
            <h5>Total Geral: R$ <?= number_format(array_sum(array_column($itens,'orcamentovalortotal')),2,",",".") ?></h5>
        </div>
    </div>

    <!-- OBSERVAÇÃO -->
    <?php if(!empty($orcamento['clienteobs'])): ?>
    <div class="header mb-3">
        <h5 class="section-title">Observações</h5>
        <p><?= htmlspecialchars($orcamento['clienteobs']) ?></p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
