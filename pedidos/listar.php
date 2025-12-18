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
$pedidos = $stmt->fetchAll();

$totalPedidos = count($pedidos);
$statusDisponiveis = ['Criado','Produção','Instalação','Finalizado','Cancelado'];
?>

<style>
    body { background-color: #f8f9fc; }
    .page-header { background: #fff; padding: 1.2rem; border-bottom: 1px solid #e3e6f0; margin-bottom: 1.5rem; }
    .card-filter { border: none; border-radius: 10px; background-color: #fff; box-shadow: 0 0.15rem 1rem 0 rgba(0,0,0,0.05); }
    
    .table thead th { 
        background-color: #f8f9fc; 
        text-transform: uppercase; 
        font-size: 0.7rem; 
        color: #4e73df; 
        letter-spacing: 0.05rem;
    }

    /* Cores de Status seguindo o padrão */
    .status-badge { padding: 0.4em 0.8em; border-radius: 50rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
    .badge-Criado { background-color: #858796; color: #fff; }
    .badge-Produção { background-color: #f6c23e; color: #fff; }
    .badge-Instalação { background-color: #36b9cc; color: #fff; }
    .badge-Finalizado { background-color: #1cc88a; color: #fff; }
    .badge-Cancelado { background-color: #e74a3b; color: #fff; }

    /* Botões de Ação Quadrados como no Orçamento */
    .btn-action { 
        width: 35px; height: 35px; 
        display: inline-flex; align-items: center; justify-content: center; 
        border-radius: 8px; transition: 0.2s; text-decoration: none; border: none;
    }
    .btn-view { background-color: #f0f3ff; color: #4e73df !important; }
    .btn-edit { background-color: #fff4e5; color: #ff9800 !important; }
    .btn-whatsapp { background-color: #25D366; color: #fff !important; }
    
    .btn-action:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
</style>

<div class="page-header shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2"></i>Pedidos</h4>
        </div>
        
    </div>
</div>

<div class="container">
    <div class="card card-filter mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Nº Pedido</label>
                    <input type="number" name="f_id" class="form-control form-control-sm" placeholder="Ex: 501" value="<?= htmlspecialchars($f_id) ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Situação</label>
                    <select name="situacao" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach($statusDisponiveis as $st): ?>
                            <option value="<?= $st ?>" <?= $f_status == $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Cliente</label>
                    <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome do cliente..." value="<?= htmlspecialchars($f_busca) ?>">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">Filtrar</button>
                    <?php if($f_id || $f_status || $f_busca): ?>
                        <a href="listar.php" class="btn btn-outline-danger btn-sm" title="Limpar"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 text-center" width="80">Nº</th>
                        <th>Cliente</th>
                        <th class="text-center">Situação</th>
                        <th>Previsão Entrega</th>
                        <th class="text-end">Total</th>
                        <th class="text-center" width="180">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!$pedidos): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 opacity-50"></i>
                                Nenhum pedido encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach($pedidos as $p): 
                        $whatsapp = preg_replace('/\D/', '', $p['clientewhatsapp'] ?? '');
                        $urlWhats = "https://api.whatsapp.com/send?phone=55{$whatsapp}";
                    ?>
                        <tr>
                            <td class="ps-4 text-center fw-bold text-primary">#<?= $p['pedidocodigo'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['clientenomecompleto']) ?></div>
                                <div class="text-muted small" style="font-size: 0.7rem;"><i class="bi bi-whatsapp me-1"></i><?= $p['clientewhatsapp'] ?></div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge badge-<?= $p['pedidosituacao'] ?>">
                                    <?= $p['pedidosituacao'] ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <i class="bi bi-calendar-event me-1"></i>
                                <?= $p['pedidoprevisaoentrega'] ? date('d/m/Y', strtotime($p['pedidoprevisaoentrega'])) : '--/--/----' ?>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                R$ <?= number_format($p['pedidototal'], 2, ",", ".") ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <?php if($whatsapp): ?>
                                        <a href="<?= $urlWhats ?>" target="_blank" class="btn-action btn-whatsapp" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                    <?php endif; ?>
                                    
                                    <a href="visualizar.php?id=<?= $p['pedidocodigo'] ?>" class="btn-action btn-view" title="Ver Detalhes">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    
                                    <a href="editar.php?id=<?= $p['pedidocodigo'] ?>" class="btn-action btn-edit" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>