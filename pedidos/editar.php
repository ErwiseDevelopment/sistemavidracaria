<?php
// 1. INICIALIZAÇÃO E CONEXÃO
require_once "../config/database.php";

$erro = "";
$sucesso = "";

$pedidoid = $_GET['id'] ?? null;
if(!$pedidoid){
    header("Location: listar.php");
    exit;
}

// -------------------------------------------------------------------------
// 2. LÓGICA DE SALVAMENTO
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // ATUALIZA DADOS DO CLIENTE
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

        // DELETA ITENS ANTIGOS PARA REINSERIR
        $pdo->prepare("DELETE FROM pedidoitem WHERE pedidocodigo=?")->execute([$pedidoid]);

        $sqlItem = $pdo->prepare("
            INSERT INTO pedidoitem
            (pedidocodigo, pedidoitemseq, produtocodigo, produtodescricao, pedidoqnt, pedidovalor, pedidodesconto, pedidovalortotal)
            VALUES (?,?,?,?,?,?,?,?)
        ");

        $totalBruto = 0;
        $seq = 1; 
        foreach($_POST['itens'] ?? [] as $i){
            if(empty($i['id'])) continue;

            $valor = (float) $i['valor'];
            // Seguindo o padrão: Qtd sempre 1 e Desconto Item sempre 0
            $sqlItem->execute([
                $pedidoid, $seq, $i['id'], $i['nome'], 
                1, $valor, 0, $valor
            ]);

            $totalBruto += $valor;
            $seq++;
        }

        // ATUALIZA DADOS DO PEDIDO (Incluindo o Desconto Global)
        $descontoGlobal = (float) ($_POST['pedidovlrdesconto'] ?? 0);
        $totalLiquido = $totalBruto - $descontoGlobal;

        $sqlPedido = $pdo->prepare("
            UPDATE pedido SET
                pedidoprevisaoentrega=?, pedidoformapagamento=?, 
                pedidosituacao=?, pedidototal=?, pedidovlrdesconto=?
            WHERE pedidocodigo=?
        ");
        $sqlPedido->execute([
            $_POST['pedidoprevisaoentrega'], 
            $_POST['pedidoformapagamento'],
            $_POST['pedidosituacao'], 
            $totalLiquido,
            $descontoGlobal,
            $pedidoid
        ]);

        $pdo->commit();
        header("Location: editar.php?id=$pedidoid&sucesso=1");
        exit;

    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = "Erro ao salvar pedido: " . $e->getMessage();
    }
}

