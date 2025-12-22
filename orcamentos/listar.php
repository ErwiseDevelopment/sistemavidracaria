<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

// Pegar filtros da URL
$f_id     = $_GET['f_id'] ?? '';
$f_status = $_GET['f_status'] ?? '';
$f_inicio = $_GET['f_inicio'] ?? '';
$f_fim    = $_GET['f_fim'] ?? '';

// Construir consulta base
$query = "
    SELECT o.*, c.clientenomecompleto, c.clientewhatsapp,
           (SELECT SUM(orcamentovalortotal) 
            FROM orcamentoitem i 
            WHERE i.orcamentocodigo = o.orcamentocodigo) AS total
    FROM orcamento o
    LEFT JOIN clientes c ON o.clienteid = c.clientecodigo
    WHERE 1=1";

$params = [];

if ($f_id) {
    $query .= " AND o.orcamentocodigo = ?";
    $params[] = $f_id;
}
if ($f_status) {
    $query .= " AND o.orcamentosituacao = ?";
    $params[] = $f_status;
}
if ($f_inicio) {
    $query .= " AND o.orcamentodatacriacao >= ?";
    $params[] = $f_inicio;
}
if ($f_fim) {
    $query .= " AND o.orcamentodatacriacao <= ?";
    $params[] = $f_fim . " 23:59:59";
}

$query .= " ORDER BY o.orcamentocodigo DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusDisponiveis = ['Criado', 'Enviado', 'Aprovado', 'Finalizado', 'Cancelado'];
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
    .badge-Criado { background-color: #fef3c7; color: #d97706; }
    .badge-Aprovado { background-color: #dcfce7; color: #16a34a; }
    .badge-Enviado { background-color: #e0f2fe; color: #0284c7; }
    .badge-Finalizado { background-color: #d1fae5; color: #065f46; }
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
    
    .order-number {
        background: #f0f3ff;
        color: var(--primary);
        font-weight: 800;
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    .client-name { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 5px; }
    
    .order-info { font-size: 0.85rem; color: #64748b; margin-bottom: 10px; }
    .order-total { font-size: 1.2rem; font-weight: 800; color: var(--primary); display: block; margin-top: 10px; }

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
        <h4 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-ruled me-2"></i>Orçamentos</h4>
        <a href="criar.php" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill shadow-sm">Novo Orçamento</a>
    </div>
</div>

<div class="container">
    <div class="card filter-section shadow-sm">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2 col-6">
                    <label class="small fw-bold text-muted">Nº Orçamento</label>
                    <input type="number" name="f_id" class="form-control form-control-sm" placeholder="Ex: 105" value="<?= htmlspecialchars($f_id) ?>">
                </div>
                <div class="col-md-3 col-6">
                    <label class="small fw-bold text-muted">Status</label>
                    <select name="f_status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach($statusDisponiveis as $st): ?>
                            <option value="<?= $st ?>" <?= $f_status == $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="small fw-bold text-muted">Início:</label>
                    <input type="date" name="f_inicio" class="form-control form-control-sm" value="<?= $f_inicio ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label class="small fw-bold text-muted">Até:</label>
                    <input type="date" name="f_fim" class="form-control form-control-sm" value="<?= $f_fim ?>">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">Filtrar</button>
                    <?php if($f_id || $f_status || $f_inicio || $f_fim): ?>
                        <a href="listar.php" class="btn btn-outline-danger btn-sm px-3"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="mobile-list">
        <?php foreach($orcamentos as $o): 
            $whatsapp = preg_replace('/\D/', '', $o['clientewhatsapp'] ?? '');
            $linkAprovacao = BASE_URL . "/public/orcamento.php?c={$o['orcamentolinkaprovacao']}";
            $urlWhats = "https://api.whatsapp.com/send?phone=55{$whatsapp}&text=".urlencode("Olá, segue seu orçamento: $linkAprovacao");
        ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-number">#<?= $o['orcamentocodigo'] ?></span>
                    <span class="status-badge badge-<?= $o['orcamentosituacao'] ?>"><?= $o['orcamentosituacao'] ?></span>
                </div>
                <div class="client-name"><?= htmlspecialchars($o['clientenomecompleto'] ?? 'N/A') ?></div>
                <div class="order-info">
                    <div><i class="bi bi-calendar-check me-1"></i> Criado: <?= date('d/m/Y', strtotime($o['orcamentodatacriacao'])) ?></div>
                    <div class="text-muted small"><i class="bi bi-whatsapp me-1"></i> <?= $o['clientewhatsapp'] ?></div>
                </div>
                <div class="order-total">R$ <?= number_format($o['total'] ?? 0, 2, ',', '.') ?></div>
                
                <div class="action-group">
                    <?php if($whatsapp && $o['orcamentolinkaprovacao']): ?>
                        <a href="<?= $urlWhats ?>" target="_blank" class="btn-circle btn-wa"><i class="bi bi-whatsapp"></i></a>
                    <?php endif; ?>
                    <a href="editar.php?id=<?= $o['orcamentocodigo'] ?>" class="btn-circle btn-ed"><i class="bi bi-pencil"></i></a>
                    <a href="visualizar.php?id=<?= $o['orcamentocodigo'] ?>" class="btn-circle btn-vi"><i class="bi bi-eye"></i></a>
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
                        <th>Datas</th>
                        <th>Cliente</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orcamentos as $o): 
                        $whatsapp = preg_replace('/\D/', '', $o['clientewhatsapp'] ?? '');
                    ?>
                        <tr>
                            <td class="ps-4 text-center fw-bold text-primary">#<?= $o['orcamentocodigo'] ?></td>
                            <td class="small">
                                <div><span class="text-muted">Criado:</span> <?= date('d/m/Y', strtotime($o['orcamentodatacriacao'])) ?></div>
                                <div><span class="text-muted">Entrega:</span> <?= !empty($o['orcamentoprevisaoentrega']) ? date('d/m/Y', strtotime($o['orcamentoprevisaoentrega'])) : '--' ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($o['clientenomecompleto'] ?? 'N/A') ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-whatsapp me-1"></i><?= $o['clientewhatsapp'] ?></div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge badge-<?= $o['orcamentosituacao'] ?>"><?= $o['orcamentosituacao'] ?></span>
                            </td>
                            <td class="text-end fw-bold">R$ <?= number_format($o['total'] ?? 0, 2, ',', '.') ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="https://api.whatsapp.com/send?phone=55<?= $whatsapp ?>" target="_blank" class="btn btn-sm btn-light text-success"><i class="bi bi-whatsapp"></i></a>
                                    <a href="visualizar.php?id=<?= $o['orcamentocodigo'] ?>" class="btn btn-sm btn-light text-primary"><i class="bi bi-eye"></i></a>
                                    <a href="editar.php?id=<?= $o['orcamentocodigo'] ?>" class="btn btn-sm btn-light text-warning"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(empty($orcamentos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search fs-1 opacity-25 d-block mb-3"></i>
            Nenhum orçamento encontrado.
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>