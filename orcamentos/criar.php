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

        // LÓGICA DO CLIENTE
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

        // INSERE ORÇAMENTO
        $link = md5(uniqid(rand(), true));
        $sql = $pdo->prepare("INSERT INTO orcamento 
            (clienteid, orcamentoprevisaoentrega, orcamentoformapagamento, orcamentolinkaprovacao, orcamentosituacao, orcamentodatacriacao)
            VALUES (?,?,?,?, 'Criado', NOW())");
        $sql->execute([$clienteid, $_POST['orcamentoprevisaoentrega'], $_POST['orcamentoformapagamento'], $link]);
        $orcamentoid = $pdo->lastInsertId();

        // INSERE ITENS (QUANTIDADE SEMPRE 1 CONFORME SOLICITADO)
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

        // DESCONTO GERAL E TOTAL FINAL
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

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    .card-item-mobile { border: 1px solid #dee2e6; border-radius: 10px; padding: 15px; margin-bottom: 10px; background: #fff; }
    @media (max-width: 768px) {
        .table-res-oculta { display: none; }
        .btn-mobile-full { width: 100%; margin-bottom: 10px; }
        .row-total-mobile { flex-direction: column-reverse; }
    }
</style>

<div class="container my-3 my-md-4">
    <?php if($erro): ?><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-x-circle me-2"></i><?= $erro ?></div><?php endif; ?>
    <?php if($sucesso): ?><div class="alert alert-success shadow-sm border-0"><i class="bi bi-check-circle me-2"></i><?= $sucesso ?></div><?php endif; ?>

    <form method="post" id="formOrcamento">
        <div class="card shadow-sm border-0 p-3 p-md-4">
            
            <div class="row mb-3 align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-bold text-primary mb-0">Novo Orçamento</h4>
                </div>
                <div class="col-md-4 text-center d-none d-md-block">
                    <label class="small fw-bold text-muted d-block">TOTAL FINAL</label>
                    <input type="text" id="valor_total_topo" class="form-control form-control-lg text-center fw-bold border-primary bg-light" value="R$ 0,00" readonly>
                </div>
                <div class="col-md-4 text-end">
                    <a href="listar.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
                </div>
            </div>

            <div class="bg-light p-3 rounded-3 mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="text-primary fw-bold mb-0">CLIENTE</h6>
                    <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalClientes">Buscar</button>
                </div>
                <input type="hidden" name="clienteid" id="clienteid">
                <div class="row g-2">
                    <div class="col-md-6"><input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" placeholder="Nome Completo" required></div>
                    <div class="col-md-3"><input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control" placeholder="WhatsApp"></div>
                    <div class="col-md-3"><input type="text" name="clientecep" id="clientecep" class="form-control" placeholder="CEP"></div>
                    <div class="col-md-5"><input name="clientelogradouro" id="clientelogradouro" class="form-control" placeholder="Endereço"></div>
                    <div class="col-md-2"><input name="clientenumero" id="clientenumero" class="form-control" placeholder="Nº"></div>
                    <div class="col-md-5"><input name="clientebairro" id="clientebairro" class="form-control" placeholder="Bairro"></div>
                    <div class="col-12"><textarea name="clienteobs" id="clienteobs" class="form-control" rows="1" placeholder="Observações"></textarea></div>
                </div>
            </div>

            <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">ITENS (QTD 1)</h6>
            
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover border" id="tabelaItens">
                    <thead class="table-light small">
                        <tr>
                            <th>Descrição</th>
                            <th width="200">Valor Unitário</th>
                            <th width="150" class="text-end">Subtotal</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="itensMobile" class="d-md-none"></div>

            <button type="button" class="btn btn-outline-primary fw-bold btn-mobile-full mt-2" data-bs-toggle="modal" data-bs-target="#modalProdutos">
                <i class="bi bi-plus-circle me-1"></i> Adicionar Item
            </button>

            <div class="row mt-4 pt-3 border-top g-3">
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="small fw-bold">PAGAMENTO</label>
                            <select name="orcamentoformapagamento" class="form-select">
                                <option>PIX</option><option>Cartão</option><option>Dinheiro</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">DESCONTO GERAL (R$)</label>
                            <input type="number" name="desconto_geral" id="desconto_geral" class="form-control border-danger" value="0.00" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="mb-2">
                        <span class="text-muted">Subtotal: </span><span id="subtotal_valor" class="fw-bold">R$ 0,00</span>
                    </div>
                    <h3 class="fw-bold text-success">TOTAL: <span id="total_final">R$ 0,00</span></h3>
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow fw-bold btn-mobile-full">SALVAR</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalClientes" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-body">... Carregando clientes ...</div></div></div>
</div>

<?php include "../orcamentos/modal_produtos.php"; ?>

<script>
let index = 0;

// Adicionar Produto
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('selecionarProduto')) {
        const tr = e.target.closest('tr');
        addProduto(tr.dataset.id, tr.dataset.nome);
    }
});

