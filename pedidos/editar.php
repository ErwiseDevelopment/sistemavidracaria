<?php
require_once "../config/database.php";
require_once "../config/config.php";

$erro = "";
$sucesso = "";

$pedidoid = $_GET['id'] ?? null;
if(!$pedidoid){ header("Location: listar.php"); exit; }

// --- LÓGICA DE SALVAMENTO (Mantida do original) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $sqlClie = $pdo->prepare("UPDATE clientes SET clientenomecompleto=?, clientewhatsapp=?, clientecep=?, clientelogradouro=?, clientenumero=?, clientecpl=?, clientebairro=?, clientecidade=? WHERE clientecodigo=?");
        $sqlClie->execute([$_POST['clientenomecompleto'] ?? '', $_POST['clientewhatsapp'] ?? '', $_POST['clientecep'] ?? '', $_POST['clientelogradouro'] ?? '', $_POST['clientenumero'] ?? '', $_POST['clientecpl'] ?? '', $_POST['clientebairro'] ?? '', $_POST['clientecidade'] ?? '', $_POST['clienteid']]);

        $pdo->prepare("DELETE FROM pedidoitem WHERE pedidocodigo=?")->execute([$pedidoid]);
        $sqlItem = $pdo->prepare("INSERT INTO pedidoitem (pedidocodigo, pedidoitemseq, produtocodigo, produtodescricao, largura, altura, m2, pedidoqnt, pedidovalor, pedidodesconto, pedidovalortotal) VALUES (?,?,?,?,?,?,?,?,?,?,?)");

        $totalBruto = 0;
        $seq = 1; 
        foreach($_POST['itens'] ?? [] as $i){
            if(empty($i['id'])) continue;
            $larg = floatval($i['largura'] ?? 0); $alt = floatval($i['altura'] ?? 0); $valor = floatval($i['valor'] ?? 0);
            $m2 = ($larg > 0 && $alt > 0) ? ($larg * $alt / 1000000) : 0;
            $sqlItem->execute([$pedidoid, $seq, $i['id'], $i['nome'], $larg, $alt, $m2, 1, $valor, 0, $valor]);
            $totalBruto += $valor; $seq++;
        }

        $descontoGlobal = (float) ($_POST['pedidovlrdesconto'] ?? 0);
        $totalLiquido = $totalBruto - $descontoGlobal;
        $sqlPedido = $pdo->prepare("UPDATE pedido SET pedidoprevisaoentrega=?, pedidoformapagamento=?, pedidosituacao=?, pedidototal=?, pedidovlrdesconto=? WHERE pedidocodigo=?");
        $sqlPedido->execute([$_POST['pedidoprevisaoentrega'], $_POST['pedidoformapagamento'], $_POST['pedidosituacao'], $totalLiquido, $descontoGlobal, $pedidoid]);

        $pdo->commit();
        header("Location: editar.php?id=$pedidoid&sucesso=1");
        exit;
    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = "Erro: " . $e->getMessage();
    }
}

// --- BUSCA DE DADOS ---
$sql = $pdo->prepare("SELECT p.*, c.* FROM pedido p JOIN clientes c ON p.clienteid = c.clientecodigo WHERE p.pedidocodigo=?");
$sql->execute([$pedidoid]);
$pedido = $sql->fetch(PDO::FETCH_ASSOC);
$sql = $pdo->prepare("SELECT * FROM pedidoitem WHERE pedidocodigo=? ORDER BY pedidoitemseq");
$sql->execute([$pedidoid]);
$itens = $sql->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
require_once "../includes/menu.php";
$whatsLink = preg_replace('/\D/', '', $pedido['clientewhatsapp']);
?>

