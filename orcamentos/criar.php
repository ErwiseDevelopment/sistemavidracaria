<?php
require_once "../config/database.php";

$erro = "";
$sucesso = "";
$orcamentoid = null;

if ($_POST) {
    try {
        if (empty($_POST['clientenomecompleto'])) throw new Exception("O nome do cliente é obrigatório.");
        if (empty($_POST['itens'])) throw new Exception("Adicione pelo menos um produto.");

        $pdo->beginTransaction();

        // 1. LÓGICA DO CLIENTE (Salva ou Atualiza)
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

        // 2. INSERE ORÇAMENTO MESTRE
        $link = md5(uniqid(rand(), true));
        $sql = $pdo->prepare("INSERT INTO orcamento 
            (clienteid, orcamentoprevisaoentrega, orcamentoformapagamento, orcamentolinkaprovacao, orcamentosituacao, orcamentodatacriacao)
            VALUES (?,?,?,?, 'Criado', NOW())");
        $sql->execute([$clienteid, $_POST['orcamentoprevisaoentrega'], $_POST['orcamentoformapagamento'], $link]);
        $orcamentoid = $pdo->lastInsertId();

        // 3. INSERE ITENS (QUANTIDADE FIXA 1)
        $sqlItem = $pdo->prepare("INSERT INTO orcamentoitem
            (orcamentocodigo, orcamentoitemseq, produtocodigo, produtodescricao, orcamentoqnt, orcamentovalor, orcamentodesconto, orcamentovalortotal)
            VALUES (?,?,?,?,1,?,0,?)");

        $totalItens = 0;
        $seq = 1;
        foreach ($_POST['itens'] ?? [] as $i) {
            if (empty($i['id'])) continue;
            $valor = floatval($i['valor']);
            $sqlItem->execute([$orcamentoid, $seq, $i['id'], $i['nome'], $valor, $valor]);
            $totalItens += $valor;
            $seq++;
        }

        // 4. APLICA DESCONTO GERAL E FINALIZA
        $descontoGeral = floatval($_POST['desconto_geral'] ?? 0);
        $totalFinal = $totalItens - $descontoGeral;

        $pdo->prepare("UPDATE orcamento SET orcamentodesconto=?, orcamentovalortotal=? WHERE orcamentocodigo=?")
            ->execute([$descontoGeral, $totalFinal, $orcamentoid]);

        $pdo->commit();
        $sucesso = "Orçamento #$orcamentoid criado com sucesso!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

// Busca dados para os modais
$clientes = $pdo->query("SELECT * FROM clientes WHERE clientesituacao=1 ORDER BY clientenomecompleto")->fetchAll();
$produtos = $pdo->query("SELECT * FROM produtos WHERE produtosituacao=1 ORDER BY produtonome")->fetchAll();

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    .card-item-mobile { border: 1px solid #dee2e6; border-radius: 10px; padding: 15px; margin-bottom: 10px; background: #fff; position: relative; }
    .bg-custom-light { background-color: #f8f9fc; }
    @media (max-width: 768px) {
        .btn-mobile-full { width: 100%; margin-bottom: 10px; }
    }
</style>

<div class="container my-3 my-md-4">
    <?php if($erro): ?><div class="alert alert-danger border-0 shadow-sm"><?= $erro ?></div><?php endif; ?>
    <?php if($sucesso): ?><div class="alert alert-success border-0 shadow-sm"><?= $sucesso ?></div><?php endif; ?>

    <form method="post" id="formOrcamento">
        <div class="card shadow-sm border-0 p-3 p-md-4">
            
            <div class="row mb-3 align-items-center">
                <div class="col-8">
                    <h4 class="fw-bold text-primary mb-0">Novo Orçamento</h4>
                </div>
                <div class="col-4 text-end">
                    <a href="listar.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
                </div>
            </div>

            <div class="bg-custom-light p-3 rounded-3 mb-4 border">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-primary fw-bold mb-0 text-uppercase small">Dados do Cliente</h6>
                    <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalClientes">
                        <i class="bi bi-search me-1"></i> BUSCAR
                    </button>
                </div>
                
                <input type="hidden" name="clienteid" id="clienteid">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="small fw-bold">NOME</label>
                        <input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">WHATSAPP</label>
                        <input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">CEP</label>
                        <input type="text" name="clientecep" id="clientecep" class="form-control">
                    </div>
                    <div class="col-md-9">
                        <label class="small fw-bold">ENDEREÇO</label>
                        <input name="clientelogradouro" id="clientelogradouro" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Nº</label>
                        <input name="clientenumero" id="clientenumero" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold">OBSERVAÇÕES TÉCNICAS</label>
                        <textarea name="clienteobs" id="clienteobs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <h6 class="text-primary fw-bold border-bottom pb-2 mb-3 text-uppercase small">Itens do Pedido</h6>
            
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover border align-middle" id="tabelaItens">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Descrição do Produto</th>
                            <th width="180">Valor Unitário</th>
                            <th width="150" class="text-end">Subtotal</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="itensMobile" class="d-md-none"></div>

            <button type="button" class="btn btn-outline-primary fw-bold btn-mobile-full mt-2" data-bs-toggle="modal" data-bs-target="#modalProdutos">
                <i class="bi bi-plus-circle me-1"></i> ADICIONAR PRODUTO
            </button>

            <div class="row mt-4 pt-3 border-top g-3">
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="small fw-bold">PAGAMENTO</label>
                            <select name="orcamentoformapagamento" class="form-select">
                                <option>PIX</option><option>Cartão</option><option>Dinheiro</option><option>Parcelado</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-danger">DESCONTO (R$)</label>
                            <input type="number" name="desconto_geral" id="desconto_geral" class="form-control border-danger fw-bold" value="0.00" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="mb-1 text-muted small">Subtotal: <span id="subtotal_valor">R$ 0,00</span></div>
                    <h2 class="fw-bold text-success mb-3">TOTAL: <span id="total_final">R$ 0,00</span></h2>
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow fw-bold btn-mobile-full">SALVAR ORÇAMENTO</button>
                </div>
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
                <input type="text" id="buscaCliente" class="form-control mb-3" placeholder="Filtrar por nome...">
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-sm table-hover border">
                        <thead class="table-light sticky-top">
                            <tr><th>Nome</th><th>WhatsApp</th><th class="text-end">Ação</th></tr>
                        </thead>
                        <tbody id="corpoListaClientes">
                            <?php foreach($clientes as $c): ?>
                            <tr class="item-cliente-busca" 
                                data-id="<?= $c['clientecodigo'] ?>"
                                data-nome="<?= htmlspecialchars($c['clientenomecompleto']) ?>"
                                data-whatsapp="<?= $c['clientewhatsapp'] ?>"
                                data-cep="<?= $c['clientecep'] ?>"
                                data-logradouro="<?= htmlspecialchars($c['clientelogradouro']) ?>"
                                data-numero="<?= $c['clientenumero'] ?>"
                                data-obs="<?= htmlspecialchars($c['clienteobs']) ?>">
                                <td><?= htmlspecialchars($c['clientenomecompleto']) ?></td>
                                <td><?= $c['clientewhatsapp'] ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-primary btn-sm btnSelecionarCliente">Selecionar</button>
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

<div class="modal fade" id="modalProdutos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Escolher Produto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="buscaProd" class="form-control mb-3" placeholder="O que você procura?">
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-sm table-hover border">
                        <thead class="table-light sticky-top">
                            <tr><th>Descrição</th><th class="text-end">Ação</th></tr>
                        </thead>
                        <tbody id="corpoListaProdutos">
                            <?php foreach($produtos as $p): ?>
                            <tr class="item-produto-busca" data-id="<?= $p['produtocodigo'] ?>" data-nome="<?= htmlspecialchars($p['produtonome']) ?>">
                                <td><?= htmlspecialchars($p['produtonome']) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm btnAddProd">Adicionar</button>
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

<script>
let index = 0;

// 1. SELEÇÃO DE CLIENTE
document.querySelectorAll('.btnSelecionarCliente').forEach(btn => {
    btn.addEventListener('click', function() {
        const tr = this.closest('tr');
        document.getElementById('clienteid').value = tr.dataset.id;
        document.getElementById('clientenomecompleto').value = tr.dataset.nome;
        document.getElementById('clientewhatsapp').value = tr.dataset.whatsapp;
        document.getElementById('clientecep').value = tr.dataset.cep;
        document.getElementById('clientelogradouro').value = tr.dataset.logradouro;
        document.getElementById('clientenumero').value = tr.dataset.numero;
        document.getElementById('clienteobs').value = tr.dataset.obs;
        bootstrap.Modal.getInstance(document.getElementById('modalClientes')).hide();
    });
});

// 2. SELEÇÃO DE PRODUTO
document.querySelectorAll('.btnAddProd').forEach(btn => {
    btn.addEventListener('click', function() {
        const tr = this.closest('tr');
        addProduto(tr.dataset.id, tr.dataset.nome);
        bootstrap.Modal.getInstance(document.getElementById('modalProdutos')).hide();
    });
});

function addProduto(id, nome) {
    // Tabela Desktop
    const tbody = document.querySelector('#tabelaItens tbody');
    const tr = document.createElement('tr');
    tr.id = `item-row-${index}`;
    tr.innerHTML = `
        <td>
            <input type="hidden" name="itens[${index}][id]" value="${id}">
            <input type="hidden" name="itens[${index}][nome]" value="${nome}">
            <span class="fw-bold">${nome}</span>
        </td>
        <td><input type="number" name="itens[${index}][valor]" class="form-control valor-input" value="0.00" step="0.01" oninput="atualizarTotais()"></td>
        <td class="text-end fw-bold">R$ <span class="subtotal-item">0,00</span></td>
        <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="removerItem(${index})"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);

    // Card Mobile
    const mobContainer = document.getElementById('itensMobile');
    const card = document.createElement('div');
    card.id = `item-card-${index}`;
    card.className = 'card-item-mobile shadow-sm border';
    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="fw-bold small text-primary">${nome}</span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removerItem(${index})"><i class="bi bi-trash"></i></button>
        </div>
        <div class="row align-items-center">
            <div class="col-6 small text-muted">Preço do Item:</div>
            <div class="col-6 text-end">
                <input type="number" class="form-control form-control-sm valor-mob-ref" data-target="${index}" value="0.00" step="0.01" oninput="sincronizar(this)">
            </div>
        </div>
    `;
    mobContainer.appendChild(card);

    index++;
    atualizarTotais();
}

function sincronizar(el) {
    const idx = el.dataset.target;
    document.querySelector(`#item-row-${idx} .valor-input`).value = el.value;
    atualizarTotais();
}

function removerItem(idx) {
    document.getElementById(`item-row-${idx}`).remove();
    document.getElementById(`item-card-${idx}`).remove();
    atualizarTotais();
}

function atualizarTotais() {
    let subtotalGeral = 0;
    document.querySelectorAll('.valor-input').forEach(input => {
        const v = parseFloat(input.value) || 0;
        input.closest('tr').querySelector('.subtotal-item').innerText = v.toLocaleString('pt-br', {minimumFractionDigits: 2});
        subtotalGeral += v;
    });

    const desconto = parseFloat(document.getElementById('desconto_geral').value) || 0;
    const final = subtotalGeral - desconto;

    document.getElementById('subtotal_valor').innerText = subtotalGeral.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    document.getElementById('total_final').innerText = final.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

document.getElementById('desconto_geral').addEventListener('input', atualizarTotais);

// FILTROS DOS MODAIS
document.getElementById('buscaCliente').addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    document.querySelectorAll('.item-cliente-busca').forEach(tr => {
        tr.style.display = tr.dataset.nome.toLowerCase().includes(termo) ? '' : 'none';
    });
});

document.getElementById('buscaProd').addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    document.querySelectorAll('.item-produto-busca').forEach(tr => {
        tr.style.display = tr.dataset.nome.toLowerCase().includes(termo) ? '' : 'none';
    });
});
</script>

</body>
</html>