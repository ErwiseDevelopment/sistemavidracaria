<?php
// 1. INICIALIZAÇÃO E CONEXÃO (Sem espaços ou HTML antes desta tag)
require_once "../config/database.php";

$erro = "";
$sucesso = "";

$pedidoid = $_GET['id'] ?? null;
if(!$pedidoid){
    header("Location: listar.php");
    exit;
}

// -------------------------------------------------------------------------
// 2. LÓGICA DE SALVAMENTO (DEVE VIR ANTES DE QUALQUER HTML)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // ATUALIZA DADOS DO CLIENTE (Tratando campos que podem vir vazios)
        $sqlClie = $pdo->prepare("
            UPDATE clientes SET
                clientenomecompleto=?, clientewhatsapp=?, clientecep=?, 
                clientelogradouro=?, clientenumero=?, clientecpl=?, 
                clientebairro=?, clientecidade=?, clienteobs=?
            WHERE clientecodigo=?
        ");
        $sqlClie->execute([
            $_POST['clientenomecompleto'] ?? '', 
            $_POST['clientewhatsapp'] ?? '', 
            $_POST['clientecep'] ?? '',
            $_POST['clientelogradouro'] ?? '', 
            $_POST['clientenumero'] ?? '', 
            $_POST['clientecpl'] ?? '',
            $_POST['clientebairro'] ?? '', 
            $_POST['clientecidade'] ?? '', 
            $_POST['clienteobs'] ?? '',
            $_POST['clienteid']
        ]);

        // DELETA ITENS ANTIGOS
        $pdo->prepare("DELETE FROM pedidoitem WHERE pedidocodigo=?")->execute([$pedidoid]);

        // INSERE ITENS NOVOS
        $sqlItem = $pdo->prepare("
            INSERT INTO pedidoitem
            (pedidocodigo, pedidoitemseq, produtocodigo, produtodescricao, pedidoqnt, pedidovalor, pedidodesconto, pedidovalortotal)
            VALUES (?,?,?,?,?,?,?,?)
        ");

        $valor_total = 0;
        $seq = 1; 
        foreach($_POST['itens'] ?? [] as $i){
            if(empty($i['id'])) continue;

            $qnt = (float) $i['qnt'];
            $valor = (float) $i['valor'];
            $desconto = (float) $i['desconto'];
            $total_item = ($qnt * $valor) - $desconto;

            $sqlItem->execute([
                $pedidoid, $seq, $i['id'], $i['nome'], 
                $qnt, $valor, $desconto, $total_item
            ]);

            $valor_total += $total_item;
            $seq++;
        }

        // ATUALIZA DADOS DO PEDIDO
        $sqlPedido = $pdo->prepare("
            UPDATE pedido SET
                pedidoprevisaoentrega=?, pedidoformapagamento=?, 
                pedidosituacao=?, pedidototal=?
            WHERE pedidocodigo=?
        ");
        $sqlPedido->execute([
            $_POST['pedidoprevisaoentrega'], 
            $_POST['pedidoformapagamento'],
            $_POST['pedidosituacao'], 
            $valor_total, 
            $pedidoid
        ]);

        $pdo->commit();
        // Redireciona para evitar reenvio de formulário ao atualizar a página
        header("Location: editar.php?id=$pedidoid&sucesso=1");
        exit;

    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = "Erro ao salvar pedido: " . $e->getMessage();
    }
}

// -------------------------------------------------------------------------
// 3. BUSCA DE DADOS PARA PREENCHER O FORMULÁRIO
// -------------------------------------------------------------------------
$sql = $pdo->prepare("
    SELECT p.*, c.* FROM pedido p 
    JOIN clientes c ON p.clienteid = c.clientecodigo 
    WHERE p.pedidocodigo=?
");
$sql->execute([$pedidoid]);
$pedido = $sql->fetch(PDO::FETCH_ASSOC);

if(!$pedido){
    header("Location: listar.php");
    exit;
}

$sql = $pdo->prepare("SELECT * FROM pedidoitem WHERE pedidocodigo=? ORDER BY pedidoitemseq");
$sql->execute([$pedidoid]);
$itens = $sql->fetchAll(PDO::FETCH_ASSOC);

$produtos = $pdo->query("SELECT * FROM produtos ORDER BY produtonome")->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------------------
// 4. INCLUSÃO DO VISUAL (SÓ COMEÇA AQUI O HTML)
// -------------------------------------------------------------------------
require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<div class="container my-4">
    <?php if(isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
            <i class="bi bi-check-circle-fill me-2"></i> Pedido atualizado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $erro ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post" id="formPedido" class="needs-validation" novalidate>
        <input type="hidden" name="clienteid" value="<?= $pedido['clienteid'] ?>">
        <input type="hidden" name="pedidocodigo" value="<?= $pedidoid ?>">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h4 class="mb-0 fw-bold text-dark">Pedido #<?= $pedidoid ?></h4>
                    </div>
                    <div class="col-md-4 text-md-center">
                        <label class="small fw-bold text-muted d-block text-uppercase">Total do Pedido</label>
                        <input type="text" id="valor_total_topo" class="form-control form-control-lg text-center fw-bold border-primary text-primary bg-light" 
                               value="R$ <?= number_format($pedido['pedidototal'], 2, ',', '.') ?>" readonly>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <div class="btn-group shadow-sm">
                            <a href="imprimir.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-warning fw-bold">
                                <i class="bi bi-printer"></i> Imprimir
                            </a>
                            <a href="desenho.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-info text-white fw-bold">
                                <i class="bi bi-pencil-ruler"></i> Desenho
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                   <div class="col-md-4">
                        <label class="form-label fw-bold">Data/Hora da Instalação</label>
                        <input type="datetime-local" name="pedidoprevisaoentrega" class="form-control" 
                            value="<?= date('Y-m-d\TH:i', strtotime($pedido['pedidoprevisaoentrega'] ?? 'now')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Forma de Pagamento</label>
                        <select name="pedidoformapagamento" class="form-select">
                            <?php foreach(['Débito','Crédito','PIX','Dinheiro','Outros'] as $f): ?>
                                <option <?= ($pedido['pedidoformapagamento'] ?? '') == $f ? 'selected' : '' ?>><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Situação</label>
                        <select name="pedidosituacao" class="form-select fw-bold text-primary">
                            <?php foreach(['Criado','Produção','Instalação','Finalizado','Cancelado'] as $s): ?>
                                <option <?= ($pedido['pedidosituacao'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h6 class="text-primary fw-bold text-uppercase small border-bottom pb-2 mb-3">Informações do Cliente</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label class="small fw-bold">Nome do Cliente</label>
                        <input name="clientenomecompleto" class="form-control" value="<?= htmlspecialchars($pedido['clientenomecompleto']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">WhatsApp</label>
                        <input name="clientewhatsapp" class="form-control" value="<?= htmlspecialchars($pedido['clientewhatsapp']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">CEP</label>
                        <input name="clientecep" class="form-control" value="<?= htmlspecialchars($pedido['clientecep']) ?>">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="small fw-bold">Logradouro</label>
                        <input name="clientelogradouro" class="form-control" value="<?= htmlspecialchars($pedido['clientelogradouro']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Nº</label>
                        <input name="clientenumero" class="form-control" value="<?= htmlspecialchars($pedido['clientenumero']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Cidade</label>
                        <input name="clientecidade" class="form-control" value="<?= htmlspecialchars($pedido['clientecidade'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Bairro</label>
                        <input name="clientebairro" class="form-control" value="<?= htmlspecialchars($pedido['clientebairro']) ?>">
                    </div>
                     <div class="col-md-4">
    <label class="small fw-bold">Complemento</label>
    <input name="clientecpl" class="form-control" 
           value="<?= htmlspecialchars($pedido['clientecpl'] ?? '') ?>">
</div>
                </div>
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="small fw-bold text-danger">Observações Internas / Detalhes de Instalação</label>
                        <textarea name="clienteobs" class="form-control" rows="2" placeholder="Ex: Vidro temperado 8mm incolor..."><?= htmlspecialchars($pedido['clienteobs'] ?? '') ?></textarea>
                    </div>
                </div>

                <h6 class="text-primary fw-bold text-uppercase small border-bottom pb-2 mb-3">Itens do Pedido</h6>
                <div class="table-responsive">
                    <table class="table table-hover border align-middle" id="tabelaItens">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th width="100">Qtd</th>
                                <th width="140">Vlr. Unitário</th>
                                <th width="140">Desconto</th>
                                <th width="140" class="text-end">Subtotal</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($itens as $k => $i): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($i['produtodescricao']) ?></span>
                                    <input type="hidden" name="itens[<?= $k ?>][id]" value="<?= $i['produtocodigo'] ?>">
                                    <input type="hidden" name="itens[<?= $k ?>][nome]" value="<?= htmlspecialchars($i['produtodescricao']) ?>">
                                </td>
                                <td><input type="number" name="itens[<?= $k ?>][qnt]" class="form-control qtd" value="<?= $i['pedidoqnt'] ?>" min="1"></td>
                                <td><input type="number" name="itens[<?= $k ?>][valor]" class="form-control valor" value="<?= $i['pedidovalor'] ?>" step="0.01"></td>
                                <td><input type="number" name="itens[<?= $k ?>][desconto]" class="form-control desconto" value="<?= $i['pedidodesconto'] ?>" step="0.01"></td>
                                <td class="text-end fw-bold">R$ <span class="totalItem"><?= number_format($i['pedidovalortotal'], 2, ',', '.') ?></span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remover"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php include "../orcamentos/modal_produtos.php"; ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalProdutos">
                        <i class="bi bi-plus-circle me-1"></i> Adicionar Produto
                    </button>
                    <div>
                        <a href="listar.php" class="btn btn-light border px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                            <i class="bi bi-save me-1"></i> SALVAR TUDO
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Contador de itens baseado no que já existe na tabela
let indexItens = <?= count($itens) ?>;

// ESCUTADOR PARA O MODAL (Integrando com a classe selecionarProduto do seu modal)
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('selecionarProduto')) {
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        const nome = tr.dataset.nome;
        
        if (id && nome) {
            addProdutoManual(id, nome);
        }
    }
});

function addProdutoManual(id, nome) {
    const tbody = document.querySelector('#tabelaItens tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <span class="fw-bold">${nome}</span>
            <input type="hidden" name="itens[${indexItens}][id]" value="${id}">
            <input type="hidden" name="itens[${indexItens}][nome]" value="${nome}">
        </td>
        <td><input type="number" name="itens[${indexItens}][qnt]" class="form-control qtd" value="1" min="1"></td>
        <td><input type="number" name="itens[${indexItens}][valor]" class="form-control valor" value="0.00" step="0.01"></td>
        <td><input type="number" name="itens[${indexItens}][desconto]" class="form-control desconto" value="0.00" step="0.01"></td>
        <td class="text-end fw-bold">R$ <span class="totalItem">0,00</span></td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm remover"><i class="bi bi-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
    indexItens++;
    atualizarTotais();
    
    // Fecha o modal (seletor padrão do Bootstrap)
    const modalElem = document.getElementById('modalProdutos');
    const modal = bootstrap.Modal.getInstance(modalElem);
    if(modal) modal.hide();
}

function atualizarTotais() {
    let totalGeral = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const q = parseFloat(tr.querySelector('.qtd').value) || 0;
        const v = parseFloat(tr.querySelector('.valor').value) || 0;
        const d = parseFloat(tr.querySelector('.desconto').value) || 0;
        const t = (q * v) - d;
        
        const spanTotal = tr.querySelector('.totalItem');
        if(spanTotal) spanTotal.innerText = t.toLocaleString('pt-br', {minimumFractionDigits: 2});
        totalGeral += t;
    });
    
    const campoTotal = document.getElementById('valor_total_topo');
    if(campoTotal) {
        campoTotal.value = totalGeral.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    }
}

// Eventos para recalcular ao digitar
document.addEventListener('input', e => {
    if(e.target.matches('.qtd, .valor, .desconto')) atualizarTotais();
});

// Evento para remover item
document.addEventListener('click', e => {
    const btnRemover = e.target.closest('.remover');
    if(btnRemover){
        btnRemover.closest('tr').remove();
        atualizarTotais();
    }
});

// Inicializa os cálculos ao carregar a página
window.onload = atualizarTotais;
</script>