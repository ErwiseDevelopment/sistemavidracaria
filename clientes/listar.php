<?php
require_once "../config/config.php"; // Para usar BASE_URL se necessário
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

// Buscar todos os clientes
$sql = $pdo->query("SELECT * FROM clientes ORDER BY clientecodigo DESC");
$clientes = $sql->fetchAll();
?>

<style>
    body { background-color: #f8f9fc; }
    .card-table { border: none; border-radius: 15px; overflow: hidden; }
    .table thead th { 
        background-color: #f1f4f9; 
        text-transform: uppercase; 
        font-size: 0.75rem; 
        letter-spacing: 0.05em; 
        color: #4e73df; 
        border-top: none; 
    }
    .avatar-circle {
        width: 35px;
        height: 35px;
        background: #4e73df;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
        font-size: 0.8rem;
    }
    .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Base de Clientes</h4>
            <p class="text-muted small mb-0">Gerencie os contatos e endereços da Visa Vidros</p>
        </div>
        <a href="criar.php" class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-person-plus-fill me-2"></i>Novo Cliente
        </a>
    </div>

    <div class="card card-table shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" width="80">ID</th>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Localização</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): 
                            $waNumber = preg_replace('/\D/', '', $c['clientewhatsapp'] ?? '');
                            // Pegar iniciais para o "avatar"
                            $iniciais = strtoupper(substr($c['clientenomecompleto'], 0, 1));
                        ?>
                            <tr>
                                <td class="ps-4 text-muted small">#<?= $c['clientecodigo'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3"><?= $iniciais ?></div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($c['clientenomecompleto'] ?? '') ?></div>
                                            <small class="text-muted">Cadastrado em: <?= date('d/m/Y') // Caso tenha campo data, substitua aqui ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if($waNumber): ?>
                                        <a href="https://wa.me/55<?= $waNumber ?>" target="_blank" class="text-decoration-none text-success fw-bold small">
                                            <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($c['clientewhatsapp'] ?? '') ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Sem telefone</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="bi bi-geo-alt me-1 text-muted"></i>
                                        <?= htmlspecialchars($c['clientecidade'] ?? 'Não informada') ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if($c['clientesituacao']): ?>
                                        <span class="badge bg-success-subtle text-success px-3 border border-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted px-3 border">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group gap-1">
                                        <a href="editar.php?id=<?= $c['clientecodigo'] ?>" class="btn-action btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($clientes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                                    Nenhum cliente encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>