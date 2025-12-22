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
$totalOrcamentos = $pdo->query("SELECT COUNT(*) FROM orcamento WHERE orcamentosituacao = 'Criado'")->fetchColumn();
$pedidosProducao = $pdo->query("SELECT COUNT(*) FROM pedido WHERE pedidosituacao = 'Produção'")->fetchColumn();

// 3. Faturamento: Soma de pedidos que NÃO estão cancelados (Considerando o mês atual)
$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');
$sqlFaturamento = $pdo->prepare("
    SELECT SUM(pedidototal) 
    FROM pedido 
    WHERE pedidosituacao <> 'Cancelado' 
    AND pedidoprevisaoentrega BETWEEN ? AND ?
");
$sqlFaturamento->execute([$inicioMes, $fimMes]);
$faturamentoMes = $sqlFaturamento->fetchColumn() ?: 0;
?>

<style>
    body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
    
    /* Cards de Estatísticas */
    .card-stat { border: none; border-radius: 16px; transition: all 0.3s ease; overflow: hidden; }
    .card-stat:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
    
    .icon-shape { width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

    .bg-gradient-blue { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; }
    .bg-gradient-cyan { background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); color: white; }
    .bg-gradient-emerald { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; }

    /* Ações Rápidas */
    .btn-quick { border: 1px solid #e2e8f0; background: #fff; padding: 20px; border-radius: 16px; text-align: center; color: #334155; text-decoration: none; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .btn-quick i { font-size: 1.8rem; color: #38bdf8; }
    .btn-quick:hover { border-color: #38bdf8; background: #f0f9ff; transform: scale(1.02); }

    /* Estilo para Mobile Cards (substitui a tabela em telas pequenas) */
    @media (max-width: 767.98px) {
        .mobile-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 5px solid #4361ee;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .desktop-table { display: none; }
    }

    @media (min-width: 768px) {
        .mobile-view { display: none; }
    }
</style>

<div class="container mt-4 mb-5">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-8">
            <h2 class="fw-bold text-dark mb-1">Painel de Controle</h2>
            <p class="text-muted mb-0">Olá, <strong><?= explode(' ', $_SESSION['usuario_nome'])[0]; ?></strong>. Resumo da Visa Vidros.</p>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card card-stat bg-gradient-blue shadow-sm">
                <div class="card-body p-4 text-center text-md-start">
                    <p class="text-white-50 small fw-bold text-uppercase mb-1">Orçamentos Abertos</p>
                    <h2 class="mb-0 fw-bold"><?= $totalOrcamentos ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-gradient-cyan shadow-sm">
                <div class="card-body p-4 text-center text-md-start">
                    <p class="text-white-50 small fw-bold text-uppercase mb-1">Em Produção</p>
                    <h2 class="mb-0 fw-bold"><?= $pedidosProducao ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-gradient-emerald shadow-sm">
                <div class="card-body p-4 text-center text-md-start">
                    <p class="text-white-50 small fw-bold text-uppercase mb-1">Faturamento Mês</p>
                    <h2 class="mb-0 fw-bold">R$ <?= number_format($faturamentoMes, 2, ',', '.') ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h5 class="fw-bold mb-3"><i class="bi bi-truck me-2"></i>Agenda de Instalações</h5>
            
            <div class="desktop-table card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Data/Hora</th>
                                    <th>Cliente</th>
                                    <th>Pedido</th>
                                    <th class="text-end pe-4">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($proximasInstalacoes as $inst): ?>
                                <tr>
                                    <td class="ps-4"><strong><?= date('d/m/Y', strtotime($inst['pedidoprevisaoentrega'])) ?></strong></td>
                                    <td><?= htmlspecialchars($inst['clientenomecompleto']) ?></td>
                                    <td><span class="badge bg-light text-dark border">#<?= $inst['pedidocodigo'] ?></span></td>
                                    <td class="text-end pe-4"><a href="pedidos/editar.php?id=<?= $inst['pedidocodigo'] ?>" class="btn btn-sm btn-primary rounded-pill">Gerenciar</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mobile-view">
                <?php if(!$proximasInstalacoes): ?>
                    <div class="text-center p-4 bg-white rounded shadow-sm">
                        <p class="text-muted mb-0">Nenhuma instalação para hoje.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($proximasInstalacoes as $inst): 
                        $waNumber = preg_replace('/\D/', '', $inst['clientewhatsapp']);
                    ?>
                        <div class="mobile-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-primary fw-bold"><?= date('d/m/Y H:i', strtotime($inst['pedidoprevisaoentrega'])) ?></small>
                                    <h6 class="fw-bold mb-1 mt-1 text-dark"><?= htmlspecialchars($inst['clientenomecompleto']) ?></h6>
                                    <p class="mb-2 small text-muted">Pedido #<?= $inst['pedidocodigo'] ?></p>
                                </div>
                                <a href="https://wa.me/+55<?= $waNumber ?>" class="btn btn-success btn-sm rounded-circle"><i class="bi bi-whatsapp"></i></a>
                            </div>
                            <div class="d-grid mt-2">
                                <a href="pedidos/editar.php?id=<?= $inst['pedidocodigo'] ?>" class="btn btn-outline-primary btn-sm rounded-pill">Ver Detalhes</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <h6 class="fw-bold text-dark mb-3">Ações Rápidas</h6>
            <div class="row g-2">
                <div class="col-6">
                    <a href="orcamentos/criar.php" class="btn-quick shadow-sm text-decoration-none">
                        <i class="bi bi-file-earmark-plus"></i>
                        <span class="small fw-bold">Novo Orçamento</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="pedidos/listar.php" class="btn-quick shadow-sm text-decoration-none">
                        <i class="bi bi-cart-plus"></i>
                        <span class="small fw-bold">Ver Pedidos</span>
                    </a>
                </div>
            </div>
            
            <div class="card mt-4 border-0 shadow-sm rounded-4 bg-primary text-white">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold mb-1">Deseja ajuda?</h6>
                    <p class="small opacity-75">Suporte rápido disponível.</p>
                    <a href="https://wa.me/5511934008521" class="btn btn-light btn-sm rounded-pill px-4 fw-bold">WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>