// -------------------------------------------------------------------------
// 3. BUSCA DE DADOS
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

    <form method="post" id="formPedido">
        <input type="hidden" name="clienteid" value="<?= $pedido['clienteid'] ?>">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center g-3">
                    <div class="col-md-4 col-12 text-center text-md-start">
                        <h4 class="mb-0 fw-bold text-dark">Pedido #<?= $pedidoid ?></h4>
                    </div>
                    <div class="col-md-4 col-12 text-center">
                        <label class="small fw-bold text-muted d-block text-uppercase">Total Líquido</label>
                        <input type="text" id="valor_total_topo" class="form-control form-control-lg text-center fw-bold border-primary text-primary bg-light" 
                               value="R$ <?= number_format($pedido['pedidototal'], 2, ',', '.') ?>" readonly>
                    </div>
                    <div class="col-md-4 col-12 text-center text-md-end">
                        <div class="btn-group shadow-sm">
                            <a href="imprimir.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-warning fw-bold">Imprimir</a>
                            <a href="desenho.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-info fw-bold">Folha desenho</a>
                            <a href="listar.php" class="btn btn-outline-secondary fw-bold">Voltar</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3 mb-4 bg-light p-3 rounded border">
                   <div class="col-md-4">
                        <label class="form-label fw-bold small">Data/Hora da Instalação</label>
                        <input type="datetime-local" name="pedidoprevisaoentrega" class="form-control" 
                            value="<?= date('Y-m-d\TH:i', strtotime($pedido['pedidoprevisaoentrega'] ?? 'now')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Forma de Pagamento</label>
                        <select name="pedidoformapagamento" class="form-select">
                            <?php foreach(['PIX','Dinheiro','Crédito','Débito','Parcelado','Outros'] as $f): ?>
                                <option <?= ($pedido['pedidoformapagamento'] ?? '') == $f ? 'selected' : '' ?>><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Situação</label>
                        <select name="pedidosituacao" class="form-select fw-bold text-primary border-primary">
                            <?php foreach(['Criado','Produção','Instalação','Finalizado','Cancelado'] as $s): ?>
                                <option <?= ($pedido['pedidosituacao'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h6 class="text-primary fw-bold text-uppercase small border-bottom pb-2 mb-3">Informações do Cliente</h6>
                <div class="row g-2 mb-2">
                    <div class="col-md-6 col-12">
                        <label class="small fw-bold">Nome</label>
                        <input name="clientenomecompleto" class="form-control" value="<?= htmlspecialchars($pedido['clientenomecompleto']) ?>" required>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="small fw-bold">WhatsApp</label>
                        <input name="clientewhatsapp" class="form-control" value="<?= htmlspecialchars($pedido['clientewhatsapp']) ?>">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="small fw-bold">CEP</label>
                        <input name="clientecep" class="form-control" value="<?= htmlspecialchars($pedido['clientecep']) ?>">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-5 col-12">
                        <label class="small fw-bold">Logradouro</label>
                        <input name="clientelogradouro" class="form-control" value="<?= htmlspecialchars($pedido['clientelogradouro']) ?>">
                    </div>
                    <div class="col-md-2 col-4">
                        <label class="small fw-bold">Nº</label>
                        <input name="clientenumero" class="form-control" value="<?= htmlspecialchars($pedido['clientenumero']) ?>">
                    </div>
                    <div class="col-md-2 col-8">
                        <label class="small fw-bold">Cidade</label>
                        <input name="clientecidade" class="form-control" value="<?= htmlspecialchars($pedido['clientecidade'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="small fw-bold">Bairro</label>
                        <input name="clientebairro" class="form-control" value="<?= htmlspecialchars($pedido['clientebairro']) ?>">
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="small fw-bold text-danger">Observações Internas</label>
                        <textarea name="clienteobs" class="form-control" rows="2"><?= htmlspecialchars($pedido['clienteobs'] ?? '') ?></textarea>
                        <input type="hidden" name="clientecpl" value="<?= htmlspecialchars($pedido['clientecpl'] ?? '') ?>">
                    </div>
                </div>

                <h6 class="text-primary fw-bold text-uppercase small border-bottom pb-2 mb-3">Itens do Pedido</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-hover border align-middle" id="tabelaItens">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Produto</th>
                                <th width="200">Valor Unitário</th>
                                <th width="150" class="text-end">Subtotal</th>
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
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" name="itens[<?= $k ?>][valor]" class="form-control valor" value="<?= $i['pedidovalor'] ?>" step="0.01">
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-primary">R$ <span class="totalItem"><?= number_format($i['pedidovalortotal'], 2, ',', '.') ?></span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remover"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card bg-light border-0 p-3 shadow-sm">
                            <div class="mb-3">
                                <label class="small fw-bold text-danger text-uppercase">Desconto no Total (R$)</label>
                                <input type="number" name="pedidovlrdesconto" id="pedidovlrdesconto" class="form-control form-control-lg border-danger fw-bold" value="<?= $pedido['pedidovlrdesconto'] ?>" step="0.01">
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted fw-bold text-uppercase">Soma dos Itens:</span>
                                <span id="label_bruto" class="fw-bold text-dark">R$ 0,00</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 fw-bold mb-0 text-uppercase">Total Líquido:</span>
                                <span id="label_liquido" class="h5 fw-bold mb-0 text-primary">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3">
                    <button type="button" class="btn btn-dark fw-bold w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalProdutos">
                        <i class="bi bi-plus-circle me-1"></i> ADICIONAR PRODUTO
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow w-100 w-md-auto fw-bold">
                        <i class="bi bi-check-lg me-1"></i> SALVAR ALTERAÇÕES
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include "../orcamentos/modal_produtos.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let indexItens = <?= count($itens) ?>;

function atualizarTotais() {
    let totalBruto = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const v = parseFloat(tr.querySelector('.valor').value) || 0;
        // Qtd é sempre 1
        tr.querySelector('.totalItem').innerText = v.toLocaleString('pt-br', {minimumFractionDigits: 2});
        totalBruto += v;
    });
    
    const desconto = parseFloat(document.getElementById('pedidovlrdesconto').value) || 0;
    const totalLiquido = totalBruto - desconto;

    document.getElementById('label_bruto').innerText = totalBruto.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('label_liquido').innerText = totalLiquido.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    
    const campoTotalTopo = document.getElementById('valor_total_topo');
    if(campoTotalTopo) campoTotalTopo.value = totalLiquido.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

// Escuta cliques no modal de produtos
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('selecionarProduto')) {
        const trModal = e.target.closest('tr');
        const id = trModal.dataset.id;
        const nome = trModal.dataset.nome;
        const valor = parseFloat(trModal.dataset.valor || 0);

        const tbody = document.querySelector('#tabelaItens tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <span class="fw-bold d-block text-dark">${nome}</span>
                <input type="hidden" name="itens[${indexItens}][id]" value="${id}">
                <input type="hidden" name="itens[${indexItens}][nome]" value="${nome}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">R$</span>
                    <input type="number" name="itens[${indexItens}][valor]" class="form-control valor" value="${valor.toFixed(2)}" step="0.01">
                </div>
            </td>
            <td class="text-end fw-bold text-primary">R$ <span class="totalItem">${valor.toLocaleString('pt-br',{minimumFractionDigits:2})}</span></td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm remover"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tbody.appendChild(row);
        indexItens++;
        atualizarTotais();
        
        const m = document.getElementById('modalProdutos');
        bootstrap.Modal.getInstance(m).hide();
    }
});

document.addEventListener('input', e => {
    if(e.target.matches('.valor, #pedidovlrdesconto')) atualizarTotais();
});

document.addEventListener('click', e => {
    const btn = e.target.closest('.remover');
    if(btn){
        btn.closest('tr').remove();
        atualizarTotais();
    }
});

window.onload = atualizarTotais;
</script>