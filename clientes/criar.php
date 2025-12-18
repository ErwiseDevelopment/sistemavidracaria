<?php
require_once "../config/config.php";
require_once "../config/database.php";

$erro = "";
$sucesso = "";

if ($_POST) {
    $nome        = $_POST['clientenomecompleto'];
    $whatsapp    = $_POST['clientewhatsapp'];
    $cep         = $_POST['clientecep'];
    $logradouro  = $_POST['clientelogradouro'];
    $bairro      = $_POST['clientebairro'];
    $cidade      = $_POST['clientecidade'];
    $numero      = $_POST['clientenumero'];
    $complemento = $_POST['clientecpl'] ?? '';
    $obs         = $_POST['clienteobs'] ?? '';
    $situacao    = isset($_POST['clientesituacao']) ? 1 : 0;

    $sql = $pdo->prepare("INSERT INTO clientes (clientenomecompleto, clientewhatsapp, clientecep, clientelogradouro, clientebairro, clientecidade, clientenumero, clientecpl, clienteobs, clientesituacao) VALUES (?,?,?,?,?,?,?,?,?,?)");
    
    if ($sql->execute([$nome, $whatsapp, $cep, $logradouro, $bairro, $cidade, $numero, $complemento, $obs, $situacao])) {
        $sucesso = "Cliente cadastrado com sucesso!";
        // Limpa os campos após o sucesso para um novo cadastro, se desejar
        $_POST = array(); 
    } else {
        $erro = "Erro ao cadastrar cliente!";
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
            <h4 class="fw-bold text-dark mb-0">Novo Cliente</h4>
            <p class="text-muted small mb-0">Cadastre um novo cliente na base da Visa Vidros</p>
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
            <a href="listar.php" class="alert-link ms-2">Ir para a lista</a>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="card card-form shadow-sm">
            <div class="card-body p-4">
                
                <div class="section-title">Dados Principais</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Nome Completo</label>
                        <input type="text" name="clientenomecompleto" class="form-control" placeholder="Nome do cliente" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">WhatsApp / Telefone</label>
                        <input type="text" name="clientewhatsapp" class="form-control" placeholder="(00) 00000-0000" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Situação</label>
                        <div class="form-check form-switch mt-2">
                            <input type="checkbox" name="clientesituacao" class="form-check-input" id="status" checked>
                            <label class="form-check-label small" for="status">Ativo</label>
                        </div>
                    </div>
                </div>

                <div class="section-title">Endereço e Localização</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">CEP</label>
                        <div class="input-group">
                            <input type="text" name="clientecep" id="cep" class="form-control" placeholder="00000-000">
                            <button class="btn btn-outline-primary" type="button" onclick="buscarCep()"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Logradouro (Rua/Av)</label>
                        <input type="text" name="clientelogradouro" id="logradouro" class="form-control" placeholder="Rua, Avenida...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Número</label>
                        <input type="text" name="clientenumero" class="form-control" placeholder="123">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Complemento</label>
                        <input type="text" name="clientecpl" class="form-control" placeholder="Ex: Apto 12, Fundos...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Bairro</label>
                        <input type="text" name="clientebairro" id="bairro" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Cidade</label>
                        <input type="text" name="clientecidade" id="cidade" class="form-control">
                    </div>
                </div>

                <div class="section-title">Informações Adicionais</div>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Observações Internas</label>
                        <textarea name="clienteobs" class="form-control" rows="3" placeholder="Informações relevantes sobre o cliente..."></textarea>
                    </div>
                </div>

                <div class="border-top pt-4 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm">
                        <i class="bi bi-person-check me-2"></i> Cadastrar Cliente
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
                // Move o foco para o número automaticamente
                document.getElementsByName('clientenumero')[0].focus();
            } else {
                alert("CEP não encontrado.");
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert("Erro ao buscar o CEP.");
        });
}
</script>

<?php require_once "../includes/footer.php"; ?>