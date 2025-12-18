<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

$id = $_GET['id'] ?? 0;

// Buscar dados do pedido e cliente com os nomes de campos corretos
$sql = $pdo->prepare("
    SELECT p.*, c.clientenomecompleto, c.clientewhatsapp, 
           c.clientelogradouro, c.clientenumero, c.clientebairro, 
           c.clientecidade, c.clientecpl, c.clientecep, c.clienteobs
    FROM pedido p
    JOIN clientes c ON p.clienteid = c.clientecodigo
    WHERE p.pedidocodigo = ?
");
$sql->execute([$id]);
$pedido = $sql->fetch();

if (!$pedido) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Pedido não encontrado.</div></div>";
    exit;
}

// Buscar itens do pedido
$sqlItems = $pdo->prepare("
    SELECT i.*, pr.produtonome 
    FROM pedidoitem i
    LEFT JOIN produtos pr ON i.produtocodigo = pr.produtocodigo
    WHERE i.pedidocodigo = ?
");
$sqlItems->execute([$id]);
$itens = $sqlItems->fetchAll();
?>

<style>
    body { background-color: #f8f9fc; }
    .view-card { border: none; border-radius: 15px; background: #fff; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); }
    .os-header { border-bottom: 2px solid #e3e6f0; padding-bottom: 20px; margin-bottom: 25px; }
    .label-os { font-size: 0.75rem; text-transform: uppercase; color: #858796; font-weight: 700; margin-bottom: 2px; }
    .value-os { font-size: 1rem; color: #4e73df; font-weight: 600; }
    
    /* Cores de Status */
    .status-pill { padding: 6px 16px; border-radius: 50px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; }
    .status-Criado { background-color: #858796; color: #fff; }
    .status-Produção { background-color: #f6c23e; color: #fff; }
    .status-Instalação { background-color: #36b9cc; color: #fff; }
    .status-Finalizado { background-color: #1cc88a; color: #fff; }
    .status-Cancelado { background-color: #e74a3b; color: #fff; }

    @media print {
        .no-print { display: none !important; }
        .view-card { box-shadow: none; border: 1px solid #ddd; }
        body { background: white; padding: 0; }
        .container { max-width: 100%; width: 100%; }
    }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h4 class="fw-bold text-dark mb-0">Visualizar Pedido #<?= $pedido['pedidocodigo'] ?></h4>
            <a href="listar.php" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> Voltar para a lista</a>
        </div>
        <div class="d-flex gap-2 shadow-sm">
            <button onclick="window.print()" class="btn btn-light border"><i class="bi bi-printer me-2"></i>Imprimir O.S.</button>
            <a href="editar.php?id=<?= $id ?>" class="btn btn-primary"><i class="bi bi-pencil me-2"></i>Editar</a>
        </div>
    </div>

    <div class="card view-card p-4 p-md-5">
        <div class="os-header d-flex justify-content-between align-items-start">
            <div>
                <h2 class="fw-bold mb-1 text-primary">VISA VIDROS</h2>
                <p class="text-muted small mb-0">Vidraçaria e Esquadrias de Alumínio</p>
            </div>
            <div class="text-end">
                <span class="status-pill status-<?= $pedido['pedidosituacao'] ?>"><?= $pedido['pedidosituacao'] ?></span>
                <div class="mt-2 small text-muted">Data do Pedido: <?= date('d/m/Y', strtotime($pedido['pedidodatacriacao'])) ?></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="label-os">Cliente</div>
                <div class="value-os"><?= htmlspecialchars($pedido['clientenomecompleto']) ?></div>
                <div class="small text-muted mt-1">
                    <i class="bi bi-whatsapp me-1 text-success"></i> <?= $pedido['clientewhatsapp'] ?><br>
                    <?php if($pedido['clienteobs']): ?>
                        <i class="bi bi-info-circle me-1"></i> <span class="fst-italic"><?= htmlspecialchars($pedido['clienteobs']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="label-os">Local de Instalação</div>
                <div class="value-os" style="font-size: 0.95rem; color: #3a3b45;">
                    <?= htmlspecialchars($pedido['clientelogradouro']) ?>, <?= htmlspecialchars($pedido['clientenumero']) ?><br>
                    <?= htmlspecialchars($pedido['clientecpl'] ? $pedido['clientecpl'] . ' - ' : '') ?> 
                    <?= htmlspecialchars($pedido['clientebairro']) ?><br>
                    <?= htmlspecialchars($pedido['clientecidade']) ?> - <?= htmlspecialchars($pedido['clientecep']) ?>
                </div>
            </div>
        </div>

        <div class="row mb-5 border-top pt-3">
            <div class="col-md-4">
                <div class="label-os">Previsão de Entrega</div>
                <div class="fw-bold text-dark">
                    <i class="bi bi-calendar-check me-1"></i>
                    <?= $pedido['pedidoprevisaoentrega'] ? date('d/m/Y', strtotime($pedido['pedidoprevisaoentrega'])) : 'Não definida' ?>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="bg-light">
                    <tr class="text-muted small">
                        <th class="py-3 ps-3">DESCRIÇÃO DO PRODUTO/SERVIÇO</th>
                        <th class="text-center py-3" width="100">QUANT.</th>
                        <th class="text-end py-3" width="150">VLR. UNIT.</th>
                        <th class="text-end py-3 pe-3" width="150">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['produtonome'] ?? 'Produto') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($item['pedidoitemobservacao'] ?? '') ?></div>
                            </td>
                            <td class="text-center"><?= $item['pedidoqtd'] ?></td>
                            <td class="text-end">R$ <?= number_format($item['pedidoitemvalorunitario'], 2, ',', '.') ?></td>
                            <td class="text-end fw-bold pe-3">R$ <?= number_format($item['pedidoitemvalortotal'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="3" class="text-end fw-bold py-3">VALOR TOTAL DO PEDIDO:</td>
                        <td class="text-end fw-bold py-3 pe-3 text-primary fs-5">R$ <?= number_format($pedido['pedidototal'], 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (!empty($pedido['pedidoobservacao'])): ?>
            <div class="mb-5 p-3 rounded border-start border-4 border-primary bg-light">
                <div class="label-os">Observações Técnicas do Pedido</div>
                <div class="small text-dark mt-1"><?= nl2br(htmlspecialchars($pedido['pedidoobservacao'])) ?></div>
            </div>
        <?php endif; ?>

        <div class="mt-5 d-none d-print-block">
            <div class="row text-center">
                <div class="col-6">
                    <div class="mt-4 pt-1 border-top border-dark mx-4 small">Assinatura Responsável</div>
                </div>
                <div class="col-6">
                    <div class="mt-4 pt-1 border-top border-dark mx-4 small">Assinatura do Cliente</div>
                </div>
            </div>
            <div class="text-center mt-5">
                <p class="text-muted" style="font-size: 0.65rem;">Documento gerado pelo sistema interno Visa Vidros em <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>