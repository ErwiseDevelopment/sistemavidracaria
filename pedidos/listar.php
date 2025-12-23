<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

/* ======================
    FILTROS E LÓGICA
====================== */
$f_id     = $_GET['f_id'] ?? '';
$f_status = $_GET['situacao'] ?? '';
$f_busca  = $_GET['busca'] ?? '';

$where = [];
$params = [];

if($f_id){
    $where[] = "p.pedidocodigo = ?";
    $params[] = $f_id;
}
if($f_status){
    $where[] = "p.pedidosituacao = ?";
    $params[] = $f_status;
}
if($f_busca){
    $where[] = "c.clientenomecompleto LIKE ?";
    $params[] = "%$f_busca%";
}

$sql = "
    SELECT p.*, c.clientenomecompleto, c.clientewhatsapp
    FROM pedido p
    JOIN clientes c ON p.clienteid = c.clientecodigo
";

if($where){
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.pedidocodigo DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusDisponiveis = ['Criado','Produção','Instalação','Finalizado','Cancelado'];
?>

<style>
    :root {
        --primary: #4361ee;
        --success: #2ec4b6;
        --danger: #e71d36;
        --warning: #ff9f1c;
        --dark: #011627;
        --light-bg: #f8f9fd;
    }

    body { background-color: var(--light-bg); font-family: 'Inter', sans-serif; }

    /* Header */
    .page-header { background: #fff; padding: 1.2rem; border-bottom: 1px solid #e3e6f0; margin-bottom: 1.5rem; }

    /* Filtros */
    .filter-section {
        background: white;
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        margin-bottom: 2rem;
    }

    /* Badges Estilizados */
    .status-badge {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 6px 12px;
        border-radius: 10px;
        letter-spacing: 0.5px;
        display: inline-block;
    }
    /* Cores personalizadas para Pedidos */
    .badge-Criado { background-color: #f1f5f9; color: #475569; }
    .badge-Produção { background-color: #fef3c7; color: #d97706; }
    .badge-Instalação { background-color: #e0f2fe; color: #0284c7; }
    .badge-Finalizado { background-color: #dcfce7; color: #16a34a; }
    .badge-Cancelado { background-color: #fee2e2; color: #dc2626; }

    /* Card Mobile */
    .order-card {
        background: white;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.03);
        padding: 1.5rem;
        margin-bottom: 1.2rem;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .order-number { background: #f0f3ff; color: var(--primary); font-weight: 800; padding: 6px 14px; border-radius: 12px; font-size: 0.9rem; }
    .client-name { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 5px; }
    .order-info { font-size: 0.85rem; color: #64748b; margin-bottom: 10px; }
    .order-total { font-size: 1.2rem; font-weight: 800; color: var(--dark); display: block; margin-top: 10px; }

    /* Ações */
    .action-group {
        display: flex;
        gap: 8px;
        margin-top: 1.2rem;
        border-top: 1px solid #f1f5f9;
        padding-top: 1.2rem;
    }
    .btn-circle {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 42px;
        border-radius: 12px;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-wa { background: #25D366; color: white !important; }
    .btn-vi { background: #f0f3ff; color: var(--primary) !important; }
    .btn-ed { background: #fff7ed; color: #ea580c !important; }

    /* Visibilidade por dispositivo */
    @media (max-width: 768px) { .desktop-table { display: none; } }
    @media (min-width: 769px) { .mobile-list { display: none; } }
</style>

<div class="page-header shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <h4 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2"></i>Pedidos</h4>
        <div class="text-muted small d-none d-md-block">Total: <strong><?= count($pedidos) ?></strong> pedidos</div>
    </div>
</div>

<div class="container">
    <div class="card filter-section shadow-sm">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2 col-6">
                    <label class="small fw-bold text-muted">Nº Pedido</label>
                    <input type="number" name="f_id" class="form-control form-control-sm" placeholder="Ex: 501" value="<?= htmlspecialchars($f_id) ?>">
                </div>
                <div class="col-md-3 col-6">
                    <label class="small fw-bold text-muted">Situação</label>
                    <select name="situacao" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach($statusDisponiveis as $st): ?>
                            <option value="<?= $st ?>" <?= $f_status == $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-12">
                    <label class="small fw-bold text-muted">Cliente</label>
                    <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome do cliente..." value="<?= htmlspecialchars($f_busca) ?>">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">Filtrar</button>
                    <?php if($f_id || $f_status || $f_busca): ?>
                        <a href="listar.php" class="btn btn-outline-danger btn-sm px-3"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="mobile-list">
        <?php foreach($pedidos as $p): 
            $whatsapp = preg_replace('/\D/', '', $p['clientewhatsapp'] ?? '');
            $urlWhats = "https://api.whatsapp.com/send?phone=55{$whatsapp}";
        ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-number">#<?= $p['pedidocodigo'] ?></span>
                    <span class="status-badge badge-<?= $p['pedidosituacao'] ?>"><?= $p['pedidosituacao'] ?></span>
                </div>
                <div class="client-name"><?= htmlspecialchars($p['clientenomecompleto']) ?></div>
                <div class="order-info">
                    <div><i class="bi bi-calendar-event me-1"></i> Entrega: <?= $p['pedidoprevisaoentrega'] ? date('d/m/Y', strtotime($p['pedidoprevisaoentrega'])) : '--/--/----' ?></div>
                    <div class="text-muted small"><i class="bi bi-whatsapp me-1"></i> <?= $p['clientewhatsapp'] ?></div>
                </div>
                <div class="order-total">R$ <?= number_format($p['pedidototal'], 2, ',', '.') ?></div>
                
                <div class="action-group">
                    <?php if($whatsapp): ?>
                        <a href="<?= $urlWhats ?>" target="_blank" class="btn-circle btn-wa"><i class="bi bi-whatsapp"></i></a>
                    <?php endif; ?>
                    <a href="editar.php?id=<?= $p['pedidocodigo'] ?>" class="btn-circle btn-ed"><i class="bi bi-pencil"></i></a>
                    <a href="visualizar.php?id=<?= $p['pedidocodigo'] ?>" class="btn-circle btn-vi"><i class="bi bi-eye"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="desktop-table card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 text-center">Nº</th>
                        <th>Cliente</th>
                        <th class="text-center">Situação</th>
                        <th>Previsão Entrega</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pedidos as $p): 
                        $whatsapp = preg_replace('/\D/', '', $p['clientewhatsapp'] ?? '');
                    ?>
                        <tr>
                            <td class="ps-4 text-center fw-bold text-primary">#<?= $p['pedidocodigo'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['clientenomecompleto']) ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-whatsapp me-1"></i><?= $p['clientewhatsapp'] ?></div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge badge-<?= $p['pedidosituacao'] ?>"><?= $p['pedidosituacao'] ?></span>
                            </td>
                            <td class="small text-muted">
                                <i class="bi bi-calendar-event me-1"></i>
                                <?= $p['pedidoprevisaoentrega'] ? date('d/m/Y', strtotime($p['pedidoprevisaoentrega'])) : '--/--/----' ?>
                            </td>
                            <td class="text-end fw-bold">R$ <?= number_format($p['pedidototal'], 2, ',', '.') ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="https://api.whatsapp.com/send?phone=55<?= $whatsapp ?>" target="_blank" class="btn btn-sm btn-light text-success"><i class="bi bi-whatsapp"></i></a>
                                    <a href="visualizar.php?id=<?= $p['pedidocodigo'] ?>" class="btn btn-sm btn-light text-primary"><i class="bi bi-eye"></i></a>
                                    <a href="editar.php?id=<?= $p['pedidocodigo'] ?>" class="btn btn-sm btn-light text-warning"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(empty($pedidos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search fs-1 opacity-25 d-block mb-3"></i>
            Nenhum pedido encontrado.
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>