<?php
require_once "../includes/header.php";

require_once "../config/database.php";

$id = $_GET['id'] ?? null;
if(!$id){
    echo "<div class='alert alert-danger'>Orçamento não encontrado.</div>";
    exit;
}

// Buscar orçamento
$sql = $pdo->prepare("SELECT o.*, c.clientenomecompleto, c.clientewhatsapp, c.clientecep, c.clientelogradouro, c.clientenumero, c.clientecpl, c.clientebairro, c.clientecidade, c.clienteobs 
                      FROM orcamento o 
                      JOIN clientes c ON o.clienteid = c.clientecodigo 
                      WHERE orcamentocodigo = ?");
$sql->execute([$id]);
$orcamento = $sql->fetch();

if(!$orcamento){
    echo "<div class='alert alert-danger'>Orçamento não encontrado.</div>";
    exit;
}

// Buscar itens
$itens = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=?");
$itens->execute([$id]);
$itens = $itens->fetchAll();

// Atualizar situação
if($_POST){
    $situacao = $_POST['orcamentosituacao'] ?? $orcamento['orcamentosituacao'];
    $sqlUpdate = $pdo->prepare("UPDATE orcamento SET orcamentosituacao=? WHERE orcamentocodigo=?");
    $sqlUpdate->execute([$situacao, $id]);
    $orcamento['orcamentosituacao'] = $situacao;
    $sucesso = "Situação atualizada!";
}

?>
<div class="container mt-4">
    <h4>Visualizar Orçamento #<?= $orcamento['orcamentocodigo'] ?></h4>

    <?php if(!empty($sucesso)): ?>
        <div class="alert alert-success"><?= $sucesso ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header">Cliente</div>
        <div class="card-body">
            <p><strong>Nome:</strong> <?= htmlspecialchars($orcamento['clientenomecompleto']) ?></p>
            <p><strong>WhatsApp:</strong> <?= htmlspecialchars($orcamento['clientewhatsapp']) ?></p>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($orcamento['clientelogradouro']) ?>, <?= $orcamento['clientenumero'] ?>, <?= htmlspecialchars($orcamento['clientecpl']) ?>, <?= htmlspecialchars($orcamento['clientebairro']) ?>, <?= htmlspecialchars($orcamento['clientecidade']) ?>, CEP: <?= $orcamento['clientecep'] ?></p>
            <p><strong>Observações:</strong> <?= htmlspecialchars($orcamento['clienteobs']) ?></p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Itens do Orçamento</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Valor</th>
                        <th>Desconto</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalGeral = 0;
                    foreach($itens as $i):
                        $totalItem = $i['orcamentoqnt'] * $i['orcamentovalor'] - $i['orcamentodesconto'];
                        $totalGeral += $totalItem;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($i['produtodescricao']) ?></td>
                        <td><?= $i['orcamentoqnt'] ?></td>
                        <td><?= number_format($i['orcamentovalor'],2,",",".") ?></td>
                        <td><?= number_format($i['orcamentodesconto'],2,",",".") ?></td>
                        <td><?= number_format($totalItem,2,",",".") ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h5>Total Geral: R$ <?= number_format($totalGeral,2,",",".") ?></h5>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Detalhes do Orçamento</div>
        <div class="card-body">
            <p><strong>Previsão de Entrega:</strong> <?= $orcamento['orcamentoprevisaoentrega'] ?></p>
            <p><strong>Forma de Pagamento:</strong> <?= ucfirst($orcamento['orcamentoformapagamento']) ?></p>
        </div>
    </div>
   
</div>