function addProduto(id, nome) {
    const tableBody = document.querySelector('#tabelaItens tbody');
    const mobileDiv = document.querySelector('#itensMobile');

    // HTML para Desktop (Tabela)
    const tr = document.createElement('tr');
    tr.id = `item-${index}`;
    tr.innerHTML = `
        <td><input type="hidden" name="itens[${index}][id]" value="${id}"><input type="hidden" name="itens[${index}][nome]" value="${nome}">${nome}</td>
        <td><input class="form-control valor" name="itens[${index}][valor]" value="0.00" step="0.01" type="number"></td>
        <td class="text-end fw-bold">R$ <span class="total-item">0,00</span></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger" onclick="remover(${index})"><i class="bi bi-trash"></i></button></td>
    `;
    tableBody.appendChild(tr);

    // HTML para Mobile (Card)
    const card = document.createElement('div');
    card.className = 'card-item-mobile shadow-sm';
    card.id = `item-mob-${index}`;
    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="fw-bold small">${nome}</span>
            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="remover(${index})"><i class="bi bi-trash"></i></button>
        </div>
        <div class="row align-items-center">
            <div class="col-7"><label class="small text-muted">Valor Unitário:</label></div>
            <div class="col-5"><input class="form-control form-control-sm valor-mob" data-ref="${index}" value="0.00" step="0.01" type="number"></div>
        </div>
    `;
    mobileDiv.appendChild(card);

    index++;
    atualizarTotais();
}

function remover(idx) {
    document.getElementById(`item-${idx}`).remove();
    document.getElementById(`item-mob-${idx}`).remove();
    atualizarTotais();
}

function atualizarTotais() {
    let subtotal = 0;
    // Sincroniza valores entre mobile e desktop
    document.querySelectorAll('.valor').forEach((input, i) => { subtotal += parseFloat(input.value) || 0; });
    
    const desconto = parseFloat(document.getElementById('desconto_geral').value) || 0;
    const totalFinal = subtotal - desconto;

    document.getElementById('subtotal_valor').innerText = subtotal.toLocaleString('pt-br', {style:'currency', currency:'BRL'});
    document.getElementById('total_final').innerText = totalFinal.toLocaleString('pt-br', {style:'currency', currency:'BRL'});
    document.getElementById('valor_total_topo').value = totalFinal.toLocaleString('pt-br', {style:'currency', currency:'BRL'});
}

// Evento para atualizar quando digitar valores ou desconto
document.addEventListener('input', e => {
    if(e.target.classList.contains('valor-mob')) {
        const idx = e.target.dataset.ref;
        document.querySelector(`#item-${idx} .valor`).value = e.target.value;
    }
    if(e.target.classList.contains('valor') || e.target.id === 'desconto_geral') {
        atualizarTotais();
    }
});

// Busca CEP Automática
document.getElementById('clientecep')?.addEventListener('blur', async function(){
    const cep = this.value.replace(/\D/g,'');
    if(cep.length === 8){
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if(!data.erro){
            document.getElementById('clientelogradouro').value = data.logradouro || '';
            document.getElementById('clientebairro').value = data.bairro || '';
        }
    }
});
</script>