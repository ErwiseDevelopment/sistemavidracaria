<?php
// 1. PROCESSAMENTO PHP (Sempre no topo antes de qualquer HTML)
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

        // INSERE ITENS
        $sqlItem = $pdo->prepare("INSERT INTO orcamentoitem
            (orcamentocodigo, orcamentoitemseq, produtocodigo, produtodescricao, orcamentoqnt, orcamentovalor, orcamentodesconto, orcamentovalortotal)
            VALUES (?,?,?,?,?,?,?,?)");

        $totalOrcamento = 0;
        $seq = 1;
        foreach ($_POST['itens'] ?? [] as $i) {
            if (empty($i['id'])) continue;
            $qnt = floatval($i['qnt']);
            $valor = floatval($i['valor']);
            $desconto = floatval($i['desconto']);
            $totalItem = ($qnt * $valor) - $desconto;

            $sqlItem->execute([$orcamentoid, $seq, $i['id'], $i['nome'], $qnt, $valor, $desconto, $totalItem]);
            $totalOrcamento += $totalItem;
            $seq++;
        }

        // ATUALIZA TOTAL FINAL
        $pdo->prepare("UPDATE orcamento SET orcamentovalortotal=? WHERE orcamentocodigo=?")->execute([$totalOrcamento, $orcamentoid]);

        $pdo->commit();
        $sucesso = "Orçamento #$orcamentoid criado com sucesso!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

// BUSCA PRODUTOS (Para redundância caso o modal externo falhe)
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY produtonome")->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<div class="container my-4">
    <?php if($erro): ?><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-x-circle me-2"></i><?= $erro ?></div><?php endif; ?>
    <?php if($sucesso): ?><div class="alert alert-success shadow-sm border-0"><i class="bi bi-check-circle me-2"></i><?= $sucesso ?></div><?php endif; ?>

    <form method="post" id="formOrcamento" class="needs-validation" novalidate>
        <div class="card shadow-sm border-0 p-4">
            
            <div class="row mb-4 align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-bold text-primary mb-0">Novo Orçamento</h4>
                    <span class="text-muted small">Preencha os dados abaixo para gerar</span>
                </div>
                <div class="col-md-4 text-md-center mt-3 mt-md-0">
                    <label class="small fw-bold text-muted d-block text-uppercase">Total Previsto</label>
                    <input type="text" id="valor_total_topo" class="form-control form-control-lg text-center fw-bold border-primary text-primary bg-light" value="R$ 0,00" readonly>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="listar.php" class="btn btn-outline-secondary px-4">Voltar</a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Previsão de Instalação</label>
                    <input type="datetime-local" name="orcamentoprevisaoentrega" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Condição de Pagamento</label>
                    <select name="orcamentoformapagamento" class="form-select">
                        <?php foreach(['Débito','Crédito','PIX','Dinheiro','Parcelado','Outros'] as $p): ?>
                            <option <?= ($_POST['orcamentoformapagamento']??'')==$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="text-primary fw-bold text-uppercase mb-0">Informações do Cliente</h6>
                <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalClientes">
                    <i class="bi bi-search me-1"></i> Buscar Cliente
                </button>
            </div>

            <input type="hidden" name="clienteid" id="clienteid">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="small fw-bold">Nome Completo</label>
                    <input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" placeholder="Obrigatório" required>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold">WhatsApp</label>
                    <input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control" placeholder="(00) 00000-0000">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold">CEP</label>
                    <input type="text" name="clientecep" id="clientecep" class="form-control" placeholder="00000-000">
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="small fw-bold">Logradouro</label>
                    <input name="clientelogradouro" id="clientelogradouro" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="small fw-bold">Nº</label>
                    <input name="clientenumero" id="clientenumero" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Cidade</label>
                    <input name="clientecidade" id="clientecidade" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Bairro</label>
                    <input name="clientebairro" id="clientebairro" class="form-control">
                </div>
                                <div class="col-md-4">
                    <label class="small fw-bold">Complemento</label>
                    <input name="clientecpl" id="clientecpl" class="form-control">
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-12">
                    <label class="small fw-bold">Observações / Detalhes Técnicos</label>
                    <textarea name="clienteobs" id="clienteobs" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <h6 class="text-primary fw-bold text-uppercase small border-bottom pb-2 mb-3">Produtos do Orçamento</h6>
            <div class="table-responsive">
                <table class="table table-hover border align-middle" id="tabelaItens">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th>Descrição do Produto</th>
                            <th width="100">Qtd</th>
                            <th width="140">Unitário</th>
                            <th width="140">Desconto</th>
                            <th width="140" class="text-end">Subtotal</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="button" class="btn btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalProdutos">
                    <i class="bi bi-plus-circle me-1"></i> Adicionar Produto
                </button>
                <button type="submit" class="btn btn-success btn-lg px-5 shadow fw-bold">
                    SALVAR ORÇAMENTO
                </button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalClientes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Selecionar Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="buscaCliente" class="form-control mb-3" placeholder="Filtrar por nome ou celular...">
        <div class="table-responsive" style="max-height: 400px;">
            <table class="table table-hover border" id="listaClientes">
                <thead class="table-light sticky-top">
                    <tr><th>Nome</th><th>WhatsApp</th><th class="text-end">Ação</th></tr>
                </thead>
                <tbody>
                    <?php
                    $clientes = $pdo->query("SELECT * FROM clientes WHERE clientesituacao=1 ORDER BY clientenomecompleto")->fetchAll();
                    foreach($clientes as $c):
                    ?>
                    <tr data-id="<?= $c['clientecodigo'] ?>"
                        data-nomecompleto="<?= htmlspecialchars($c['clientenomecompleto']) ?>"
                        data-whatsapp="<?= $c['clientewhatsapp'] ?>"
                        data-cep="<?= $c['clientecep'] ?>"
                        data-logradouro="<?= htmlspecialchars($c['clientelogradouro']) ?>"
                        data-numero="<?= $c['clientenumero'] ?>"
                        data-cpl="<?= htmlspecialchars($c['clientecpl'] ?? '') ?>"
                        data-bairro="<?= htmlspecialchars($c['clientebairro']) ?>"
                        data-cidade="<?= htmlspecialchars($c['clientecidade'] ?? '') ?>"
                        data-obs="<?= htmlspecialchars($c['clienteobs'] ?? '') ?>">
                        <td class="fw-bold"><?= htmlspecialchars($c['clientenomecompleto']) ?></td>
                        <td><?= htmlspecialchars($c['clientewhatsapp']) ?></td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-primary selecionarCliente">Escolher</button></td>
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

// INTEGRAÇÃO COM MODAL_PRODUTOS.PHP (Classe selecionarProduto)
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
            <span class="fw-bold text-dark">${nome}</span>
            <input type="hidden" name="itens[${index}][id]" value="${id}">
            <input type="hidden" name="itens[${index}][nome]" value="${nome}">
        </td>
        <td><input class="form-control qtd" name="itens[${index}][qnt]" value="1" min="1" type="number"></td>
        <td><input class="form-control valor" name="itens[${index}][valor]" value="0.00" step="0.01" type="number"></td>
        <td><input class="form-control desconto" name="itens[${index}][desconto]" value="0.00" step="0.01" type="number"></td>
        <td class="text-end fw-bold">R$ <span class="total">0,00</span></td>
        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remover"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    index++;
    atualizarTotais();
    bootstrap.Modal.getInstance(document.getElementById('modalProdutos')).hide();
}

