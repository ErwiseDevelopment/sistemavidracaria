<?php
require_once "../config/config.php"; 
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

// Buscar todos os clientes
$sql = $pdo->query("SELECT * FROM clientes ORDER BY clientecodigo DESC");
$clientes = $sql->fetchAll();
?>

<style>
    body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
    
    /* Estilo da Tabela Desktop */
    .card-table { border: none; border-radius: 16px; background: white; }
    .table thead th { 
        background-color: #f8fafc; 
        text-transform: uppercase; 
        font-size: 0.7rem; 
        font-weight: 700;
        letter-spacing: 0.05em; 
        color: #64748b; 
        border-top: none;
        padding: 15px;
    }
    
    .avatar-circle {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        color: white;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px;
        font-weight: bold; font-size: 1rem;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
    }

    /* Cards para Mobile */
    @media (max-width: 767.98px) {
        .desktop-view { display: none; }
        .mobile-card {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .mobile-card .client-name { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
    }

    @media (min-width: 768px) {
        .mobile-view { display: none; }
    }

    .btn-action { 
        width: 38px; height: 38px; 
        display: inline-flex; align-items: center; justify-content: center; 
        border-radius: 10px; transition: 0.2s; 
    }
</style>

<div class="container mt-4 mb-5">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-8 col-md-6">
            <h4 class="fw-800 text-slate-900 mb-0">Base de Clientes</h4>
            <p class="text-muted small mb-0">Gerencie contatos da Visa Vidros</p>
        </div>
        <div class="col-4 col-md-6 text-end">
            <a href="criar.php" class="btn btn-primary rounded-pill shadow-sm px-md-4">
                <i class="bi bi-person-plus-fill"></i> <span class="d-none d-md-inline ms-1">Novo Cliente</span>
            </a>
        </div>
    </div>

    <div class="card card-table shadow-sm desktop-view">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Cliente</th>
                            <th>Contato</th>
                            <th>Localização</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): 
                            $waNumber = preg_replace('/\D/', '', $c['clientewhatsapp'] ?? '');
                            $iniciais = strtoupper(substr($c['clientenomecompleto'], 0, 1));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center text-decoration-none">
                                    <div class="avatar-circle me-3"><?= $iniciais ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['clientenomecompleto']) ?></div>
                                        <small class="text-muted">ID: #<?= $c['clientecodigo'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if($waNumber): ?>
                                    <a href="https://wa.me/+55<?= $waNumber ?>" target="_blank" class="btn btn-sm btn-light border text-success fw-bold rounded-pill px-3">
                                        <i class="bi bi-whatsapp me-1"></i> <?= $c['clientewhatsapp'] ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">---</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-slate-600">
                                    <i class="bi bi-geo-alt me-1 text-primary"></i>
                                    <?= htmlspecialchars($c['clientecidade'] ?? '---') ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if($c['clientesituacao']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success px-3">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border px-3">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="editar.php?id=<?= $c['clientecodigo'] ?>" class="btn-action btn btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mobile-view">
        <?php foreach ($clientes as $c): 
            $waNumber = preg_replace('/\D/', '', $c['clientewhatsapp'] ?? '');
            $iniciais = strtoupper(substr($c['clientenomecompleto'], 0, 1));
        ?>
            <div class="mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3" style="width: 35px; height: 35px; font-size: 0.9rem;"><?= $iniciais ?></div>
                        <span class="text-muted small">ID #<?= $c['clientecodigo'] ?></span>
                    </div>
                    <?php if($c['clientesituacao']): ?>
                        <span class="badge bg-success-subtle text-success small">Ativo</span>
                    <?php endif; ?>
                </div>
                
                <div class="client-name mb-1"><?= htmlspecialchars($c['clientenomecompleto']) ?></div>
                <div class="text-muted small mb-3">
                    <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($c['clientecidade'] ?? 'Cidade não informada') ?>
                </div>

                <div class="d-flex gap-2">
                    <?php if($waNumber): ?>
                        <a href="https://wa.me/+55<?= $waNumber ?>" target="_blank" class="btn btn-success flex-grow-1 rounded-pill">
                            <i class="bi bi-whatsapp me-2"></i>WhatsApp
                        </a>
                    <?php endif; ?>
                    <a href="editar.php?id=<?= $c['clientecodigo'] ?>" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if(empty($clientes)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm mt-3">
            <i class="bi bi-people fs-1 text-muted opacity-25"></i>
            <p class="text-muted mt-2">Nenhum cliente cadastrado ainda.</p>
        </div>
    <?php endif; ?>

</div>

<?php require_once "../includes/footer.php"; ?>