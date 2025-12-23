<?php
require_once "../config/database.php";

$id = $_GET['id'] ?? null;
if (!$id) exit("Pedido não encontrado");

$sql = $pdo->prepare("
    SELECT p.*, c.* FROM pedido p 
    JOIN clientes c ON p.clienteid = c.clientecodigo 
    WHERE p.pedidocodigo = ?");
$sql->execute([$id]);
$pedido = $sql->fetch();

$itens = $pdo->prepare("SELECT * FROM pedidoitem WHERE pedidocodigo = ?");
$itens->execute([$id]);
$lista_itens = $itens->fetchAll();

// REGRA DE BLOQUEIO: Se aprovado, finalizado ou cancelado, esconde o botão editar
$statusAtual = strtolower(trim($pedido['pedidosituacao'] ?? ''));
$bloqueado = in_array($statusAtual, ['aprovado', 'finalizado', 'cancelado']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pedido #<?= $id ?> - VISA VIDROS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { size: A4; margin: 8mm; }
        body { background: #eee; font-family: 'Inter', system-ui, sans-serif; font-size: 11px; color: #333; }
        
        .print-container { 
            background: white; width: 210mm; min-height: 280mm;
            margin: 0 auto; padding: 20px; box-sizing: border-box; position: relative;
        }
        
        .brand-name { color: #1a237e; font-size: 26px; font-weight: 800; letter-spacing: -1px; line-height: 1; }
        .order-badge { background: #f0f2f5; padding: 10px; border-radius: 8px; text-align: center; border: 1px solid #ddd; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        .info-card { border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px; }
        .info-card-title { font-size: 9px; font-weight: 700; color: #777; text-transform: uppercase; border-bottom: 1px solid #eee; margin-bottom: 5px; display: block; }
        
        .table-modern { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .table-modern th { background: #1a237e !important; color: white !important; padding: 8px; font-size: 10px; text-transform: uppercase; text-align: left; }
        .table-modern td { padding: 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        .table-modern tr:nth-child(even) { background: #fafafa; }

        .summary-box { background: #f8f9fa; border-radius: 8px; padding: 12px; border: 1px solid #dee2e6; }
        .signature-area { margin-top: 40px; text-align: center; border-top: 1px solid #333; width: 250px; margin-left: auto; margin-right: auto; padding-top: 5px; }

        /* Selo de Status */
        .badge-status { 
            padding: 4px 10px; border-radius: 5px; font-weight: bold; font-size: 10px; 
            background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1;
        }

        @media print {
            body { background: white; padding: 0; }
            .print-container { border: none; margin: 0; height: auto; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print text-center py-3">
    <button onclick="window.print()" class="btn btn-primary shadow-sm px-4">IMPRIMIR</button>
    
    <?php if(!$bloqueado): ?>
        <a href="editar.php?id=<?= $id ?>" class="btn btn-warning px-4 border">EDITAR</a>
    <?php endif; ?>
    
    <a href="listar.php" class="btn btn-light px-4 border">VOLTAR</a>
</div>

<div class="print-container shadow-sm">
    <div class="row align-items-center mb-3">
        <div class="col-7">
            <div class="brand-name">VISA VIDROS</div> 
            <div class="text-muted fw-bold" style="font-size: 10px;">WWW.VISAVIDROSABC.COM.BR</div>
            <div class="mt-2">
                <span class="badge-status">STATUS: <?= strtoupper($pedido['pedidosituacao'] ?? 'CRIADO') ?></span>
            </div>
        </div>
        <div class="col-5">
            <div class="order-badge">
                <span class="text-muted small d-block">ORÇAMENTO N°</span> 
                <span class="h4 fw-bold m-0"><?= $id ?></span> 
                <div class="small fw-bold border-top mt-1 pt-1">EMISSÃO: <?= date('d/m/Y', strtotime($pedido['pedidodata'] ?? 'now')) ?></div> 
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <span class="info-card-title">Dados da Empresa</span> 
            <strong>CNPJ:</strong> 19.302.578/0001-20<br> 
            <strong>Endereço:</strong> Rua Minotauro, 197 - Jd. Estádio<br> 
            <strong>Contato:</strong> (11) 99450-7922 | contato@visavidroabc.com.br 
        </div>
        <div class="info-card">
            <span class="info-card-title">Dados do Cliente</span> 
            <strong>Nome:</strong> <?= htmlspecialchars($pedido['clientenomecompleto'] ?? '') ?><br> 
            <strong>WhatsApp:</strong> <?= htmlspecialchars($pedido['clientewhatsapp'] ?? 'Não informado') ?><br> 
            <strong>Endereço:</strong> <?= htmlspecialchars($pedido['clientelogradouro'] ?? '') ?>, <?= htmlspecialchars($pedido['clientenumero'] ?? '') ?> - <?= htmlspecialchars($pedido['clientebairro'] ?? '') ?><br>
            <strong>Cidade:</strong> <?= htmlspecialchars($pedido['clientecidade'] ?? '') ?> / <?= htmlspecialchars($pedido['clienteestado'] ?? '') ?>
        </div>
    </div>

    <table class="table-modern">
        <thead>
            <tr>
                <th width="15%">Código</th> 
                <th width="50%">Descrição dos Itens</th> 
                <th width="10%" class="text-center">Qtd</th> 
                <th width="25%" class="text-end">Valor Total</th> 
            </tr>
        </thead>
        <tbody>
            <?php foreach($lista_itens as $item): ?>
            <tr>
                <td class="fw-bold text-muted"><?= htmlspecialchars($item['produtocodigo'] ?? '') ?></td> 
                <td><?= htmlspecialchars($item['produtodescricao'] ?? '') ?></td> 
                <td class="text-center"><?= (int)$item['pedidoqnt'] ?></td> 
                <td class="text-end fw-bold">R$ <?= number_format($item['pedidovalortotal'], 2, ',', '.') ?></td> 
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row mt-3">
        <div class="col-7">
            <div class="info-card h-100">
                <span class="info-card-title">Informações Adicionais</span> 
                <strong>Previsão de Entrega:</strong> <?= !empty($pedido['pedidoprevisaoentrega']) ? date('d/m/Y', strtotime($pedido['pedidoprevisaoentrega'])) : 'A definir' ?><br>
                <strong>Pagamento:</strong> <?= htmlspecialchars($pedido['pedidoformapagamento'] ?? 'A combinar') ?><br> 
                <strong>Observações:</strong> <?= nl2br(htmlspecialchars($pedido['pedido_obs'] ?? 'Nada consta.')) ?> 
            </div>
        </div>
        <div class="col-5">
            <div class="summary-box">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Subtotal:</span>
                    <span>R$ <?= number_format($pedido['pedidototal'], 2, ',', '.') ?></span> 
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Desconto:</span>
                    <span>R$ 0,00</span> 
                </div>
                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <span class="fw-bold h6">TOTAL GERAL:</span>
                    <span class="fw-bold h5 text-primary">R$ <?= number_format($pedido['pedidototal'], 2, ',', '.') ?></span> 
                </div>
            </div>
        </div>
    </div>

    <div class="signature-area">
        Assinatura do Cliente
    </div>

    <div class="text-center text-muted mt-3" style="font-size: 9px;">
        Este documento é uma proposta comercial válida por 10 dias.<br>
        A data de entrega pode sofrer alterações conforme disponibilidade de material em estoque.
    </div>
</div>

</body>
</html>