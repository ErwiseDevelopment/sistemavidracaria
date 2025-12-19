<?php
// 1. PROCESSAMENTO PHP
require_once "../config/database.php";

$erro = "";
$sucesso = "";
$orcamentoid = null;

if ($_POST) {
    try {
        if (empty($_POST['clientenomecompleto'])) throw new Exception("O nome do cliente é obrigatório.");
        if (empty($_POST['itens'])) throw new Exception("Adicione pelo menos um produto.");

        $pdo->beginTransaction();

        // --- LÓGICA DO CLIENTE (Mantida Original) ---
        $clienteid = $_POST['clienteid'] ?? null;
        if (!$clienteid) {
            $sql = $pdo->prepare("INSERT INTO clientes 
                (clientenomecompleto, clientewhatsapp, clientecep, clientelogradouro, clientenumero, clientecpl, clientebairro, clientecidade, clienteobs, clientesituacao)
                VALUES (?,?,?,?,?,?,?,?,?,1)");
            $sql->execute([
                $_POST['clientenomecompleto'], $_POST['clientewhatsapp'], $_POST['clientecep'],
                $_POST['clientelogradouro'], $_POST['clientenumero'], $_POST['clientecpl'],
                $_POST['clientebairro'], $_POST['clientecidade'], $_POST['clienteobs']
            ]);
            $clienteid = $pdo->lastInsertId();
        } else {
            $sql = $pdo->prepare("UPDATE clientes SET
                clientenomecompleto=?, clientewhatsapp=?, clientecep=?, clientelogradouro=?, clientenumero=?, clientecpl=?, clientebairro=?, clientecidade=?, clienteobs=?
                WHERE clientecodigo=?");
            $sql->execute([
                $_POST['clientenomecompleto'], $_POST['clientewhatsapp'], $_POST['clientecep'],
                $_POST['clientelogradouro'], $_POST['clientenumero'], $_POST['clientecpl'],
                $_POST['clientebairro'], $_POST['clientecidade'], $_POST['clienteobs'],
                $clienteid
            ]);
        }

        // --- INSERE ORÇAMENTO (Com campo orcamentovlrdesconto) ---
        $link = md5(uniqid(rand(), true));
        $descontoGeral = floatval($_POST['orcamentovlrdesconto'] ?? 0);
        
        $sql = $pdo->prepare("INSERT INTO orcamento 
            (clienteid, orcamentoprevisaoentrega, orcamentoformapagamento, orcamentolinkaprovacao, orcamentosituacao, orcamentovlrdesconto, orcamentodatacriacao)
            VALUES (?,?,?,?, 'Criado', ?, NOW())");
        $sql->execute([
            $clienteid, $_POST['orcamentoprevisaoentrega'], $_POST['orcamentoformapagamento'], $link, $descontoGeral
        ]);
        $orcamentoid = $pdo->lastInsertId();

        // --- INSERE ITENS (Qtd fixa 1 e Desconto Item 0) ---
        $sqlItem = $pdo->prepare("INSERT INTO orcamentoitem
            (orcamentocodigo, orcamentoitemseq, produtocodigo, produtodescricao, orcamentoqnt, orcamentovalor, orcamentodesconto, orcamentovalortotal)
            VALUES (?,?,?,?,?,?,?,?)");

        $totalBruto = 0;
        $seq = 1;
        foreach ($_POST['itens'] as $i) {
            $valor = floatval($i['valor']);
            $sqlItem->execute([$orcamentoid, $seq, $i['id'], $i['nome'], 1, $valor, 0, $valor]);
            $totalBruto += $valor;
            $seq++;
        }

        // --- ATUALIZA TOTAL FINAL LÍQUIDO NO BANCO ---
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

<div class="container my-4">
    <?php if($erro): ?><div class="alert alert-danger shadow-sm border-0"><?= $erro ?></div><?php endif; ?>
    <?php if($sucesso): ?><div class="alert alert-success shadow-sm border-0"><?= $sucesso ?></div><?php endif; ?>

    <form method="post" id="formOrcamento">
        <div class="card shadow-sm border-0 p-3 p-md-4">
            
            <div class="row mb-4 align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-bold text-primary mb-0">Novo Orçamento</h4>
                </div>
                <div class="col-md-4 text-center mt-3 mt-md-0">
                    <label class="small fw-bold text-muted d-block uppercase">Total Líquido</label>
                    <input type="text" id="valor_total_topo" class="form-control form-control-lg text-center fw-bold border-primary text-primary bg-light" value="R$ 0,00" readonly>
                </div>
                <div class="col-md-4 text-end mt-3 mt-md-0">
                    <a href="listar.php" class="btn btn-outline-secondary px-4">Voltar</a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-12">
                    <label class="form-label fw-bold small">PREVISÃO DE ENTREGA</label>
                    <input type="datetime-local" name="orcamentoprevisaoentrega" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="col-md-6 col-12">
                    <label class="form-label fw-bold small">FORMA DE PAGAMENTO</label>
                    <select name="orcamentoformapagamento" class="form-select">
                        <option>PIX</option><option>Dinheiro</option><option>Cartão de Crédito</option><option>Cartão de Débito</option><option>Boleto</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="text-primary fw-bold mb-0">DADOS DO CLIENTE</h6>
                <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalClientes">
                    <i class="bi bi-search me-1"></i> Buscar Cliente
                </button>
            </div>

            <input type="hidden" name="clienteid" id="clienteid">
            <div class="row g-2 mb-3">
                <div class="col-md-6 col-12"><label class="small fw-bold">Nome Completo</label><input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" required></div>
                <div class="col-md-3 col-6"><label class="small fw-bold">WhatsApp</label><input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control"></div>
                <div class="col-md-3 col-6"><label class="small fw-bold">CEP</label><input type="text" name="clientecep" id="clientecep" class="form-control"></div>
            </div>
            
            <div class="row g-2 mb-3">
                <div class="col-md-5 col-12"><label class="small fw-bold">Logradouro</label><input name="clientelogradouro" id="clientelogradouro" class="form-control"></div>
                <div class="col-md-1 col-3"><label class="small fw-bold">Nº</label><input name="clientenumero" id="clientenumero" class="form-control"></div>
                <div class="col-md-3 col-9"><label class="small fw-bold">Bairro</label><input name="clientebairro" id="clientebairro" class="form-control"></div>
                <div class="col-md-3 col-12"><label class="small fw-bold">Cidade</label><input name="clientecidade" id="clientecidade" class="form-control"></div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4"><label class="small fw-bold">Complemento</label><input name="clientecpl" id="clientecpl" class="form-control"></div>
                <div class="col-md-8"><label class="small fw-bold">Observações</label><textarea name="clienteobs" id="clienteobs" class="form-control" rows="1"></textarea></div>
            </div>

            <h6 class="text-primary fw-bold small border-bottom pb-2 mb-3">ITENS DO ORÇAMENTO</h6>
            <div class="table-responsive">
                <table class="table table-hover border align-middle" id="tabelaItens">
                    <thead class="table-light small uppercase">
                        <tr>
                            <th>Produto</th>
                            <th width="220">Valor</th>
                            <th width="150" class="text-end">Subtotal</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="row justify-content-end mt-4">
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card bg-light border-0 p-3 shadow-sm">
                        <div class="mb-3">
                            <label class="small fw-bold text-danger">DESCONTO TOTAL (R$)</label>
                            <input type="number" name="orcamentovlrdesconto" id="orcamentovlrdesconto" class="form-control form-control-lg border-danger fw-bold" value="0.00" step="0.01">
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Soma dos Itens:</span>
                            <span id="label_bruto" class="fw-bold">R$ 0,00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h6 fw-bold mb-0 text-uppercase">Total Final:</span>
                            <span id="label_liquido" class="h5 fw-bold mb-0 text-primary">R$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between mt-4 gap-3">
                <button type="button" class="btn btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalProdutos">
                    <i class="bi bi-plus-circle me-1"></i> ADICIONAR ITEM
                </button>
                <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow">
                    GERAR ORÇAMENTO
                </button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalClientes" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Selecionar Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="buscaCliente" class="form-control mb-3" placeholder="Digite nome ou telefone para filtrar...">
        <div class="table-responsive" style="max-height: 400px;">
            <table class="table table-hover border" id="listaClientes">
                <thead class="table-light sticky-top">
                    <tr><th>Nome</th><th>WhatsApp</th><th class="text-end">Ação</th></tr>
                </thead>
                <tbody>
                    <?php
                    $clientes = $pdo->query("SELECT * FROM clientes WHERE clientesituacao=1 ORDER BY clientenomecompleto")->fetchAll();
                    foreach($clientes as $c): ?>
                    <tr data-id="<?= $c['clientecodigo'] ?>" 
                        data-nome="<?= htmlspecialchars($c['clientenomecompleto']) ?>" 
                        data-whatsapp="<?= $c['clientewhatsapp'] ?>" 
                        data-cep="<?= $c['clientecep'] ?>" 
                        data-logradouro="<?= htmlspecialchars($c['clientelogradouro']) ?>" 
                        data-numero="<?= $c['clientenumero'] ?>" 
                        data-bairro="<?= htmlspecialchars($c['clientebairro']) ?>" 
                        data-cidade="<?= htmlspecialchars($c['clientecidade']) ?>" 
                        data-cpl="<?= htmlspecialchars($c['clientecpl'] ?? '') ?>" 
                        data-obs="<?= htmlspecialchars($c['clienteobs'] ?? '') ?>">
                        <td class="fw-bold"><?= htmlspecialchars($c['clientenomecompleto']) ?></td>
                        <td><?= htmlspecialchars($c['clientewhatsapp']) ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-primary selecionarCliente">Escolher</button>
                        </td>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let index = 0;

// 1. INTEGRAÇÃO COM MODAL_PRODUTOS.PHP (RESTAURADA)
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('selecionarProduto')) {
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        const nome = tr.dataset.nome;
        addProdutoManual(id, nome);
    }
});

function addProdutoManual(id, nome){
    const tbody = document.querySelector('#tabelaItens tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <span class="fw-bold text-dark d-block">${nome}</span>
            <input type="hidden" name="itens[${index}][id]" value="${id}">
            <input type="hidden" name="itens[${index}][nome]" value="${nome}">
            <input type="hidden" class="qtd" value="1"> 
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">R$</span>
                <input class="form-control valor" name="itens[${index}][valor]" value="0.00" step="0.01" type="number">
            </div>
            <input type="hidden" class="desconto" value="0">
        </td>
        <td class="text-end fw-bold">R$ <span class="total">0,00</span></td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm remover"><i class="bi bi-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
    index++;
    atualizarTotais();
    
    // Fecha modal
    const mProd = document.getElementById('modalProdutos');
    if(mProd) bootstrap.Modal.getOrCreateInstance(mProd).hide();
}

// 2. CÁLCULOS TOTAIS (RESTAURADOS COM DESCONTO GLOBAL)
function atualizarTotais(){
    let totalBruto = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const v = parseFloat(tr.querySelector('.valor').value) || 0;
        tr.querySelector('.total').innerText = v.toLocaleString('pt-br', {minimumFractionDigits: 2});
        totalBruto += v;
    });

    const dGeral = parseFloat(document.getElementById('orcamentovlrdesconto').value) || 0;
    const totalLiquido = totalBruto - dGeral;

    document.getElementById('label_bruto').innerText = totalBruto.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('label_liquido').innerText = totalLiquido.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('valor_total_topo').value = totalLiquido.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

// 3. SELEÇÃO DE CLIENTE (RESTAURADA CONFORME ORIGINAL)
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('selecionarCliente')){
        const tr = e.target.closest('tr');
        
        // Mapeamento dos campos baseado no dataset da tr
        document.getElementById('clienteid').value = tr.dataset.id;
        document.getElementById('clientenomecompleto').value = tr.dataset.nome;
        document.getElementById('clientewhatsapp').value = tr.dataset.whatsapp;
        document.getElementById('clientecep').value = tr.dataset.cep;
        document.getElementById('clientelogradouro').value = tr.dataset.logradouro;
        document.getElementById('clientenumero').value = tr.dataset.numero;
        document.getElementById('clientebairro').value = tr.dataset.bairro;
        document.getElementById('clientecidade').value = tr.dataset.cidade;
        document.getElementById('clientecpl').value = tr.dataset.cpl;
        document.getElementById('clienteobs').value = tr.dataset.obs;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalClientes')).hide();
    }
});

