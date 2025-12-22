<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

$f_id     = $_GET['f_id'] ?? '';
$f_status = $_GET['situacao'] ?? '';
$f_busca  = $_GET['busca'] ?? '';

$where = [];
$params = [];
if($f_id) { $where[] = "p.pedidocodigo = ?"; $params[] = $f_id; }
if($f_status) { $where[] = "p.pedidosituacao = ?"; $params[] = $f_status; }
if($f_busca) { $where[] = "c.clientenomecompleto LIKE ?"; $params[] = "%$f_busca%"; }

$sql = "SELECT p.*, c.clientenomecompleto, c.clientewhatsapp
        FROM pedido p
        JOIN clientes c ON p.clienteid = c.clientecodigo";
if($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY p.pedidocodigo DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();
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

    /* Filtros Modernos */
    .filter-section {
        background: white;
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        margin-bottom: 2rem;
    }

    /* Estilo dos Cards de Pedido (Mobile & Desktop) */
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
    
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08);
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .order-number {
        background: #f0f3ff;
        color: var(--primary);
        font-weight: 800;
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    .client-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
        display: block;
    }

    .order-info {
        display: flex;
        gap: 15px;
        margin-top: 10px;
        font-size: 0.85rem;
        color: #64748b;
    }

    .order-total {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary);
        margin-top: 1rem;
        display: block;
    }

    /* Badges de Status */
    .badge-status {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 6px 12px;
        border-radius: 10px;
        letter-spacing: 0.5px;
    }
    .st-Criado { background: #e2e8f0; color: #475569; }
    .st-Produção { background: #fef3c7; color: #d97706; }
    .st-Instalação { background: #e0f2fe; color: #0284c7; }
    .st-Finalizado { background: #dcfce7; color: #16a34a; }
    .st-Cancelado { background: #fee2e2; color: #dc2626; }

    /* Botões de Ação flutuantes */
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
        height: 45px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: 0.2s;
    }
    .btn-wa { background: #25D366; color: white; }
    .btn-ed { background: #f1f5f9; color: #475569; }
    .btn-vi { background: var(--primary); color: white; }

    /* Esconder tabela em telas menores */
    @media (max-width: 768px) {
        .desktop-table { display: none; }
    }
    @media (min-width: 769px) {
        .mobile-list { display: none; }
    }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Pedidos</h2>
        <a href="novo.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow">Novo Pedido</a>
    </div>

    <div class="card filter-section shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                <div class="col-md-2 col-4">
                    <label class="small fw-bold text-muted">Nº</label>
                    <input type="number" name="f_id" class="form-control" placeholder="ID" value="<?= $f_id ?>">
                </div>
                <div class="col-md-3 col-8">
                    <label class="small fw-bold text-muted">Status</label>
                    <select name="situacao" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach($statusDisponiveis as $st): ?>
                            <option value="<?= $st ?>" <?= $f_status == $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 col-12">
                    <label class="small fw-bold text-muted">Cliente</label>
                    <input type="text" name="busca" class="form-control" placeholder="Nome do cliente..." value="<?= $f_busca ?>">
                </div>
                <div class="col-md-2 col-12 d-grid">
                    <label class="d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="btn btn-dark fw-bold">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mobile-list">
        <?php foreach($pedidos as $p): 
            $whatsapp = preg_replace('/\D/', '', $p['clientewhatsapp'] ?? '');
        ?>
        <div class="order-card">
            <div class="order-header">
                <span class="order-number">#<?= $p['pedidocodigo'] ?></span>
                <span class="badge-status st-<?= $p['pedidosituacao'] ?>"><?= $p['pedidosituacao'] ?></span>
            </div>
            
            <h3 class="client-name"><?= htmlspecialchars($p['clientenomecompleto']) ?></h3>
            
            <div class="order-info">
                <span><i class="bi bi-calendar3 me-1"></i> <?= $p['pedidoprevisaoentrega'] ? date('d/m/Y', strtotime($p['pedidoprevisaoentrega'])) : '--' ?></span>
                <span><i class="bi bi-clock me-1"></i> Prevista</span>
            </div>

            <span class="order-total">R$ <?= number_format($p['pedidototal'], 2, ",", ".") ?></span>

            <div class="action-group">
                <?php if($whatsapp): ?>
                    <a href="https://api.whatsapp.com/send?phone=55<?= $whatsapp ?>" class="btn-circle btn-wa"><i class="bi bi-whatsapp"></i></a>
                <?php endif; ?>
                <a href="editar.php?id=<?= $p['pedidocodigo'] ?>" class="btn-circle btn-ed"><i class="bi bi-pencil-square"></i></a>
                <a href="visualizar.php?id=<?= $p['pedidocodigo'] ?>" class="btn-circle btn-vi">Ver</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="desktop-table bg-white rounded-4 shadow-sm overflow-hidden">
        <table class="table align-middle mb-0">
            <thead class="bg-light">
                <tr class="text-muted small uppercase">
                    <th class="p-3 ps-4">Pedido</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Entrega</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pedidos as $p): ?>
                <tr>
                    <td class="p-3 ps-4 fw-bold">#<?= $p['pedidocodigo'] ?></td>
                    <td><span class="fw-bold"><?= htmlspecialchars($p['clientenomecompleto']) ?></span></td>
                    <td><span class="badge-status st-<?= $p['pedidosituacao'] ?>"><?= $p['pedidosituacao'] ?></span></td>
                    <td><?= $p['pedidoprevisaoentrega'] ? date('d/m/Y', strtotime($p['pedidoprevisaoentrega'])) : '--' ?></td>
                    <td class="text-end fw-bold text-primary">R$ <?= number_format($p['pedidototal'], 2, ",", ".") ?></td>
                    <td class="text-center p-3">
                        <div class="d-flex gap-2 justify-content-center">
                             <a href="editar.php?id=<?= $p['pedidocodigo'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                             <a href="visualizar.php?id=<?= $p['pedidocodigo'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>