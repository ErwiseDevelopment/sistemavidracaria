<?php
// 1. INICIALIZAÇÃO E CONEXÃO
require_once "../config/database.php";
require_once "../config/config.php";

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
            $sqlItem->execute([$pedidoid, $seq, $i['id'], $i['nome'], 1, $valor, 0, $valor]);
            $totalBruto += $valor;
            $seq++;
        }

        $descontoGlobal = (float) ($_POST['pedidovlrdesconto'] ?? 0);
        $totalLiquido = $totalBruto - $descontoGlobal;

        $sqlPedido = $pdo->prepare("
            UPDATE pedido SET
                pedidoprevisaoentrega=?, pedidoformapagamento=?, 
                pedidosituacao=?, pedidototal=?, pedidovlrdesconto=?
            WHERE pedidocodigo=?
        ");
        $sqlPedido->execute([
            $_POST['pedidoprevisaoentrega'], $_POST['pedidoformapagamento'],
            $_POST['pedidosituacao'], $totalLiquido, $descontoGlobal, $pedidoid
        ]);

        $pdo->commit();
        header("Location: editar.php?id=$pedidoid&sucesso=1");
        exit;

    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = "Erro ao salvar pedido: " . $e->getMessage();
    }
}

// 3. BUSCA DE DADOS
$sql = $pdo->prepare("SELECT p.*, c.* FROM pedido p JOIN clientes c ON p.clienteid = c.clientecodigo WHERE p.pedidocodigo=?");
$sql->execute([$pedidoid]);
$pedido = $sql->fetch(PDO::FETCH_ASSOC);

if(!$pedido) { header("Location: listar.php"); exit; }

$sql = $pdo->prepare("SELECT * FROM pedidoitem WHERE pedidocodigo=? ORDER BY pedidoitemseq");
$sql->execute([$pedidoid]);
$itens = $sql->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
require_once "../includes/menu.php";

// Limpa o número do WhatsApp para o link
$whatsLink = preg_replace('/\D/', '', $pedido['clientewhatsapp']);
?>

