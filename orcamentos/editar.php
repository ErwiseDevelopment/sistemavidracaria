<?php
require_once "../config/database.php";
require_once "../config/config.php";

$erro = "";
$sucesso = "";

$orcamentoid = $_GET['id'] ?? null;
if(!$orcamentoid){
    header("Location: listar.php");
    exit;
}

// 1. BUSCAR ORÇAMENTO E CLIENTE (Garantindo que pegamos todos os campos)
$sql = $pdo->prepare("SELECT o.*, c.* FROM orcamento o JOIN clientes c ON o.clienteid = c.clientecodigo WHERE orcamentocodigo=?");
$sql->execute([$orcamentoid]);
$orcamento = $sql->fetch();
if(!$orcamento){
    header("Location: listar.php");
    exit;
}

$situacao = $orcamento['orcamentosituacao'];
$statusAnterior = $orcamento['orcamentosituacao'];
$bloqueado = ($orcamento['orcamentosituacao'] === 'Aprovado');

// 2. BUSCAR ITENS
$itensRaw = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=?");
$itensRaw->execute([$orcamentoid]);
$itens = $itensRaw->fetchAll();

if($_POST && !$bloqueado){
    try {
        $pdo->beginTransaction();

        $clienteid = $_POST['clienteid'];
        // Ajustado para incluir clientecpl e clienteobs corretamente
        $sqlC = $pdo->prepare("UPDATE clientes SET clientenomecompleto=?, clientewhatsapp=?, clientecep=?, clientelogradouro=?, clientenumero=?, clientecpl=?, clientebairro=?, clientecidade=?, clienteobs=? WHERE clientecodigo=?");
        $sqlC->execute([
            $_POST['clientenomecompleto'], 
            $_POST['clientewhatsapp'], 
            $_POST['clientecep'],
            $_POST['clientelogradouro'], 
            $_POST['clientenumero'], 
            $_POST['clientecpl'] ?? '',
            $_POST['clientebairro'], 
            $_POST['clientecidade'], 
            $_POST['clienteobs'] ?? '', 
            $clienteid
        ]);

        $previsao = $_POST['orcamentoprevisaoentrega'];
        $formapagamento = $_POST['orcamentoformapagamento'];
        $situacaoNova = $_POST['orcamentosituacao'];
        $descontoGeral = floatval($_POST['orcamentovlrdesconto'] ?? 0);

        $pdo->prepare("UPDATE orcamento SET orcamentoprevisaoentrega=?, orcamentoformapagamento=?, orcamentosituacao=?, orcamentovlrdesconto=? WHERE orcamentocodigo=?")
            ->execute([$previsao, $formapagamento, $situacaoNova, $descontoGeral, $orcamentoid]);

        $pdo->prepare("DELETE FROM orcamentoitem WHERE orcamentocodigo=?")->execute([$orcamentoid]);
        $itensPost = $_POST['itens'] ?? [];
        $sqlItem = $pdo->prepare("INSERT INTO orcamentoitem (orcamentocodigo, orcamentoitemseq, produtocodigo, produtodescricao, orcamentoqnt, orcamentovalor, orcamentodesconto, orcamentovalortotal) VALUES (?,?,?,?,?,?,?,?)");

        $totalBruto = 0;
        $seqItem = 1;
        foreach($itensPost as $i){
            if(empty($i['id'])) continue;
            $valor = floatval($i['valor']);
            $sqlItem->execute([$orcamentoid, $seqItem, $i['id'], $i['nome'], 1, $valor, 0, $valor]);
            $totalBruto += $valor;
            $seqItem++;
        }

        $totalLiquido = $totalBruto - $descontoGeral;
        $pdo->prepare("UPDATE orcamento SET orcamentovalortotal=? WHERE orcamentocodigo=?")->execute([$totalLiquido, $orcamentoid]);

        // Lógica de Pedido quando Aprova
        if($statusAnterior !== 'Aprovado' && $situacaoNova === 'Aprovado'){
            $sqlPed = $pdo->prepare("INSERT INTO pedido (clienteid, pedidoprevisaoentrega, pedidoformapagamento, pedidosituacao, pedidototal, pedidodatacriacao, pedidovlrdesconto) VALUES (?,?,?,?,?, NOW(),?)");
            $sqlPed->execute([$clienteid, $previsao, $formapagamento, 'Criado', $totalLiquido, $descontoGeral ]);
            $pedidoid = $pdo->lastInsertId();

            $sqlItP = $pdo->prepare("INSERT INTO pedidoitem (pedidocodigo, pedidoitemseq, produtocodigo, produtodescricao, pedidoqnt, pedidovalor, pedidodesconto, pedidovalortotal) VALUES (?,?,?,?,?,?,?,?)");
            foreach($itensPost as $idx => $i){
                $v = floatval($i['valor']);
                $sqlItP->execute([$pedidoid, $idx+1, $i['id'], $i['nome'], 1, $v, 0, $v]);
            }
        }

        $pdo->commit();
        header("Location: editar.php?id=$orcamentoid&sucesso=1");
        exit;
    } catch(Exception $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    :root { --primary: #4361ee; --bg-light: #f8f9fd; }
    body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; }
    .card-custom { background: white; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.04); margin-bottom: 1.5rem; }
    .status-header { background: white; padding: 1rem; border-bottom: 1px solid #edf2f7; margin-bottom: 1.5rem; }
    .total-display { background: #f0f3ff; color: var(--primary); border: 2px solid var(--primary); font-size: 1.5rem; font-weight: 800; border-radius: 15px; padding: 10px; }
    .section-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }

    @media (max-width: 767.98px) {
        .item-row { background: white; border: 1px solid #e2e8f0; border-radius: 15px; padding: 15px; margin-bottom: 10px; position: relative; display: block !important; }
        .item-row td { display: block; border: none !important; padding: 5px 0 !important; }
        thead { display: none; }
        .remover-btn-container { position: absolute; top: 10px; right: 10px; }
    }
</style>

<div class="status-header shadow-sm sticky-top">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark">Editar Orçamento #<?= $orcamentoid ?></h5>
            <span class="badge rounded-pill <?= $bloqueado ? 'bg-success' : 'bg-warning' ?> text-uppercase"><?= $situacao ?></span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <input type="text" id="valor_total_topo" class="total-display text-center" style="width: 200px;" value="R$ <?= number_format($orcamento['orcamentovalortotal'], 2, ',', '.') ?>" readonly>
            <a href="listar.php" class="btn btn-light rounded-pill px-3 fw-bold border">Sair</a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if(isset($_GET['sucesso'])): ?><div class="alert alert-success border-0 shadow-sm rounded-4"><i class="bi bi-check-circle me-2"></i>Alterações salvas com sucesso!</div><?php endif; ?>
    <?php if($erro): ?><div class="alert alert-danger border-0 shadow-sm rounded-4"><?= $erro ?></div><?php endif; ?>

    <form method="post" id="formOrcamento">
        <input type="hidden" name="clienteid" id="clienteid" value="<?= $orcamento['clienteid'] ?>">

        <div class="row">
            <div class="col-lg-8">
                <div class="card card-custom p-4">
                    <div class="section-title"><i class="bi bi-gear-fill"></i> Detalhes do Serviço</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Previsão Instalação</label>
                            <input type="datetime-local" name="orcamentoprevisaoentrega" class="form-control rounded-3" value="<?= date('Y-m-d\TH:i', strtotime($orcamento['orcamentoprevisaoentrega'])) ?>" <?= $bloqueado?'readonly':'' ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Pagamento</label>
                            <select name="orcamentoformapagamento" class="form-select rounded-3" <?= $bloqueado?'disabled':'' ?>>
                                <?php foreach(['PIX','Dinheiro','Crédito','Débito','Parcelado','Outros'] as $f): ?>
                                    <option <?= ($orcamento['orcamentoformapagamento']==$f)?'selected':'' ?>><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Situação</label>
                            <select name="orcamentosituacao" class="form-select rounded-3 fw-bold <?= $bloqueado?'text-success':'' ?>" <?= $bloqueado?'disabled':'' ?>>
                                <?php foreach(['Criado','Aguardando Retorno Cliente','Cancelado','Aprovado'] as $s): ?>
                                    <option <?= ($orcamento['orcamentosituacao']==$s)?'selected':'' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0"><i class="bi bi-cart-fill"></i> Itens do Orçamento</div>
                        <?php if(!$bloqueado): ?>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProdutos">
                            + Adicionar Item
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table align-middle" id="tabelaItens">
                            <thead class="bg-light">
                                <tr class="text-muted small">
                                    <th>Descrição do Produto</th>
                                    <th width="180">Vlr. Unitário</th>
                                    <th width="120" class="text-end">Total</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($itens as $idx => $item): ?>
                                <tr class="item-row">
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['produtodescricao']) ?></div>
                                        <input type="hidden" name="itens[<?= $idx ?>][id]" value="<?= $item['produtocodigo'] ?>">
                                        <input type="hidden" name="itens[<?= $idx ?>][nome]" value="<?= htmlspecialchars($item['produtodescricao']) ?>">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text border-0 bg-light">R$</span>
                                            <input type="number" name="itens[<?= $idx ?>][valor]" value="<?= $item['orcamentovalor'] ?>" class="form-control border-light bg-light valorItem" step="0.01" <?= $bloqueado?'readonly':'' ?>>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        R$ <span class="totalItem"><?= number_format($item['orcamentovalortotal'], 2, ',', '.') ?></span>
                                    </td>
                                    <td class="text-center remover-btn-container">
                                        <?php if(!$bloqueado): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm border-0 removerItem"><i class="bi bi-trash3"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0"><i class="bi bi-person-fill"></i> Cliente</div>
                        <?php if(!$bloqueado): ?>
                            <button type="button" class="btn btn-outline-dark btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalClientes">Trocar</button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nome</label>
                        <input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= htmlspecialchars($orcamento['clientenomecompleto']) ?>" <?= $bloqueado?'readonly':'' ?>>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">WhatsApp</label>
                        <input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control form-control-sm border-0 bg-light" value="<?= htmlspecialchars($orcamento['clientewhatsapp']) ?>" <?= $bloqueado?'readonly':'' ?>>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Endereço</label>
                        <div class="small text-dark p-2 bg-light rounded-3 mb-2">
                            <i class="bi bi-geo-alt-fill text-primary me-1"></i>
                            <span id="label_endereco"><?= htmlspecialchars($orcamento['clientelogradouro']) ?>, <?= $orcamento['clientenumero'] ?> - <?= $orcamento['clientebairro'] ?></span>
                        </div>
                        
                        <label class="small fw-bold text-muted">Complemento / Ref.</label>
                        <input type="text" name="clientecpl" id="clientecpl" class="form-control form-control-sm border-0 bg-light mb-2" value="<?= htmlspecialchars($orcamento['clientecpl']) ?>" <?= $bloqueado?'readonly':'' ?>>

                        <label class="small fw-bold text-muted">Observações do Cliente</label>
                        <textarea name="clienteobs" id="clienteobs" class="form-control form-control-sm border-0 bg-light" rows="2" <?= $bloqueado?'readonly':'' ?>><?= htmlspecialchars($orcamento['clienteobs']) ?></textarea>
                    </div>

                    <input type="hidden" name="clientecep" id="clientecep" value="<?= $orcamento['clientecep'] ?>">
                    <input type="hidden" name="clientelogradouro" id="clientelogradouro" value="<?= $orcamento['clientelogradouro'] ?>">
                    <input type="hidden" name="clientenumero" id="clientenumero" value="<?= $orcamento['clientenumero'] ?>">
                    <input type="hidden" name="clientecidade" id="clientecidade" value="<?= $orcamento['clientecidade'] ?>">
                    <input type="hidden" name="clientebairro" id="clientebairro" value="<?= $orcamento['clientebairro'] ?>">
                </div>  

                <div class="card card-custom p-4 border-primary" style="border: 2px solid #e0e7ff !important;">
                    <div class="section-title"><i class="bi bi-cash-stack"></i> Resumo Financeiro</div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small fw-bold">SUBTOTAL:</span>
                        <span id="label_bruto" class="fw-bold">R$ 0,00</span>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-danger">DESCONTO (R$)</label>
                        <input type="number" name="orcamentovlrdesconto" id="orcamentovlrdesconto" class="form-control form-control-lg border-danger text-danger fw-bold" value="<?= $orcamento['orcamentovlrdesconto'] ?>" step="0.01" <?= $bloqueado?'readonly':'' ?>>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">TOTAL:</span>
                        <span id="label_liquido" class="h4 fw-bold text-primary mb-0">R$ 0,00</span>
                    </div>

                    <?php if(!$bloqueado): ?>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4 rounded-pill fw-bold shadow">
                        SALVAR TUDO
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'modal_clientes.php'; ?>
<?php include '../orcamentos/modal_produtos.php'; ?>

<script>
let itemIndex = <?= count($itens) ?>;

function atualizarTotal() {
    let totalBruto = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const v = parseFloat(tr.querySelector('.valorItem')?.value) || 0;
        if(tr.querySelector('.totalItem')) {
            tr.querySelector('.totalItem').textContent = v.toLocaleString('pt-br', {minimumFractionDigits: 2});
        }
        totalBruto += v;
    });

    const desconto = parseFloat(document.getElementById('orcamentovlrdesconto').value) || 0;
    const totalLiquido = totalBruto - desconto;

    document.getElementById('label_bruto').innerText = totalBruto.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('label_liquido').innerText = totalLiquido.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('valor_total_topo').value = totalLiquido.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

// CORREÇÃO TELA CINZA: Função unificada para fechar modais
function fecharModal(idModal) {
    const modalEl = document.getElementById(idModal);
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (modalInstance) modalInstance.hide();
    
    // Força a limpeza do fundo cinza
    setTimeout(() => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style = "";
    }, 300);
}

