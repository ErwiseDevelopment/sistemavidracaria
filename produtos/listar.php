<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../includes/header.php";
require_once "../includes/menu.php";

// Pegar filtros da URL
$filtro_nome = $_GET['busca'] ?? '';
$filtro_cat  = $_GET['categoria'] ?? '';

// Construir consulta com filtros
$query = "SELECT p.*, c.categoriadescricao 
          FROM produtos p 
          LEFT JOIN categoriaproduto c ON p.categoriaproduto = c.categoriacodigo 
          WHERE 1=1";

$params = [];
if ($filtro_nome) {
    $query .= " AND p.produtonome LIKE ?";
    $params[] = "%$filtro_nome%";
}
if ($filtro_cat) {
    $query .= " AND p.categoriaproduto = ?";
    $params[] = $filtro_cat;
}

$query .= " ORDER BY p.produtocodigo DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Buscar categorias para o select do filtro
$categorias = $pdo->query("SELECT * FROM categoriaproduto WHERE categoriasituacao=1 ORDER BY categoriadescricao ASC")->fetchAll();
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
    }
    .img-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e3e6f0;
    }
    .badge-cat {
        background-color: #eef2f7;
        color: #4e73df;
        font-weight: 600;
        font-size: 0.75rem;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Catálogo de Produtos</h4>
            <p class="text-muted small mb-0">Gerencie os itens e materiais da Visa Vidros</p>
        </div>
        <a href="criar.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-2"></i>Novo Produto
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="busca" class="form-control border-start-0 ps-0" placeholder="Buscar por nome..." value="<?= htmlspecialchars($filtro_nome) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="categoria" class="form-select">
                        <option value="">Todas as Categorias</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['categoriacodigo'] ?>" <?= $filtro_cat == $cat['categoriacodigo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['categoriadescricao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">Filtrar</button>
                    <?php if($filtro_nome || $filtro_cat): ?>
                        <a href="listar.php" class="btn btn-outline-secondary" title="Limpar Filtros"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-table shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" width="80">ID</th>
                            <th width="70">Foto</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr>
                                <td class="ps-4 text-muted small">#<?= $p['produtocodigo'] ?></td>
                                <td>
                                    <?php if($p['produtoimagem']): ?>
                                        <img src="../uploads/<?= $p['produtoimagem'] ?>" class="img-thumb" alt="Produto">
                                    <?php else: ?>
                                        <div class="img-thumb d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="bi bi-image small"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['produtonome'] ?? '') ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-cat px-2 py-1">
                                        <?= htmlspecialchars($p['categoriadescricao'] ?? 'Sem Categoria') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($p['produtosituacao']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-3">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="editar.php?id=<?= $p['produtocodigo'] ?>" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if(empty($produtos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                                    Nenhum produto encontrado com esses filtros.
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