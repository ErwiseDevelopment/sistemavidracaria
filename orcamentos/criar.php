<?php
// 1. PROCESSAMENTO PHP
require_once "../config/database.php";
require_once "../config/config.php";

$erro = "";
$sucesso = "";
$orcamentoid = null;

// Buscar clientes para o modal
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY clientenomecompleto ASC")->fetchAll();

if ($_POST) {
    try {
        if (empty($_POST['clientenomecompleto'])) throw new Exception("O nome do cliente é obrigatório.");
        if (empty($_POST['itens'])) throw new Exception("Adicione pelo menos um produto.");

        $pdo->beginTransaction();

        $clienteid = $_POST['clienteid'] ?? null;
        if (!$clienteid) {
            $sql = $pdo->prepare("INSERT INTO clientes 
                (clientenomecompleto, clientewhatsapp, clientecep, clientelogradouro, clientenumero, clientecpl, clientebairro, clientecidade, clientesituacao)
                VALUES (?,?,?,?,?,?,?,?,1)");
            $sql->execute([
                $_POST['clientenomecompleto'], $_POST['clientewhatsapp'], $_POST['clientecep'],
                $_POST['clientelogradouro'], $_POST['clientenumero'], $_POST['clientecpl'],
                $_POST['clientebairro'], $_POST['clientecidade']
            ]);
            $clienteid = $pdo->lastInsertId();
        } else {
            $sql = $pdo->prepare("UPDATE clientes SET
                clientenomecompleto=?, clientewhatsapp=?, clientecep=?, clientelogradouro=?, clientenumero=?, clientecpl=?, clientebairro=?, clientecidade=?
                WHERE clientecodigo=?");
            $sql->execute([
                $_POST['clientenomecompleto'], $_POST['clientewhatsapp'], $_POST['clientecep'],
                $_POST['clientelogradouro'], $_POST['clientenumero'], $_POST['clientecpl'],
                $_POST['clientebairro'], $_POST['clientecidade'],
                $clienteid
            ]);
        }

        $link = md5(uniqid(rand(), true));
        $descontoGeral = floatval($_POST['orcamentovlrdesconto'] ?? 0);
        $observacaoOrcamento = $_POST['orcamento_obs'] ?? '';
        
        $sql = $pdo->prepare("INSERT INTO orcamento 
            (clienteid, orcamentoprevisaoentrega, orcamentoformapagamento, orcamentolinkaprovacao, orcamentosituacao, orcamentovlrdesconto, orcamento_obs, orcamentodatacriacao)
            VALUES (?,?,?,?, 'Criado', ?, ?, NOW())");
        $sql->execute([
            $clienteid, $_POST['orcamentoprevisaoentrega'], $_POST['orcamentoformapagamento'], $link, $descontoGeral, $observacaoOrcamento
        ]);
        $orcamentoid = $pdo->lastInsertId();

        $sqlItem = $pdo->prepare("INSERT INTO orcamentoitem
            (orcamentocodigo, orcamentoitemseq, produtocodigo, produtodescricao, largura, altura, m2, orcamentoqnt, orcamentovalor, orcamentodesconto, orcamentovalortotal)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)");

        $totalBruto = 0;
        $seq = 1;
        foreach ($_POST['itens'] as $i) {
            $valorDigitado = floatval($i['valor']); 
            $largura = floatval($i['largura'] ?? 0);
            $altura = floatval($i['altura'] ?? 0);
            $m2 = ($largura > 0 && $altura > 0) ? ($largura * $altura / 1000000) : 0;
            
            $totalItem = $valorDigitado;

            $sqlItem->execute([
                $orcamentoid, $seq, $i['id'], $i['nome'], 
                $largura, $altura, $m2, 1, $valorDigitado, 0, $totalItem
            ]);
            
            $totalBruto += $totalItem;
            $seq++;
        }

        $totalLiquido = $totalBruto - $descontoGeral;
        $pdo->prepare("UPDATE orcamento SET orcamentovalortotal=? WHERE orcamentocodigo=?")
            ->execute([$totalLiquido, $orcamentoid]);

        $pdo->commit();
        $sucesso = "Orçamento #$orcamentoid criado com sucesso!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    :root { --primary: #4361ee; --bg-light: #f8f9fd; }
    body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; }
    .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .status-header { background: white; padding: 1rem; border-bottom: 1px solid #edf2f7; margin-bottom: 1.5rem; }
    .total-display { background: #f0f3ff; color: var(--primary); border: 2px solid var(--primary); font-size: 1.5rem; font-weight: 800; border-radius: 12px; padding: 8px; width: 100%; max-width: 250px; }
    .section-title { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    .input-medida { background-color: #fffdf0 !important; border: 1px solid #ffeb3b !important; font-weight: bold; text-align: center; }
</style>

<div class="status-header shadow-sm sticky-top">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h5 class="fw-bold mb-0">Novo Orçamento</h5></div>
        <div class="d-flex align-items-center gap-3">
            <input type="text" id="valor_total_topo" class="total-display text-center" value="R$ 0,00" readonly>
            <a href="listar.php" class="btn btn-light rounded-pill border px-3">Sair</a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if($erro): ?><div class="alert alert-danger border-0 shadow-sm"><?= $erro ?></div><?php endif; ?>
    <?php if($sucesso): ?><div class="alert alert-success border-0 shadow-sm"><?= $sucesso ?></div><?php endif; ?>

    <form method="post" id="formOrcamento">
        <input type="hidden" name="clienteid" id="clienteid">
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-custom p-4">
                    <div class="section-title"><i class="bi bi-info-circle-fill"></i> Dados do Orçamento</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Previsão de Entrega</label>
                            <input type="datetime-local" name="orcamentoprevisaoentrega" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Forma de Pagamento</label>
                            <select name="orcamentoformapagamento" class="form-select">
                                <option>PIX</option>
                                <option>Dinheiro</option>
                                <option>Cartão de Crédito</option>
                                <option>Cartão de Débito</option>
                                <option>Boleto</option>
                                <option>Parcelado</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-primary">WhatsApp</label>
                            <input type="number" name="clientewhatsapp" id="clientewhatsapp" class="form-control fw-bold border-primary" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-end">
                                <label class="small fw-bold">Nome Completo</label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalClientes">Buscar na lista</button>
                            </div>
                            <input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" required>
                        </div>
                        <div class="col-md-3"><label class="small fw-bold">CEP</label><input type="text" name="clientecep" id="clientecep" class="form-control"></div>
                        <div class="col-md-7"><label class="small fw-bold">Logradouro</label><input type="text" name="clientelogradouro" id="clientelogradouro" class="form-control"></div>
                        <div class="col-md-2"><label class="small fw-bold">Nº</label><input type="text" name="clientenumero" id="clientenumero" class="form-control"></div>
                        <div class="col-md-4"><label class="small fw-bold">Bairro</label><input type="text" name="clientebairro" id="clientebairro" class="form-control"></div>
                        <div class="col-md-4"><label class="small fw-bold">Cidade</label><input type="text" name="clientecidade" id="clientecidade" class="form-control"></div>
                        <div class="col-md-4"><label class="small fw-bold">Complemento</label><input type="text" name="clientecpl" id="clientecpl" class="form-control"></div>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0"><i class="bi bi-cart-fill"></i> Itens do Orçamento</div>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalProdutos">+ Adicionar Item</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="tabelaItens">
                            <thead>
                                <tr class="small text-muted text-uppercase">
                                    <th>Produto</th>
                                    <th width="100">Larg(mm)</th>
                                    <th width="100">Alt(mm)</th>
                                    <th width="140">Valor Final R$</th>
                                    <th width="40"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4 sticky-top" style="top: 110px;">
                    <div class="section-title">Resumo e Desconto</div>
                    <div class="mb-3">
                        <label class="small fw-bold">OBSERVAÇÕES DO ORÇAMENTO</label>
                        <textarea name="orcamento_obs" class="form-control" rows="4" placeholder="Detalhes técnicos..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-danger">DESCONTO (R$)</label>
                        <input type="number" name="orcamentovlrdesconto" id="orcamentovlrdesconto" class="form-control form-control-lg border-danger fw-bold text-danger" value="0.00" step="0.01">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-muted">TOTAL:</span>
                        <span id="label_liquido" class="h3 fw-bold text-primary mb-0">R$ 0,00</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-lg fw-bold">SALVAR ORÇAMENTO</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalClientes" tabindex="-1">
    <div class="modal-dialog modal-lg border-0">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Selecionar Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="buscaCliente" class="form-control mb-3" placeholder="Filtrar por nome...">
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-hover" id="listaClientes">
                        <tbody class="small">
                            <?php foreach($clientes as $c): ?>
                            <tr style="cursor:pointer" class="selecionarCliente" 
                                data-id="<?= $c['clientecodigo'] ?>" 
                                data-nome="<?= htmlspecialchars($c['clientenomecompleto']) ?>" 
                                data-whatsapp="<?= $c['clientewhatsapp'] ?>" 
                                data-cep="<?= $c['clientecep'] ?>" 
                                data-logradouro="<?= htmlspecialchars($c['clientelogradouro']) ?>" 
                                data-numero="<?= $c['clientenumero'] ?>" 
                                data-bairro="<?= htmlspecialchars($c['clientebairro']) ?>" 
                                data-cidade="<?= htmlspecialchars($c['clientecidade']) ?>" 
                                data-cpl="<?= htmlspecialchars($c['clientecpl'] ?? '') ?>">
                                <td class="fw-bold"><?= htmlspecialchars($c['clientenomecompleto']) ?></td>
                                <td><?= $c['clientewhatsapp'] ?></td>
                                <td class="text-end"><span class="btn btn-sm btn-primary">Selecionar</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../orcamentos/modal_produtos.php"; ?>

<script>
let index = 0;

// FUNÇÃO DE FECHAMENTO GLOBAL
function fecharModais() {
    const modais = document.querySelectorAll('.modal.show');
    modais.forEach(m => {
        const instancia = bootstrap.Modal.getInstance(m) || bootstrap.Modal.getOrCreateInstance(m);
        if(instancia) instancia.hide();
    });
    setTimeout(() => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style = "";
    }, 300);
}

// SELEÇÃO DE PRODUTO
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('selecionarProduto')) {
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        const nome = tr.dataset.nome;
        
        const tbody = document.querySelector('#tabelaItens tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <span class="fw-bold d-block text-truncate" style="max-width: 250px;">${nome}</span>
                <small class="text-muted"><span class="val-m2">0.000</span> m²</small>
                <input type="hidden" name="itens[${index}][id]" value="${id}">
                <input type="hidden" name="itens[${index}][nome]" value="${nome}">
            </td>
            <td><input type="number" name="itens[${index}][largura]" class="form-control form-control-sm input-medida largura" placeholder="0"></td>
            <td><input type="number" name="itens[${index}][altura]" class="form-control form-control-sm input-medida altura" placeholder="0"></td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">R$</span>
                    <input class="form-control valor fw-bold text-primary" name="itens[${index}][valor]" value="0.00" step="0.01" type="number">
                </div>
            </td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger remover"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        index++;
        fecharModais();
        atualizarTotais();
    }
});

// CÁLCULO DE TOTAIS
function atualizarTotais(){
    let totalBruto = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const larg = parseFloat(tr.querySelector('.largura').value) || 0;
        const alt = parseFloat(tr.querySelector('.altura').value) || 0;
        const valorFinal = parseFloat(tr.querySelector('.valor').value) || 0;
        const m2 = (larg * alt) / 1000000;
        const campoM2 = tr.querySelector('.val-m2');
        if(campoM2) campoM2.innerText = m2.toFixed(3);
        totalBruto += valorFinal;
    });

    const desc = parseFloat(document.getElementById('orcamentovlrdesconto').value) || 0;
    const liq = totalBruto - desc;
    document.getElementById('label_liquido').innerText = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('valor_total_topo').value = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

// BUSCA WHATSAPP
document.getElementById('clientewhatsapp')?.addEventListener('blur', async function() {
    const whats = this.value.replace(/\D/g, '');
    if (whats.length >= 10) {
        try {
            const res = await fetch(`buscar_cliente_whats.php?whatsapp=${whats}`);
            if(!res.ok) return;
            const data = await res.json();
            if (data.sucesso) {
                document.getElementById('clienteid').value = data.id || '';
                document.getElementById('clientenomecompleto').value = data.nome || '';
                document.getElementById('clientecep').value = data.cep || '';
                document.getElementById('clientelogradouro').value = data.logradouro || '';
                document.getElementById('clientebairro').value = data.bairro || '';
                document.getElementById('clientenumero').value = data.numero || '';
                document.getElementById('clientecidade').value = data.cidade || '';
                document.getElementById('clientecpl').value = data.cpl || '';
            }
        } catch(e) { console.log("Erro na busca:", e); }
    }
});

// SELECIONAR CLIENTE MODAL
document.addEventListener('click', function(e) {
    const tr = e.target.closest('.selecionarCliente');
    if(tr){
        const d = tr.dataset;
        document.getElementById('clienteid').value = d.id || '';
        document.getElementById('clientenomecompleto').value = d.nome || '';
        document.getElementById('clientewhatsapp').value = d.whatsapp || '';
        document.getElementById('clientecep').value = d.cep || '';
        document.getElementById('clientelogradouro').value = d.logradouro || '';
        document.getElementById('clientenumero').value = d.numero || '';
        document.getElementById('clientebairro').value = d.bairro || '';
        document.getElementById('clientecidade').value = d.cidade || '';
        document.getElementById('clientecpl').value = d.cpl || '';
        fecharModais();
    }
});

// EVENTOS GERAIS
document.addEventListener('input', e => { 
    if(e.target.matches('.largura, .altura, .valor, #orcamentovlrdesconto')) atualizarTotais();
});

document.addEventListener('click', e => { 
    if(e.target.closest('.remover')) { 
        e.target.closest('tr').remove(); 
        atualizarTotais(); 
    } 
});

document.getElementById('clientecep')?.addEventListener('blur', async function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (!data.erro) {
            document.getElementById('clientelogradouro').value = data.logradouro || '';
            document.getElementById('clientebairro').value = data.bairro || '';
            document.getElementById('clientecidade').value = data.localidade || '';
        }
    }
});

document.getElementById('buscaCliente')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    document.querySelectorAll('#listaClientes tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(valor) ? '' : 'none';
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>