document.addEventListener('input', e => {
    if(e.target.matches('.valorItem, #orcamentovlrdesconto')) atualizarTotal();
});

document.addEventListener('click', e => {
    if(e.target.closest('.removerItem')){
        e.target.closest('.item-row').remove();
        atualizarTotal();
    }
});

// Seleção de Produto
document.addEventListener('click', e => {
    if(e.target.classList.contains('selecionarProduto')){
        const trModal = e.target.closest('tr');
        const id = trModal.dataset.id;
        const nome = trModal.dataset.nome;
        const valor = parseFloat(trModal.dataset.preco || 0); // Ajustado para 'preco' conforme o padrão anterior

        const tbody = document.querySelector('#tabelaItens tbody');
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.innerHTML = `
            <td>
                <div class="fw-bold text-dark">${nome}</div>
                <input type="hidden" name="itens[${itemIndex}][id]" value="${id}">
                <input type="hidden" name="itens[${itemIndex}][nome]" value="${nome}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text border-0 bg-light">R$</span>
                    <input type="number" name="itens[${itemIndex}][valor]" value="${valor.toFixed(2)}" class="form-control border-light bg-light valorItem" step="0.01">
                </div>
            </td>
            <td class="text-end fw-bold text-primary">R$ <span class="totalItem">${valor.toFixed(2).replace('.', ',')}</span></td>
            <td class="text-center remover-btn-container"><button type="button" class="btn btn-outline-danger btn-sm border-0 removerItem"><i class="bi bi-trash3"></i></button></td>
        `;
        tbody.appendChild(row);
        itemIndex++;
        atualizarTotal();
        fecharModal('modalProdutos');
    }
});

