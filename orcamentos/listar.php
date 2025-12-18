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

// Filtro por ID exato
if ($f_id) {
    $query .= " AND o.orcamentocodigo = ?";
    $params[] = $f_id;
}

// Filtro por Status
if ($f_status) {
    $query .= " AND o.orcamentosituacao = ?";
    $params[] = $f_status;
}

// Filtro por Data de Criação (Início e Fim)
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
    body { background-color: #f8f9fc; }
    .page-header { background: #fff; padding: 1.2rem; border-bottom: 1px solid #e3e6f0; margin-bottom: 1.5rem; }
    .card-filter { border: none; border-radius: 10px; background-color: #fff; box-shadow: 0 0.15rem 1rem 0 rgba(0,0,0,0.05); }
    .table thead th { background-color: #f8f9fc; text-transform: uppercase; font-size: 0.7rem; color: #4e73df; }
    .status-badge { padding: 0.4em 0.8em; border-radius: 50rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
    .badge-Criado { background-color: #f6c23e; color: #fff; }
    .badge-Aprovado { background-color: #1cc88a; color: #fff; }
    .badge-Enviado { background-color: #4e73df; color: #fff; }
    .badge-Cancelado { background-color: #e74a3b; color: #fff; }
    .btn-action { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; text-decoration: none; }
    .btn-whatsapp { background-color: #25D366; color: #fff !important; }
    .btn-view { background-color: #f0f3ff; color: #4e73df !important; }
    .btn-edit { background-color: #fff4e5; color: #ff9800 !important; }
    .date-column { font-size: 0.85rem; line-height: 1.2; }
</style>

<div class="page-header shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-ruled me-2"></i>Orçamentos</h4>
        </div>
        <a href="criar.php" class="btn btn-primary btn-sm px-4 fw-bold">Novo Orçamento</a>
    </div>
</div>

<div class="container">
    <div class="card card-filter mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Nº Orçamento</label>
                    <input type="number" name="f_id" class="form-control form-control-sm" placeholder="Ex: 105" value="<?= htmlspecialchars($f_id) ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Status</label>
                    <select name="f_status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach($statusDisponiveis as $st): ?>
                            <option value="<?= $st ?>" <?= $f_status == $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Criado entre:</label>
                    <input type="date" name="f_inicio" class="form-control form-control-sm" value="<?= $f_inicio ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Até:</label>
                    <input type="date" name="f_fim" class="form-control form-control-sm" value="<?= $f_fim ?>">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">Filtrar</button>
                    <?php if($f_id || $f_status || $f_inicio || $f_fim): ?>
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
                        $linkAprovacao = BASE_URL . "/public/orcamento.php?c={$o['orcamentolinkaprovacao']}";
                        $urlWhats = "https://api.whatsapp.com/send?phone=55{$whatsapp}&text=".urlencode("Olá, segue seu orçamento: $linkAprovacao");
                        $classeStatus = "badge-" . ($o['orcamentosituacao'] ?? 'Pendente');
                        
                        $dataCriacao = !empty($o['orcamentodatacriacao']) ? date('d/m/Y', strtotime($o['orcamentodatacriacao'])) : '--';
                        $previsaoEntrega = !empty($o['orcamentoprevisaoentrega']) ? date('d/m/Y', strtotime($o['orcamentoprevisaoentrega'])) : '<span class="text-warning">Não definida</span>';
                    ?>
                        <tr>
                            <td class="ps-4 text-center fw-bold text-primary">#<?= $o['orcamentocodigo'] ?></td>
                            <td class="date-column">
                                <div class="text-dark small"><span class="text-muted" style="width: 50px; display:inline-block;">Criado:</span> <strong><?= $dataCriacao ?></strong></div>
                                <div class="text-dark small"><span class="text-muted" style="width: 50px; display:inline-block;">Entrega:</span> <strong><?= $previsaoEntrega ?></strong></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($o['clientenomecompleto'] ?? 'N/A') ?></div>
                                <div class="text-muted small" style="font-size: 0.7rem;"><i class="bi bi-whatsapp me-1"></i><?= $o['clientewhatsapp'] ?></div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?= $classeStatus ?> border-0">
                                    <?= $o['orcamentosituacao'] ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                R$ <?= number_format($o['total'] ?? 0, 2, ',', '.') ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <?php if($whatsapp && $o['orcamentolinkaprovacao']): ?>
                                        <a href="<?= $urlWhats ?>" target="_blank" class="btn-action btn-whatsapp" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                    <?php endif; ?>
                                    <a href="visualizar.php?id=<?= $o['orcamentocodigo'] ?>" class="btn-action btn-view" title="Ver"><i class="bi bi-eye-fill"></i></a>
                                    <a href="editar.php?id=<?= $o['orcamentocodigo'] ?>" class="btn-action btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if(empty($orcamentos)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted small">
                                <i class="bi bi-search fs-2 d-block mb-2 opacity-50"></i>
                                Nenhum orçamento encontrado com os filtros aplicados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>