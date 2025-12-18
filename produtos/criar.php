<?php
require_once "../config/config.php";
require_once "../config/database.php";

$erro = "";
$sucesso = "";

// Buscar categorias ativas
$categorias = $pdo->query("SELECT * FROM categoriaproduto WHERE categoriasituacao=1 ORDER BY categoriadescricao ASC")->fetchAll();

if ($_POST) {
    $nome = $_POST['produtonome'];
    $categoria = $_POST['categoriaproduto'];
    $situacao = isset($_POST['produtosituacao']) ? 1 : 0;

    // Upload de imagem
    $imagem = null;
    if (!empty($_FILES['produtoimagem']['name'])) {
        $diretorio = "../uploads/";
        
        // Cria a pasta se não existir
        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['produtoimagem']['name'], PATHINFO_EXTENSION));
        $imagem = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['produtoimagem']['tmp_name'], $diretorio . $imagem);
    }

    $sql = $pdo->prepare("INSERT INTO produtos (produtonome, produtoimagem, produtosituacao, categoriaproduto) VALUES (?,?,?,?)");
    if ($sql->execute([$nome, $imagem, $situacao, $categoria])) {
        $sucesso = "Produto cadastrado com sucesso!";
    } else {
        $erro = "Erro ao cadastrar produto!";
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    body { background-color: #f8f9fc; }
    .card-form { border: none; border-radius: 15px; }
    .img-preview { 
        width: 100%; 
        max-width: 200px; 
        height: 200px; 
        object-fit: cover; 
        border-radius: 10px; 
        border: 2px dashed #d1d3e2;
        display: none;
        margin-top: 10px;
    }
    .section-title { 
        font-size: 0.85rem; 
        font-weight: 700; 
        text-transform: uppercase; 
        color: #4e73df; 
        letter-spacing: 1px;
        border-bottom: 2px solid #e3e6f0;
        padding-bottom: 5px;
        margin-bottom: 20px;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Novo Produto</h4>
            <p class="text-muted small mb-0">Adicione um novo item ao catálogo da Visa Vidros</p>
        </div>
        <a href="listar.php" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Voltar para Lista
        </a>
    </div>

    <?php if($erro): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i><?= $sucesso ?>
            <a href="listar.php" class="alert-link ms-2">Ver na lista</a>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card card-form shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="section-title">Informações Básicas</div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Nome do Produto</label>
                            <input type="text" name="produtonome" class="form-control form-control-lg" placeholder="Ex: Vidro Temperado 8mm Incolor" required autofocus>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-4">
                                <label class="form-label small fw-bold">Categoria</label>
                                <select name="categoriaproduto" class="form-select" required>
                                    <option value="">Selecione uma categoria...</option>
                                    <?php foreach($categorias as $c): ?>
                                        <option value="<?= $c['categoriacodigo'] ?>">
                                            <?= htmlspecialchars($c['categoriadescricao'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="form-label small fw-bold d-block">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="checkbox" name="produtosituacao" class="form-check-input" id="statusProd" checked>
                                    <label class="form-check-label small" for="statusProd">Produto Ativo</label>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-4 mt-2">
                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">
                                <i class="bi bi-save me-2"></i> Salvar Produto
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-form shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="section-title text-start">Imagem do Produto</div>
                        
                        <div class="mb-3">
                            <label for="imgInput" class="btn btn-outline-primary btn-sm w-100 py-3 border-dashed">
                                <i class="bi bi-cloud-arrow-up fs-4 d-block"></i>
                                Clique para selecionar foto
                            </label>
                            <input type="file" name="produtoimagem" id="imgInput" class="d-none" accept="image/*" onchange="previewImage(this)">
                        </div>

                        <div id="previewContainer">
                            <img id="preview" src="#" alt="Preview" class="img-preview shadow-sm mx-auto">
                            <p id="previewText" class="text-muted small mt-2">Nenhuma imagem selecionada</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    const text = document.getElementById('previewText');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            text.style.display = 'none';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once "../includes/footer.php"; ?>