// Seleção de Cliente
// Seleção de Cliente no Modal
document.addEventListener('click', e => {
    if(e.target.classList.contains('selecionarCliente')){
        const tr = e.target.closest('tr');
        
        // Preenchendo os campos visíveis
        document.getElementById('clienteid').value = tr.dataset.id;
        document.getElementById('clientenomecompleto').value = tr.dataset.nome;
        document.getElementById('clientewhatsapp').value = tr.dataset.whatsapp;
        document.getElementById('clientecpl').value = tr.dataset.cpl || ''; // Complemento
        document.getElementById('clienteobs').value = tr.dataset.obs || ''; // Observação
        
        // Atualizando o label de endereço
        document.getElementById('label_endereco').innerText = `${tr.dataset.logradouro}, ${tr.dataset.numero} - ${tr.dataset.bairro}`;

        // Preenchendo os campos ocultos (essencial para o salvamento)
        document.getElementById('clientecep').value = tr.dataset.cep;
        document.getElementById('clientelogradouro').value = tr.dataset.logradouro;
        document.getElementById('clientenumero').value = tr.dataset.numero;
        document.getElementById('clientebairro').value = tr.dataset.bairro;
        document.getElementById('clientecidade').value = tr.dataset.cidade;
        
        fecharModal('modalClientes');
    }
});

document.addEventListener('DOMContentLoaded', atualizarTotal);
</script>

<?php require_once "../includes/footer.php"; ?>