<style>
    :root { --primary: #4361ee; --bg-light: #f8f9fd; }
    body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; }
    .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .status-header { background: white; padding: 1rem; border-bottom: 1px solid #edf2f7; margin-bottom: 1.5rem; }
    .total-display { background: #f0f3ff; color: var(--primary); border: 2px solid var(--primary); font-size: 1.5rem; font-weight: 800; border-radius: 12px; padding: 8px; width: 100%; max-width: 250px; }
    .section-title { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
</style>

<div class="status-header shadow-sm sticky-top">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-0">Editar Pedido #<?= $pedidoid ?></h5>
            <span class="badge bg-primary rounded-pill"><?= $pedido['pedidosituacao'] ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="valor_total_topo" class="total-display text-center" value="R$ 0,00" readonly>
            <div class="btn-group">
                <a href="imprimir.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-warning fw-bold"><i class="bi bi-printer"></i></a>
                <a href="desenho.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-dark fw-bold text-nowrap">Folha Desenho</a>
                <a href="https://wa.me/55<?= $whatsLink ?>" target="_blank" class="btn btn-success fw-bold"><i class="bi bi-whatsapp"></i></a>
            </div>
            <a href="listar.php" class="btn btn-light rounded-pill border px-3">Sair</a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if($erro): ?><div class="alert alert-danger border-0 shadow-sm"><?= $erro ?></div><?php endif; ?>
    <?php if(isset($_GET['sucesso'])): ?><div class="alert alert-success border-0 shadow-sm">Pedido atualizado com sucesso!</div><?php endif; ?>

    <form method="post" id="formPedido">
        <input type="hidden" name="clienteid" value="<?= $pedido['clienteid'] ?>">
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-custom p-4">
                    <div class="section-title"><i class="bi bi-info-circle-fill"></i> Detalhes da Entrega e Status</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">Previsão de Instalação</label>
                            <input type="datetime-local" name="pedidoprevisaoentrega" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($pedido['pedidoprevisaoentrega'])) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Forma de Pagamento</label>
                            <select name="pedidoformapagamento" class="form-select">
                                <?php foreach(['PIX','Dinheiro','Cartão de Crédito','Cartão de Débito','Boleto'] as $f): ?>
                                    <option <?= $pedido['pedidoformapagamento'] == $f ? 'selected' : '' ?>><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Situação do Pedido</label>
                            <select name="pedidosituacao" class="form-select fw-bold border-primary">
                                <?php foreach(['Criado','Produção','Instalação','Finalizado','Cancelado'] as $s): ?>
                                    <option <?= $pedido['pedidosituacao'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <div class="section-title"><i class="bi bi-person-fill"></i> Dados do Cliente</div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="small fw-bold">WhatsApp</label>
                            <input type="text" name="clientewhatsapp" class="form-control" value="<?= htmlspecialchars($pedido['clientewhatsapp']) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="small fw-bold">Nome Completo</label>
                            <input type="text" name="clientenomecompleto" class="form-control" value="<?= htmlspecialchars($pedido['clientenomecompleto']) ?>" required>
                        </div>
                        <div class="col-md-3 col-6"><label class="small fw-bold">CEP</label><input type="text" name="clientecep" class="form-control" value="<?= $pedido['clientecep'] ?>"></div>
                        <div class="col-md-7 col-6"><label class="small fw-bold">Logradouro</label><input type="text" name="clientelogradouro" class="form-control" value="<?= $pedido['clientelogradouro'] ?>"></div>
                        <div class="col-md-2"><label class="small fw-bold">Nº</label><input type="text" name="clientenumero" class="form-control" value="<?= $pedido['clientenumero'] ?>"></div>
                        <div class="col-md-4"><label class="small fw-bold">Bairro</label><input type="text" name="clientebairro" class="form-control" value="<?= $pedido['clientebairro'] ?>"></div>
                        <div class="col-md-4"><label class="small fw-bold">Cidade</label><input type="text" name="clientecidade" class="form-control" value="<?= $pedido['clientecidade'] ?>"></div>
                        <div class="col-md-4"><label class="small fw-bold">Complemento</label><input type="text" name="clientecpl" class="form-control" value="<?= $pedido['clientecpl'] ?>"></div>
                        <div class="col-12"><label class="small fw-bold">Observações</label><textarea name="clienteobs" class="form-control" rows="1"><?= htmlspecialchars($pedido['clienteobs']) ?></textarea></div>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0"><i class="bi bi-cart-fill"></i> Itens do Pedido</div>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalProdutos">+ Adicionar Produto</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="tabelaItens">
                            <thead><tr class="small text-muted"><th>Produto</th><th width="150">Valor Unit.</th><th width="120" class="text-end">Total</th><th width="50"></th></tr></thead>
                            <tbody>
                                <?php foreach($itens as $k => $i): ?>
                                <tr data-index="<?= $k ?>">
                                    <td>
                                        <span class="fw-bold"><?= htmlspecialchars($i['produtodescricao']) ?></span>
                                        <input type="hidden" name="itens[<?= $k ?>][id]" value="<?= $i['produtocodigo'] ?>">
                                        <input type="hidden" name="itens[<?= $k ?>][nome]" value="<?= htmlspecialchars($i['produtodescricao']) ?>">
                                    </td>
                                    <td><input class="form-control form-control-sm valor" name="itens[<?= $k ?>][valor]" value="<?= $i['pedidovalor'] ?>" step="0.01" type="number"></td>
                                    <td class="text-end fw-bold">R$ <span class="total"><?= number_format($i['pedidovalortotal'], 2, ',', '.') ?></span></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm text-danger remover"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4 sticky-top" style="top: 110px;">
                    <div class="section-title">Resumo Financeiro</div>
                    <div class="mb-3">
                        <label class="small fw-bold text-danger">DESCONTO (R$)</label>
                        <input type="number" name="pedidovlrdesconto" id="pedidovlrdesconto" class="form-control form-control-lg border-danger fw-bold text-danger" value="<?= $pedido['pedidovlrdesconto'] ?>" step="0.01">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-muted">VALOR FINAL:</span>
                        <span id="label_liquido" class="h3 fw-bold text-primary mb-0">R$ 0,00</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-lg fw-bold">ATUALIZAR PEDIDO</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include "../orcamentos/modal_produtos.php"; ?>

<script>
let index = <?= count($itens) ?>;

function fecharModais() {
    document.querySelectorAll('.modal.show').forEach(m => {
        const inst = bootstrap.Modal.getInstance(m);
        if (inst) inst.hide();
    });
    setTimeout(() => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style = "";
    }, 150);
}

function atualizarTotais(){
    let total = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const v = parseFloat(tr.querySelector('.valor').value) || 0;
        tr.querySelector('.total').innerText = v.toLocaleString('pt-br', {minimumFractionDigits: 2});
        total += v;
    });
    const desc = parseFloat(document.getElementById('pedidovlrdesconto').value) || 0;
    const liq = total - desc;
    document.getElementById('label_liquido').innerText = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('valor_total_topo').value = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('selecionarProduto')) {
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        const nome = tr.dataset.nome;
        const valor = parseFloat(tr.dataset.valor || 0);

        const tbody = document.querySelector('#tabelaItens tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><span class="fw-bold">${nome}</span><input type="hidden" name="itens[${index}][id]" value="${id}"><input type="hidden" name="itens[${index}][nome]" value="${nome}"></td>
            <td><input class="form-control form-control-sm valor" name="itens[${index}][valor]" value="${valor.toFixed(2)}" step="0.01" type="number"></td>
            <td class="text-end fw-bold">R$ <span class="total">${valor.toLocaleString('pt-br', {minimumFractionDigits:2})}</span></td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger remover"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        index++;
        atualizarTotais();
        fecharModais();
    }
    
    if (e.target.closest('.remover')) {
        e.target.closest('tr').remove();
        atualizarTotais();
    }
});

document.addEventListener('input', e => { if(e.target.matches('.valor, #pedidovlrdesconto')) atualizarTotais(); });

window.onload = atualizarTotais;
</script>

<?php include "../includes/footer.php"; ?>