function atualizarTotais(){
    let totalGeral = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr=>{
        const q = parseFloat(tr.querySelector('.qtd').value) || 0;
        const v = parseFloat(tr.querySelector('.valor').value) || 0;
        const d = parseFloat(tr.querySelector('.desconto').value) || 0;
        const t = (q * v) - d;
        tr.querySelector('.total').innerText = t.toLocaleString('pt-br', {minimumFractionDigits: 2});
        totalGeral += t;
    });
    document.getElementById('valor_total_topo').value = totalGeral.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

// EVENTOS DE DIGITAÇÃO E REMOÇÃO
document.addEventListener('input', e => { if(e.target.matches('.qtd, .valor, .desconto')) atualizarTotais(); });
document.addEventListener('click', e => { if(e.target.closest('.remover')) { e.target.closest('tr').remove(); atualizarTotais(); } });

// SELEÇÃO DE CLIENTE
document.querySelectorAll('.selecionarCliente').forEach(btn=>{
    btn.addEventListener('click', function(){
        const tr = this.closest('tr');
        const campos = ['id','nomecompleto','whatsapp','cep','logradouro','numero','cpl','bairro','cidade','obs'];
        campos.forEach(c => {
            const el = document.getElementById('cliente' + c);
            if(el) el.value = tr.dataset[c] || '';
        });
        bootstrap.Modal.getInstance(document.getElementById('modalClientes')).hide();
    });
});

// BUSCA CEP
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

// FILTRO DE CLIENTES NO MODAL
document.getElementById('buscaCliente')?.addEventListener('input', function(){
    const f = this.value.toLowerCase();
    document.querySelectorAll('#listaClientes tbody tr').forEach(tr=>{
        tr.style.display = (tr.dataset.nome.toLowerCase().includes(f) || tr.dataset.whatsapp.includes(f)) ? '' : 'none';
    });
});
</script>

</body>
</html>