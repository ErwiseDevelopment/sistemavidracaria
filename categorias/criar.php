<?php
require_once "../config/config.php";
require_once "../config/database.php";

$erro = "";
$sucesso = "";

if ($_POST) {
    $descricao = $_POST['categoriadescricao'];
    $situacao = isset($_POST['categoriasituacao']) ? 1 : 0;

    // Inserir no banco
    $sql = $pdo->prepare("INSERT INTO categoriaproduto (categoriadescricao, categoriasituacao) VALUES (?, ?)");
    if ($sql->execute([$descricao, $situacao])) {
        $sucesso = "Categoria criada com sucesso!";
    } else {
        $erro = "Erro ao criar categoria!";
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    body { background-color: #f8f9fc; }
    .card-form { border: none; border-radius: 15px; max-width: 600px; margin: 0 auto; }
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
    <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 600px; margin: 0 auto 1.5rem auto;">
        <div>
            <h4 class="fw-bold text-dark mb-0">Nova Categoria</h4>
            <p class="text-muted small mb-0">Defina um novo grupo para seus produtos</p>
        </div>
        <a href="listar.php" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Lista
        </a>
    </div>

    <?php if($erro): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4 mx-auto" style="max-width: 600px;"><?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 mx-auto" style="max-width: 600px;">
            <i class="bi bi-check-circle me-2"></i><?= $sucesso ?>
            <a href="listar.php" class="alert-link ms-2">Voltar para a lista</a>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="card card-form shadow-sm">
            <div class="card-body p-4">
                
                <div class="section-title">Informações da Categoria</div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Descrição da Categoria</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-tag text-muted"></i></span>
                        <input type="text" name="categoriadescricao" class="form-control" placeholder="Ex: Vidros Temperados, Ferragens..." required autofocus>
                    </div>
                    <div class="form-text">Use nomes claros para facilitar a organização no estoque.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold d-block">Status de Ativação</label>
                    <div class="form-check form-switch p-0 ms-0">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" name="categoriasituacao" class="form-check-input ms-0 me-2" style="width: 2.5em; height: 1.25em;" id="status" checked>
                            <label class="form-check-label small text-muted" for="status">Esta categoria ficará disponível para novos produtos</label>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                        <i class="bi bi-save me-2"></i> Criar Categoria
                    </button>
                    <a href="listar.php" class="btn btn-light px-4 border">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>