<style>
    :root {
        --app-bg: #f4f7fa;
        --app-primary: #4e73df;
        --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    body { background-color: var(--app-bg); font-family: 'Nunito', sans-serif; }

    /* Header e Navegação */
    .page-header { background: #fff; padding: 1rem 0; border-bottom: 1px solid #e3e6f0; margin-bottom: 1.5rem; }
    
    /* Cards Estilizados */
    .app-card { 
        background: white; 
        border-radius: 12px; 
        border: 1px solid #e3e6f0; 
        box-shadow: var(--card-shadow); 
        margin-bottom: 1.5rem; 
        padding: 1.5rem;
    }
    .section-title { 
        font-size: 0.85rem; 
        font-weight: 700; 
        color: var(--app-primary); 
        text-transform: uppercase; 
        letter-spacing: 0.05rem; 
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Inputs Modernos */
    .form-label { font-weight: 600; color: #4e5e7a; font-size: 0.85rem; margin-bottom: 0.4rem; }
    .input-app { border-radius: 8px; padding: 0.55rem; border: 1px solid #d1d3e2; font-size: 0.9rem; transition: 0.2s; }
    .input-app:focus { border-color: var(--app-primary); box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.1); outline: none; }

    /* Itens e Tabela */
    .item-row-mobile { 
        background: #f8f9fc; 
        border-radius: 10px; 
        padding: 15px; 
        margin-bottom: 12px; 
        border: 1px solid #eaecf4;
        transition: 0.2s;
    }
    .item-row-mobile:hover { border-color: var(--app-primary); }

    /* Responsividade Desktop */
    @media (min-width: 992px) {
        .sticky-sidebar { position: sticky; top: 20px; }
        .mobile-footer { display: none; } /* Esconde barra flutuante no PC */
        .desktop-save-btn { display: block; }
    }

    /* Responsividade Mobile */
    @media (max-width: 991px) {
        body { padding-bottom: 90px; }
        .desktop-save-btn { display: none; }
        .mobile-footer { 
            position: fixed; bottom: 0; left: 0; right: 0; 
            background: white; padding: 1rem 1.5rem; 
            box-shadow: 0 -5px 15px rgba(0,0,0,0.1); 
            z-index: 1050; display: flex; 
            justify-content: space-between; align-items: center;
        }
    }
</style>

<div class="page-header shadow-sm bg-white">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-dark mb-0">Pedido #<?= $pedidoid ?></h4>
         </div>
        <div class="d-flex gap-2">
            <a href="https://wa.me/55<?= $whatsLink ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-2"><i class="bi bi-whatsapp"></i> Cliente</a>
            <a href="imprimir.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-2"><i class="bi bi-printer"></i> PDF</a>
            <a href="desenho.php?id=<?= $pedidoid ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-2"><i class="bi bi-printer"></i> Desenho</a>
        </div>
    </div>
</div>

<div class="container">
    <?php if($erro): ?><div class="alert alert-danger border-0"><?= $erro ?></div><?php endif; ?>
    <?php if(isset($_GET['sucesso'])): ?><div class="alert alert-success border-0 shadow-sm">✓ Pedido atualizado com sucesso!</div><?php endif; ?>

    <form method="post" id="formPedido">
        <input type="hidden" name="clienteid" value="<?= $pedido['clienteid'] ?>">

        <div class="row">
            <div class="col-lg-8">
                
                <div class="app-card">
                    <span class="section-title"><i class="bi bi-person-bounding-box"></i> Informações do Cliente</span>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="clientenomecompleto" class="form-control input-app" value="<?= htmlspecialchars($pedido['clientenomecompleto']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="clientewhatsapp" class="form-control input-app" value="<?= htmlspecialchars($pedido['clientewhatsapp']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" name="clientecep" class="form-control input-app" value="<?= $pedido['clientecep'] ?>">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Endereço</label>
                            <input type="text" name="clientelogradouro" class="form-control input-app" value="<?= $pedido['clientelogradouro'] ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Nº</label>
                            <input type="text" name="clientenumero" class="form-control input-app" value="<?= $pedido['clientenumero'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Complemento</label>
                            <input type="text" name="clientecpl" class="form-control input-app" value="<?= $pedido['clientecpl'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" name="clientebairro" class="form-control input-app" value="<?= $pedido['clientebairro'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cidade</label>
                            <input type="text" name="clientecidade" class="form-control input-app" value="<?= $pedido['clientecidade'] ?>">
                        </div>
                    </div>
                </div>

                <div class="app-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="section-title mb-0"><i class="bi bi-cart-check"></i> Itens do Pedido</span>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProdutos">+ Adicionar Produto</button>
                    </div>

                    <div id="listaItens">
                        <?php foreach($itens as $k => $i): ?>
                        <div class="item-row-mobile">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold text-primary"><i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($i['produtodescricao']) ?></span>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 remover"><i class="bi bi-trash"></i></button>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4 col-4">
                                    <label class="small text-muted mb-1 d-block">Largura (mm)</label>
                                    <input type="number" name="itens[<?= $k ?>][largura]" class="form-control form-control-sm largura input-app" value="<?= $i['largura'] ?>">
                                </div>
                                <div class="col-md-4 col-4">
                                    <label class="small text-muted mb-1 d-block">Altura (mm)</label>
                                    <input type="number" name="itens[<?= $k ?>][altura]" class="form-control form-control-sm altura input-app" value="<?= $i['altura'] ?>">
                                </div>
                                <div class="col-md-4 col-4">
                                    <label class="small text-muted mb-1 d-block">Valor (R$)</label>
                                    <input type="number" name="itens[<?= $k ?>][valor]" class="form-control form-control-sm valor input-app fw-bold text-dark" value="<?= $i['pedidovalor'] ?>" step="0.01">
                                </div>
                            </div>
                            <input type="hidden" name="itens[<?= $k ?>][id]" value="<?= $i['produtocodigo'] ?>">
                            <input type="hidden" name="itens[<?= $k ?>][nome]" value="<?= htmlspecialchars($i['produtodescricao']) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <div class="app-card border-left-primary">
                        <span class="section-title"><i class="bi bi-info-circle"></i> Status do Pedido</span>
                        <div class="mb-3">
                            <label class="form-label">Situação Atual</label>
                            <select name="pedidosituacao" class="form-select input-app fw-bold">
                                <?php foreach(['Criado','Produção','Instalação','Finalizado','Cancelado'] as $s): ?>
                                    <option <?= $pedido['pedidosituacao'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Previsão de Entrega</label>
                            <input type="datetime-local" name="pedidoprevisaoentrega" class="form-control input-app" value="<?= date('Y-m-d\TH:i', strtotime($pedido['pedidoprevisaoentrega'])) ?>">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Forma de Pagamento</label>
                            <input type="text" name="pedidoformapagamento" class="form-control input-app" value="<?= $pedido['pedidoformapagamento'] ?>">
                        </div>
                    </div>

                    <div class="app-card bg-light border-0">
                        <span class="section-title text-dark"><i class="bi bi-cash-stack"></i> Resumo Financeiro</span>
                        <div class="mb-3">
                            <label class="form-label text-danger">Desconto (R$)</label>
                            <input type="number" name="pedidovlrdesconto" id="pedidovlrdesconto" class="form-control input-app border-danger text-danger fw-bold" value="<?= $pedido['pedidovlrdesconto'] ?>" step="0.01">
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="fw-bold text-muted">TOTAL LÍQUIDO:</span>
                            <span id="label_liquido_desk" class="h3 fw-bold text-primary mb-0">R$ 0,00</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold mt-4 shadow-sm desktop-save-btn">SALVAR PEDIDO</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mobile-footer">
            <div>
                <span class="small text-muted d-block">Total a pagar</span>
                <span id="label_liquido" class="h4 fw-bold text-primary mb-0">R$ 0,00</span>
            </div>
            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow">SALVAR</button>
        </div>
    </form>
</div>

<?php include "../orcamentos/modal_produtos.php"; ?>

<script>
let index = <?= count($itens) ?>;

function atualizarTotais(){
    let totalBruto = 0;
    document.querySelectorAll('.item-row-mobile').forEach(row => {
        const valor = parseFloat(row.querySelector('.valor').value) || 0;
        totalBruto += valor;
    });
    const desc = parseFloat(document.getElementById('pedidovlrdesconto').value) || 0;
    const liq = totalBruto - desc;
    const formatado = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    
    document.getElementById('label_liquido').innerText = formatado;
    if(document.getElementById('label_liquido_desk')) {
        document.getElementById('label_liquido_desk').innerText = formatado;
    }
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('selecionarProduto')) {
        const tr = e.target.closest('tr');
        const container = document.getElementById('listaItens');
        const item = document.createElement('div');
        item.className = "item-row-mobile";
        item.innerHTML = `
            <div class="d-flex justify-content-between mb-3">
                <span class="fw-bold text-primary"><i class="bi bi-tag-fill me-1"></i> ${tr.dataset.nome}</span>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 remover"><i class="bi bi-trash"></i></button>
            </div>
            <div class="row g-2">
                <div class="col-4">
                    <input type="number" name="itens[${index}][largura]" class="form-control form-control-sm largura input-app" value="0">
                </div>
                <div class="col-4">
                    <input type="number" name="itens[${index}][altura]" class="form-control form-control-sm altura input-app" value="0">
                </div>
                <div class="col-4">
                    <input type="number" name="itens[${index}][valor]" class="form-control form-control-sm valor input-app fw-bold" value="${parseFloat(tr.dataset.preco).toFixed(2)}" step="0.01">
                </div>
            </div>
            <input type="hidden" name="itens[${index}][id]" value="${tr.dataset.id}">
            <input type="hidden" name="itens[${index}][nome]" value="${tr.dataset.nome}">
        `;
        container.appendChild(item);
        index++;
        atualizarTotais();
        bootstrap.Modal.getInstance(document.getElementById('modalProdutos')).hide();
    }
    if (e.target.closest('.remover')) {
        e.target.closest('.item-row-mobile').remove();
        atualizarTotais();
    }
});

document.addEventListener('input', e => { 
    if(e.target.matches('.largura, .altura, .valor, #pedidovlrdesconto')) atualizarTotais(); 
});

window.onload = atualizarTotais;
</script>

<?php include "../includes/footer.php"; ?>