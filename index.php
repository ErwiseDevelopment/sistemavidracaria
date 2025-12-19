<?php
require_once "includes/header.php";
require_once "includes/menu.php";
require_once "config/database.php";

// 1. Busca Pedidos com Status 'Instalação' e Data de Previsão >= Hoje
$hoje = date('Y-m-d');
$sqlInstalacao = $pdo->prepare("
    SELECT p.*, c.clientenomecompleto, c.clientewhatsapp 
    FROM pedido p 
    JOIN clientes c ON p.clienteid = c.clientecodigo 
    WHERE p.pedidosituacao = 'Instalação' 
    AND p.pedidoprevisaoentrega >= ?
    ORDER BY p.pedidoprevisaoentrega ASC
");
$sqlInstalacao->execute([$hoje]);
$proximasInstalacoes = $sqlInstalacao->fetchAll();

// 2. Estatísticas para os Cards
// Orçamentos criados (para acompanhamento de vendas)
$totalOrcamentos = $pdo->query("SELECT COUNT(*) FROM orcamento WHERE orcamentosituacao = 'Criado'")->fetchColumn();

// Pedidos que estão atualmente em produção
$pedidosProducao = $pdo->query("SELECT COUNT(*) FROM pedido WHERE pedidosituacao = 'Produção'")->fetchColumn();

?>

<style>
    body { background-color: #f8f9fc; font-family: 'Nunito', sans-serif; }
    .card-stat { border: none; border-left: 4px solid; border-radius: 10px; transition: all 0.3s; }
    .card-stat:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
    
    .bg-gradient-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
    .icon-box { width: 45px; height: 45px; background: rgba(0,0,0,0.05); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    
    .table-agenda thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #4e73df; border-top: none; }
    .btn-quick { border-radius: 10px; padding: 15px; text-align: center; transition: all 0.2s; border: 1px solid #e3e6f0; background: #fff; color: #4e73df; text-decoration: none; }
    .btn-quick:hover { background: #4e73df; color: #fff; }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Olá, <?= explode(' ', $_SESSION['usuario_nome'])[0]; ?>!</h3>
            <p class="text-muted mb-0">Aqui está o resumo da Visa Vidros para hoje.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-primary shadow-sm p-3 border">
                <i class="bi bi-calendar3 me-2"></i><?= date('d/m/Y'); ?>
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-stat shadow-sm h-100 py-2 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Orçamentos Pendentes</div>
                            <div class="h4 mb-0 fw-bold text-dark"><?= $totalOrcamentos ?></div>
                        </div>
                        <div class="icon-box"><i class="bi bi-file-earmark-text text-primary fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm h-100 py-2 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Em Produção</div>
                            <div class="h4 mb-0 fw-bold text-dark"><?= $pedidosProducao ?></div>
                        </div>
                        <div class="icon-box"><i class="bi bi-tools text-warning fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm h-100 py-2 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Faturamento (Mês)</div>
                            <div class="h4 mb-0 fw-bold text-dark">R$ <?= number_format($faturamentoMes ?? 0, 2, ',', '.') ?></div>
                        </div>
                        <div class="icon-box"><i class="bi bi-cash-stack text-success fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-truck me-2"></i>Agenda de Instalações</h6>
                    <span class="badge bg-primary-subtle text-primary"><?= count($proximasInstalacoes) ?> agendadas</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-agenda table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Data</th>
                                    <th>Cliente</th>
                                    <th>Pedido</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!$proximasInstalacoes): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-check fs-2 d-block mb-2"></i>
                                            Sem instalações previstas a partir de hoje.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($proximasInstalacoes as $inst): 
    // Limpa o número para o link do WhatsApp (mantém apenas números)
    $waNumber = preg_replace('/\D/', '', $inst['clientewhatsapp']);
?>
    <tr>
        <td class="ps-3">
            <div class="fw-bold"><?= date('d/m/Y', strtotime($inst['pedidoprevisaoentrega'])) ?></div>
            <small class="text-muted"><?= date('H:i', strtotime($inst['pedidoprevisaoentrega'])) == '00:00' ? 'Hora não definida' : date('H:i', strtotime($inst['pedidoprevisaoentrega'])) ?></small>
        </td>
        <td>
            <div class="fw-bold text-dark"><?= htmlspecialchars($inst['clientenomecompleto']) ?></div>
            <div class="d-flex align-items-center">
                <small class="text-muted me-2"><i class="bi bi-whatsapp me-1 text-success"></i><?= $inst['clientewhatsapp'] ?></small>
                <?php if($waNumber): ?>
                    <a href="https://wa.me/+55<?= $waNumber ?>" target="_blank" class="badge bg-success-subtle text-success text-decoration-none border-success border">
                        <i class="bi bi-chat-dots"></i> Chamar
                    </a>
                <?php endif; ?>
            </div>
        </td>
        <td><span class="badge bg-light text-dark border">#<?= $inst['pedidocodigo'] ?></span></td>
        <td class="text-center">
            <div class="btn-group">
                <a href="pedidos/imprimir.php?id=<?= $inst['pedidocodigo'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Ordem">
                    <i class="bi bi-printer"></i>
                </a>
                <a href="pedidos/editar.php?id=<?= $inst['pedidocodigo'] ?>" class="btn btn-sm btn-primary">
                    Gerir
                </a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <h6 class="fw-bold text-dark mb-3">Ações Rápidas</h6>
            <div class="d-grid gap-2">
                <a href="orcamentos/criar.php" class="btn-quick shadow-sm d-flex align-items-center justify-content-center fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i> Novo Orçamento
                </a>
                <a href="clientes/listar.php" class="btn-quick shadow-sm d-flex align-items-center justify-content-center fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i> Gerir Clientes
                </a>
                <a href="produtos/listar.php" class="btn-quick shadow-sm d-flex align-items-center justify-content-center fw-bold">
                    <i class="bi bi-box-seam-fill me-2"></i> Ver Estoque
                </a>
            </div>

            <div class="card mt-4 bg-gradient-primary text-white border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="fw-bold mb-1">Dica do Sistema</h6>
                            <p class="small mb-0 opacity-75">Sempre atualize o status para 'Finalizado' após a instalação para baixar o faturamento corretamente.</p>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-lightbulb fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>