<?php
// 1. PROCESSAMENTO PHP
require_once "../config/database.php";
require_once "../config/config.php";

$erro = "";
$sucesso = "";
$orcamentoid = null;

if ($_POST) {
    try {
        if (empty($_POST['clientenomecompleto'])) throw new Exception("O nome do cliente é obrigatório.");
        if (empty($_POST['itens'])) throw new Exception("Adicione pelo menos um produto.");

        $pdo->beginTransaction();

        // Lógica de Cliente (Insert ou Update)
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

        $link = md5(uniqid(rand(), true));
        $descontoGeral = floatval($_POST['orcamentovlrdesconto'] ?? 0);
        
        // Insere Orçamento com os novos campos
        $sql = $pdo->prepare("INSERT INTO orcamento 
            (clienteid, orcamentoprevisaoentrega, orcamentoformapagamento, orcamentolinkaprovacao, orcamentosituacao, orcamentovlrdesconto, orcamentodatacriacao)
            VALUES (?,?,?,?, 'Criado', ?, NOW())");
        $sql->execute([
            $clienteid, $_POST['orcamentoprevisaoentrega'], $_POST['orcamentoformapagamento'], $link, $descontoGeral
        ]);
        $orcamentoid = $pdo->lastInsertId();

        // Insere Itens
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

        // Atualiza Total Final
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
                    <div class="section-title"><i class="bi bi-info-circle-fill"></i> Informações do Orçamento</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Previsão de Entrega</label>
                            <input type="datetime-local" name="orcamentoprevisaoentrega" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Forma de Pagamento</label>
                            <select name="orcamentoformapagamento" class="form-select">
                                <option value="PIX">PIX</option>
                                <option value="Dinheiro">Dinheiro</option>
                                <option value="Cartão de Crédito">Cartão de Crédito</option>
                                <option value="Cartão de Débito">Cartão de Débito</option>
                                <option value="Boleto">Boleto</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0"><i class="bi bi-person-fill"></i> Dados do Cliente</div>
                        <button type="button" class="btn btn-outline-dark btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalClientes">Buscar Cliente</button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="small fw-bold text-primary">WhatsApp (Consulta Automática)</label>
                            <input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control fw-bold border-primary" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-8">
                            <label class="small fw-bold">Nome Completo</label>
                            <input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" required>
                        </div>
                        <div class="col-md-3 col-6"><label class="small fw-bold">CEP</label><input type="text" name="clientecep" id="clientecep" class="form-control"></div>
                        <div class="col-md-7 col-6"><label class="small fw-bold">Logradouro</label><input type="text" name="clientelogradouro" id="clientelogradouro" class="form-control"></div>
                        <div class="col-md-2"><label class="small fw-bold">Nº</label><input type="text" name="clientenumero" id="clientenumero" class="form-control"></div>
                        <div class="col-md-4"><label class="small fw-bold">Bairro</label><input type="text" name="clientebairro" id="clientebairro" class="form-control"></div>
                        <div class="col-md-4"><label class="small fw-bold">Cidade</label><input type="text" name="clientecidade" id="clientecidade" class="form-control"></div>
                        <div class="col-md-4"><label class="small fw-bold">Complemento</label><input type="text" name="clientecpl" id="clientecpl" class="form-control"></div>
                        <div class="col-12"><label class="small fw-bold">Observações Internas</label><textarea name="clienteobs" id="clienteobs" class="form-control" rows="1"></textarea></div>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0"><i class="bi bi-cart-fill"></i> Itens do Orçamento</div>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalProdutos">+ Adicionar Produto</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="tabelaItens">
                            <thead><tr class="small text-muted"><th>Produto</th><th width="150">Valor Unit.</th><th width="120" class="text-end">Total</th><th width="50"></th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4 sticky-top" style="top: 110px;">
                    <div class="section-title">Resumo Financeiro</div>
                    <div class="mb-3">
                        <label class="small fw-bold text-danger">DESCONTO TOTAL (R$)</label>
                        <input type="number" name="orcamentovlrdesconto" id="orcamentovlrdesconto" class="form-control form-control-lg border-danger fw-bold text-danger" value="0.00" step="0.01">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-muted">VALOR FINAL:</span>
                        <span id="label_liquido" class="h3 fw-bold text-primary mb-0">R$ 0,00</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-lg fw-bold">FINALIZAR ORÇAMENTO</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalClientes" tabindex="-1">
    <div class="modal-dialog modal-lg border-0">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Buscar Cliente no Banco</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="buscaCliente" class="form-control mb-3" placeholder="Digite o nome para filtrar...">
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
                                data-cpl="<?= htmlspecialchars($c['clientecpl'] ?? '') ?>" 
                                data-obs="<?= htmlspecialchars($c['clienteobs'] ?? '') ?>">
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

// FUNÇÃO PARA LIMPAR TELA CINZA DO BOOTSTRAP
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

// 1. BUSCA WHATSAPP (Gatilho automático)
document.getElementById('clientewhatsapp').addEventListener('blur', async function() {
    const whats = this.value.replace(/\D/g, '');
    if (whats.length >= 10) {
        try {
            const res = await fetch(`buscar_cliente_whats.php?whatsapp=${whats}`);
            const data = await res.json();
            if (data.sucesso) {
                document.getElementById('clienteid').value = data.id;
                document.getElementById('clientenomecompleto').value = data.nome;
                document.getElementById('clientecep').value = data.cep;
                document.getElementById('clientelogradouro').value = data.logradouro;
                document.getElementById('clientebairro').value = data.bairro;
                document.getElementById('clientenumero').value = data.numero;
                document.getElementById('clientecidade').value = data.cidade;
                document.getElementById('clientecpl').value = data.cpl;
                document.getElementById('clienteobs').value = data.obs;
            } else {
                document.getElementById('clienteid').value = ""; // Novo cliente
            }
        } catch(e) { console.error("Erro ao buscar whats"); }
    }
});

// 2. SELEÇÃO DE PRODUTO (Vindo do Modal Externo)
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('selecionarProduto')) {
        const tr = e.target.closest('tr');
        const id = tr.dataset.id;
        const nome = tr.dataset.nome;
        const valorRaw = tr.dataset.preco || tr.dataset.valor || "0";
        const valor = parseFloat(valorRaw.replace(',', '.'));

        const tbody = document.querySelector('#tabelaItens tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><span class="fw-bold">${nome}</span><input type="hidden" name="itens[${index}][id]" value="${id}"><input type="hidden" name="itens[${index}][nome]" value="${nome}"></td>
            <td><input class="form-control form-control-sm valor" name="itens[${index}][valor]" value="${valor.toFixed(2)}" step="0.01" type="number"></td>
            <td class="text-end fw-bold">R$ <span class="total">${valor.toFixed(2).replace('.', ',')}</span></td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger remover"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        index++;
        atualizarTotais();
        fecharModais();
    }
});

// 3. SELEÇÃO DE CLIENTE (Modal Lista)
document.addEventListener('click', function(e) {
    const tr = e.target.closest('.selecionarCliente');
    if(tr){
        const d = tr.dataset;
        document.getElementById('clienteid').value = d.id;
        document.getElementById('clientenomecompleto').value = d.nome;
        document.getElementById('clientewhatsapp').value = d.whatsapp;
        document.getElementById('clientecep').value = d.cep;
        document.getElementById('clientelogradouro').value = d.logradouro;
        document.getElementById('clientenumero').value = d.numero;
        document.getElementById('clientebairro').value = d.bairro;
        document.getElementById('clientecidade').value = d.cidade;
        document.getElementById('clientecpl').value = d.cpl;
        document.getElementById('clienteobs').value = d.obs;
        fecharModais();
    }
});

// BUSCA CEP
document.getElementById('clientecep').addEventListener('blur', async function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (!data.erro) {
            document.getElementById('clientelogradouro').value = data.logradouro;
            document.getElementById('clientebairro').value = data.bairro;
            document.getElementById('clientecidade').value = data.localidade;
        }
    }
});

// CÁLCULOS TOTAIS
function atualizarTotais(){
    let total = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const v = parseFloat(tr.querySelector('.valor').value) || 0;
        tr.querySelector('.total').innerText = v.toLocaleString('pt-br', {minimumFractionDigits: 2});
        total += v;
    });
    const desc = parseFloat(document.getElementById('orcamentovlrdesconto').value) || 0;
    const liq = total - desc;
    document.getElementById('label_liquido').innerText = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('valor_total_topo').value = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

document.addEventListener('input', e => { if(e.target.matches('.valor, #orcamentovlrdesconto')) atualizarTotais(); });
document.addEventListener('click', e => { if(e.target.closest('.remover')) { e.target.closest('tr').remove(); atualizarTotais(); } });

// FILTRO DE BUSCA CLIENTE NO MODAL
document.getElementById('buscaCliente').addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    document.querySelectorAll('#listaClientes tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(valor) ? '' : 'none';
    });
});
</script>

<?php include "../includes/footer.php"; ?>