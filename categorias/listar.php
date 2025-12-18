<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

// Buscar todas as categorias
$sql = $pdo->query("SELECT * FROM categoriaproduto ORDER BY categoriacodigo DESC");
$categorias = $sql->fetchAll();
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
    .category-icon {
        width: 40px;
        height: 40px;
        background: #eef2f7;
        color: #4e73df;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.2rem;
    }
    .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Categorias de Produtos</h4>
            <p class="text-muted small mb-0">Organize seus produtos por tipo (Vidros, Ferragens, Alumínios)</p>
        </div>
        <a href="criar.php" class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-plus-lg me-2"></i>Nova Categoria
        </a>
    </div>

    <div class="card card-table shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" width="100">Código</th>
                            <th>Descrição da Categoria</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#<?= $c['categoriacodigo'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="category-icon me-3">
                                            <i class="bi bi-tag-fill"></i>
                                        </div>
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars($c['categoriadescricao'] ?? '') ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if($c['categoriasituacao']): ?>
                                        <span class="badge bg-success-subtle text-success px-3 border border-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted px-3 border">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group gap-1">
                                        <a href="editar.php?id=<?= $c['categoriacodigo'] ?>" class="btn-action btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="excluir.php?id=<?= $c['categoriacodigo'] ?>" class="btn-action btn btn-outline-danger" title="Excluir" onclick="return confirm('Deseja excluir esta categoria? Isso pode afetar produtos vinculados.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if(empty($categorias)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>
                                    Nenhuma categoria cadastrada.
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