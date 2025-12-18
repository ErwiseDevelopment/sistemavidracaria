<?php
require_once "../config/config.php";
require_once "../config/database.php";

$id = $_GET['id'] ?? 0;

$sql = $pdo->prepare("SELECT * FROM produtos WHERE produtocodigo = ?");
$sql->execute([$id]);
$produto = $sql->fetch();

if (!$produto) {
    header("Location: listar.php");
    exit;
}

$erro = "";
$sucesso = "";

$categorias = $pdo->query("SELECT * FROM categoriaproduto WHERE categoriasituacao=1 ORDER BY categoriadescricao ASC")->fetchAll();

if ($_POST) {
    $nome = $_POST['produtonome'];
    $categoria = $_POST['categoriaproduto'];
    $situacao = isset($_POST['produtosituacao']) ? 1 : 0;

    // Upload de nova imagem
    if (!empty($_FILES['produtoimagem']['name'])) {
        $ext = strtolower(pathinfo($_FILES['produtoimagem']['name'], PATHINFO_EXTENSION));
        $imagem = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['produtoimagem']['tmp_name'], "../uploads/" . $imagem);
        
        // Opcional: deletar imagem antiga se desejar economizar espaço
        // if($produto['produtoimagem']) @unlink("../uploads/".$produto['produtoimagem']);
    } else {
        $imagem = $produto['produtoimagem'];
    }

    $sql = $pdo->prepare("UPDATE produtos SET produtonome=?, produtoimagem=?, produtosituacao=?, categoriaproduto=? WHERE produtocodigo=?");
    if ($sql->execute([$nome, $imagem, $situacao, $categoria, $id])) {
        $sucesso = "Produto atualizado com sucesso!";
        // Atualiza a variável para manter valores no form sem refresh
        $produto['produtonome'] = $nome;
        $produto['produtoimagem'] = $imagem;
        $produto['produtosituacao'] = $situacao;
        $produto['categoriaproduto'] = $categoria;
    } else {
        $erro = "Erro ao atualizar produto!";
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    body { background-color: #f8f9fc; }
    .card-form { border: none; border-radius: 15px; }
    .img-edit-preview { 
        width: 100%; 
        max-width: 250px; 
        height: 250px; 
        object-fit: cover; 
        border-radius: 12px; 
        border: 2px solid #e3e6f0;
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
            <h4 class="fw-bold text-dark mb-0">Editar Produto</h4>
            <p class="text-muted small mb-0">Editando: <?= htmlspecialchars($produto['produtonome'] ?? '') ?></p>
        </div>
        <a href="listar.php" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if($erro): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i><?= $sucesso ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card card-form shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="section-title">Informações Gerais</div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Nome do Produto</label>
                            <input type="text" name="produtonome" class="form-control" value="<?= htmlspecialchars($produto['produtonome'] ?? '') ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Categoria</label>
                            <select name="categoriaproduto" class="form-select" required>
                                <?php foreach($categorias as $c): ?>
                                    <option value="<?= $c['categoriacodigo'] ?>" <?= $produto['categoriaproduto'] == $c['categoriacodigo'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['categoriadescricao']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold d-block">Disponibilidade</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" name="produtosituacao" class="form-check-input" id="statusSwitch" <?= $produto['produtosituacao'] ? "checked" : "" ?>>
                                <label class="form-check-label small" for="statusSwitch">Produto Ativo no Catálogo</label>
                            </div>
                        </div>

                        <div class="border-top pt-4 mt-2">
                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">
                                <i class="bi bi-arrow-repeat me-2"></i> Atualizar Dados
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-form shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="section-title text-start">Imagem do Produto</div>
                        
                        <div class="mb-4">
                            <?php 
                                $caminhoImagem = ($produto['produtoimagem']) ? "../uploads/".$produto['produtoimagem'] : "../assets/img/no-image.png";
                            ?>
                            <img id="img-preview" src="<?= $caminhoImagem ?>" class="img-edit-preview shadow-sm mb-3" alt="Produto">
                        </div>

                        <div class="mb-3">
                            <label for="file-upload" class="btn btn-outline-dark btn-sm w-100 py-2">
                                <i class="bi bi-camera me-2"></i> Alterar Imagem
                            </label>
                            <input type="file" name="produtoimagem" id="file-upload" class="d-none" accept="image/*" onchange="readURL(this);">
                            <div class="form-text small mt-2 text-muted">Formatos aceitos: JPG, PNG. Recomendado: 800x800px</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('img-preview').setAttribute('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once "../includes/footer.php"; ?>