// 4. FILTRO DE BUSCA NO MODAL DE CLIENTES (RESTAURADO)
document.getElementById('buscaCliente')?.addEventListener('input', function(){
    const f = this.value.toLowerCase();
    document.querySelectorAll('#listaClientes tbody tr').forEach(tr=>{
        const nome = tr.dataset.nome.toLowerCase();
        const zap = tr.dataset.whatsapp;
        tr.style.display = (nome.includes(f) || zap.includes(f)) ? '' : 'none';
    });
});

// 5. VIACEP (RESTAURADO)
document.getElementById('clientecep')?.addEventListener('blur', async function(){
    const cep = this.value.replace(/\D/g,'');
    if(cep.length === 8){
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if(!data.erro){
            document.getElementById('clientelogradouro').value = data.logradouro || '';
            document.getElementById('clientebairro').value = data.bairro || '';
            document.getElementById('clientecidade').value = data.localidade || '';
        }
    }
});

// EVENTOS DE ENTRADA
document.addEventListener('input', e => { 
    if(e.target.matches('.valor, #orcamentovlrdesconto')) atualizarTotais(); 
});
document.addEventListener('click', e => { 
    if(e.target.closest('.remover')) { 
        e.target.closest('tr').remove(); 
        atualizarTotais(); 
    } 
});
</script>