<?php
require_once "../config/config.php";
require_once "../config/database.php";

$id = $_GET['id'] ?? 0;

$sql = $pdo->prepare("SELECT * FROM clientes WHERE clientecodigo = ?");
$sql->execute([$id]);
$cliente = $sql->fetch();

if (!$cliente) {
    header("Location: listar.php");
    exit;
}

$erro = "";
$sucesso = "";

if ($_POST) {
    $nome       = $_POST['clientenomecompleto'];
    $whatsapp   = $_POST['clientewhatsapp'];
    $cep        = $_POST['clientecep'];
    $logradouro = $_POST['clientelogradouro'];
    $bairro     = $_POST['clientebairro'];
    $cidade     = $_POST['clientecidade'];
    $numero     = $_POST['clientenumero'];
    $complemento = $_POST['clientecpl'] ?? ''; // Adicionado campo complemento
    $obs        = $_POST['clienteobs'] ?? '';  // Adicionado campo observações
    $situacao   = isset($_POST['clientesituacao']) ? 1 : 0;

    $sql = $pdo->prepare("UPDATE clientes SET clientenomecompleto=?, clientewhatsapp=?, clientecep=?, clientelogradouro=?, clientebairro=?, clientecidade=?, clientenumero=?, clientecpl=?, clienteobs=?, clientesituacao=? WHERE clientecodigo=?");
    
    if ($sql->execute([$nome, $whatsapp, $cep, $logradouro, $bairro, $cidade, $numero, $complemento, $obs, $situacao, $id])) {
        $sucesso = "Cadastro atualizado com sucesso!";
        // Atualiza a variável cliente para refletir as mudanças na tela sem precisar de refresh
        $sql = $pdo->prepare("SELECT * FROM clientes WHERE clientecodigo = ?");
        $sql->execute([$id]);
        $cliente = $sql->fetch();
    } else {
        $erro = "Erro ao atualizar cliente!";
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    body { background-color: #f8f9fc; }
    .card-form { border: none; border-radius: 15px; }
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
            <h4 class="fw-bold text-dark mb-0">Editar Cliente</h4>
            <p class="text-muted small mb-0">Código do Cliente: #<?= $id ?></p>
        </div>
        <a href="listar.php" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Voltar para Lista
        </a>
    </div>

    <?php if($erro): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i><?= $sucesso ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="card card-form shadow-sm">
            <div class="card-body p-4">
                
                <div class section-title>Dados Principais</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Nome Completo</label>
                        <input type="text" name="clientenomecompleto" class="form-control" value="<?= htmlspecialchars($cliente['clientenomecompleto'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">WhatsApp / Telefone</label>
                        <input type="text" name="clientewhatsapp" class="form-control" value="<?= htmlspecialchars($cliente['clientewhatsapp'] ?? '') ?>" placeholder="(00) 00000-0000" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Situação</label>
                        <div class="form-check form-switch mt-2">
                            <input type="checkbox" name="clientesituacao" class="form-check-input" id="status" <?= $cliente['clientesituacao'] ? "checked" : "" ?>>
                            <label class="form-check-label small" for="status">Cliente Ativo</label>
                        </div>
                    </div>
                </div>

                <div class section-title>Endereço e Localização</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">CEP</label>
                        <div class="input-group">
                            <input type="text" name="clientecep" id="cep" class="form-control" value="<?= htmlspecialchars($cliente['clientecep'] ?? '') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="buscarCep()"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Logradouro (Rua/Av)</label>
                        <input type="text" name="clientelogradouro" id="logradouro" class="form-control" value="<?= htmlspecialchars($cliente['clientelogradouro'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Número</label>
                        <input type="text" name="clientenumero" class="form-control" value="<?= htmlspecialchars($cliente['clientenumero'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Complemento</label>
                        <input type="text" name="clientecpl" class="form-control" value="<?= htmlspecialchars($cliente['clientecpl'] ?? '') ?>" placeholder="Ex: Apto 12, Fundos...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Bairro</label>
                        <input type="text" name="clientebairro" id="bairro" class="form-control" value="<?= htmlspecialchars($cliente['clientebairro'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Cidade</label>
                        <input type="text" name="clientecidade" id="cidade" class="form-control" value="<?= htmlspecialchars($cliente['clientecidade'] ?? '') ?>">
                    </div>
                </div>

                <div class section-title>Informações Adicionais</div>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Observações Internas</label>
                        <textarea name="clienteobs" class="form-control" rows="3"><?= htmlspecialchars($cliente['clienteobs'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="border-top pt-4 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">
                        <i class="bi bi-save me-2"></i> Salvar Alterações
                    </button>
                    <a href="listar.php" class="btn btn-light px-4 border">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function buscarCep() {
    let cep = document.getElementById('cep').value.replace(/\D/g, '');
    if (cep.length !== 8) {
        alert("Digite um CEP válido com 8 dígitos.");
        return;
    }

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(dados => {
            if (!dados.erro) {
                document.getElementById('logradouro').value = dados.logradouro;
                document.getElementById('bairro').value = dados.bairro;
                document.getElementById('cidade').value = dados.localidade;
            } else {
                alert("CEP não encontrado.");
            }
        })
        .catch(error => console.error('Erro:', error));
}
</script>

<?php require_once "../includes/footer.php"; ?>