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

// Buscar orçamento e cliente
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

// Buscar itens
$itensRaw = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=?");
$itensRaw->execute([$orcamentoid]);
$itens = $itensRaw->fetchAll();

if($_POST && !$bloqueado){
    try {
        $pdo->beginTransaction();

        // ===== CLIENTE =====
        $clienteid = $_POST['clienteid'];
        $sql = $pdo->prepare("UPDATE clientes SET clientenomecompleto=?, clientewhatsapp=?, clientecep=?, clientelogradouro=?, clientenumero=?, clientecpl=?, clientebairro=?, clientecidade=?, clienteobs=? WHERE clientecodigo=?");
        $sql->execute([
            $_POST['clientenomecompleto'], $_POST['clientewhatsapp'], $_POST['clientecep'],
            $_POST['clientelogradouro'], $_POST['clientenumero'], $_POST['clientecpl'],
            $_POST['clientebairro'], $_POST['clientecidade'], $_POST['clienteobs'], $clienteid
        ]);

        // ===== ORÇAMENTO =====
        $previsao = $_POST['orcamentoprevisaoentrega'];
        $formapagamento = $_POST['orcamentoformapagamento'];
        $situacaoNova = $_POST['orcamentosituacao'];

        $pdo->prepare("UPDATE orcamento SET orcamentoprevisaoentrega=?, orcamentoformapagamento=?, orcamentosituacao=? WHERE orcamentocodigo=?")
            ->execute([$previsao, $formapagamento, $situacaoNova, $orcamentoid]);

        // ===== ITENS (Sincronização) =====
        $pdo->prepare("DELETE FROM orcamentoitem WHERE orcamentocodigo=?")->execute([$orcamentoid]);
        $itensPost = $_POST['itens'] ?? [];
        $sqlItem = $pdo->prepare("INSERT INTO orcamentoitem (orcamentocodigo, orcamentoitemseq, produtocodigo, produtodescricao, orcamentoqnt, orcamentovalor, orcamentodesconto, orcamentovalortotal) VALUES (?,?,?,?,?,?,?,?)");

        $totalOrcamento = 0;
        $seqItem = 1;
        foreach($itensPost as $i){
            if(empty($i['id'])) continue;
            $tItem = (floatval($i['qnt']) * floatval($i['valor'])) - floatval($i['desconto']);
            $sqlItem->execute([$orcamentoid, $seqItem, $i['id'], $i['nome'], $i['qnt'], $i['valor'], $i['desconto'], $tItem]);
            $totalOrcamento += $tItem;
            $seqItem++;
        }

        $pdo->prepare("UPDATE orcamento SET orcamentovalortotal=? WHERE orcamentocodigo=?")->execute([$totalOrcamento, $orcamentoid]);

        // ===== GERAR PEDIDO SE APROVADO =====
        if($statusAnterior !== 'Aprovado' && $situacaoNova === 'Aprovado'){
            $sqlPed = $pdo->prepare("INSERT INTO pedido (clienteid, pedidoprevisaoentrega, pedidoformapagamento, pedidosituacao, pedidototal, pedidodatacriacao) VALUES (?,?,?,?,?, NOW())");
            $sqlPed->execute([$clienteid, $previsao, $formapagamento, 'Criado', $totalOrcamento]);
            $pedidoid = $pdo->lastInsertId();

            $sqlItP = $pdo->prepare("INSERT INTO pedidoitem (pedidocodigo, pedidoitemseq, produtocodigo, produtodescricao, pedidoqnt, pedidovalor, pedidodesconto, pedidovalortotal) VALUES (?,?,?,?,?,?,?,?)");
            foreach($itensPost as $idx => $i){
                $tI = (floatval($i['qnt']) * floatval($i['valor'])) - floatval($i['desconto']);
                $sqlItP->execute([$pedidoid, $idx+1, $i['id'], $i['nome'], $i['qnt'], $i['valor'], $i['desconto'], $tI]);
            }
        }

        $pdo->commit();
        header("Location: editar.php?id=$orcamentoid&sucesso=1");
        exit;
    } catch(Exception $e){
        $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<div class="container my-4">
    <?php if(isset($_GET['sucesso'])): ?><div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>Alterações salvas com sucesso!</div><?php endif; ?>
    <?php if($erro): ?><div class="alert alert-danger border-0 shadow-sm"><?= $erro ?></div><?php endif; ?>

    <form method="post" id="formOrcamento">
        <div class="card shadow-sm border-0 p-4">
            
            <div class="row mb-4 align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-bold text-primary mb-0">Orçamento #<?= $orcamentoid ?></h4>
                    <span class="badge <?= $bloqueado ? 'bg-success' : 'bg-warning' ?>"><?= $situacao ?></span>
                </div>
                <div class="col-md-4 text-md-center mt-3 mt-md-0">
                    <label class="small fw-bold text-muted d-block text-uppercase">Valor Total</label>
                    <input type="text" id="valor_total_topo" class="form-control form-control-lg text-center fw-bold border-primary text-primary bg-light" value="R$ <?= number_format($orcamento['orcamentovalortotal'], 2, ',', '.') ?>" readonly>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                   <div class="btn-group">
    <?php if($orcamento['clientewhatsapp'] && $orcamento['orcamentolinkaprovacao']): 
        // Limpa o número para o WhatsApp
        $numeroWA = preg_replace('/\D/', '', $orcamento['clientewhatsapp']);
        
        // Monta o link público usando a constante BASE_URL do seu config.php
        $linkPublico = BASE_URL . "/public/orcamento.php?c=" . $orcamento['orcamentolinkaprovacao'];
        
        // Monta a mensagem para o WhatsApp
        $textoMensagem = "Olá, segue o link do seu orçamento: " . $linkPublico;
        $linkWA = "https://wa.me/{$numeroWA}?text=" . urlencode($textoMensagem);
    ?>
        <a href="<?= $linkWA ?>" target="_blank" class="btn btn-success">
            <i class="bi bi-whatsapp"></i> Enviar Orçamento
        </a>
    <?php endif; ?>
    
    <a href="listar.php" class="btn btn-outline-secondary">Voltar</a>
</div>
                </div>
            </div>

            <div class="row g-3 mb-4 p-3 bg-light rounded">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Previsão Instalação</label>
                    <input type="datetime-local" name="orcamentoprevisaoentrega" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($orcamento['orcamentoprevisaoentrega'])) ?>" <?= $bloqueado?'readonly':'' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Pagamento</label>
                    <select name="orcamentoformapagamento" class="form-select" <?= $bloqueado?'disabled':'' ?>>
                        <?php foreach(['Débito','Crédito','PIX','Parcelado','Outros'] as $f): ?>
                            <option <?= ($orcamento['orcamentoformapagamento']==$f)?'selected':'' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Situação</label>
                    <select name="orcamentosituacao" class="form-select fw-bold <?= $bloqueado?'text-success':'' ?>" <?= $bloqueado?'disabled':'' ?>>
                        <?php foreach(['Criado','Aguardando Retorno Cliente','Cancelado','Aprovado'] as $s): ?>
                            <option <?= ($orcamento['orcamentosituacao']==$s)?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="text-primary fw-bold text-uppercase mb-0">Dados do Cliente</h6>
                <?php if(!$bloqueado): ?>
                    <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalClientes">Trocar Cliente</button>
                <?php endif; ?>
            </div>

            <input type="hidden" name="clienteid" id="clienteid" value="<?= $orcamento['clienteid'] ?>">
            <div class="row g-2 mb-2">
                <div class="col-md-6"><label class="small fw-bold">Nome</label><input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" value="<?= htmlspecialchars($orcamento['clientenomecompleto']) ?>" <?= $bloqueado?'readonly':'' ?>></div>
                <div class="col-md-3"><label class="small fw-bold">WhatsApp</label><input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control" value="<?= htmlspecialchars($orcamento['clientewhatsapp']) ?>" <?= $bloqueado?'readonly':'' ?>></div>
                <div class="col-md-3"><label class="small fw-bold">CEP</label><input type="text" name="clientecep" id="clientecep" class="form-control" value="<?= $orcamento['clientecep'] ?>" <?= $bloqueado?'readonly':'' ?>></div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-5"><label class="small fw-bold">Rua</label><input type="text" name="clientelogradouro" id="clientelogradouro" class="form-control" value="<?= htmlspecialchars($orcamento['clientelogradouro']) ?>" <?= $bloqueado?'readonly':'' ?>></div>
                <div class="col-md-2"><label class="small fw-bold">Nº</label><input type="text" name="clientenumero" id="clientenumero" class="form-control" value="<?= $orcamento['clientenumero'] ?>" <?= $bloqueado?'readonly':'' ?>></div>
                <div class="col-md-2"><label class="small fw-bold">Cidade</label><input type="text" name="clientecidade" id="clientecidade" class="form-control" value="<?= htmlspecialchars($orcamento['clientecidade']) ?>" <?= $bloqueado?'readonly':'' ?>></div>
                <div class="col-md-3"><label class="small fw-bold">Bairro</label><input type="text" name="clientebairro" id="clientebairro" class="form-control" value="<?= htmlspecialchars($orcamento['clientebairro']) ?>" <?= $bloqueado?'readonly':'' ?>></div>
            </div>
            <div class="row mb-4">
                <div class="col-md-12"><label class="small fw-bold">Observações</label><input type="text" name="clienteobs" id="clienteobs" class="form-control" value="<?= htmlspecialchars($orcamento['clienteobs']) ?>" <?= $bloqueado?'readonly':'' ?>></div>
            </div>

            <h6 class="text-primary fw-bold text-uppercase small border-bottom pb-2 mb-3">Itens do Orçamento</h6>
            <div class="table-responsive">
                <table class="table table-hover border align-middle" id="tabelaItens">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th>Produto</th>
                            <th width="100">Qtd</th>
                            <th width="130">Valor</th>
                            <th width="130">Desc.</th>
                            <th width="130" class="text-end">Total</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($itens as $index => $item): ?>
                        <tr>
                            <td>
                                <span class="fw-bold"><?= htmlspecialchars($item['produtodescricao']) ?></span>
                                <input type="hidden" name="itens[<?= $index ?>][id]" value="<?= $item['produtocodigo'] ?>">
                                <input type="hidden" name="itens[<?= $index ?>][nome]" value="<?= htmlspecialchars($item['produtodescricao']) ?>">
                            </td>
                            <td><input type="number" name="itens[<?= $index ?>][qnt]" value="<?= $item['orcamentoqnt'] ?>" class="form-control qtdItem" min="1" <?= $bloqueado?'readonly':'' ?>></td>
                            <td><input type="number" name="itens[<?= $index ?>][valor]" value="<?= $item['orcamentovalor'] ?>" class="form-control valorItem" step="0.01" <?= $bloqueado?'readonly':'' ?>></td>
                            <td><input type="number" name="itens[<?= $index ?>][desconto]" value="<?= $item['orcamentodesconto'] ?>" class="form-control descontoItem" step="0.01" <?= $bloqueado?'readonly':'' ?>></td>
                            <td class="text-end fw-bold text-primary">R$ <span class="totalItem"><?= number_format($item['orcamentovalortotal'], 2, ',', '.') ?></span></td>
                            <td class="text-center">
                                <?php if(!$bloqueado): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm removerItem"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if(!$bloqueado): ?>
            <div class="d-flex justify-content-between mt-3">
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalProdutos"><i class="bi bi-plus-circle me-1"></i> Adicionar Produto</button>
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow fw-bold text-uppercase">Salvar Alterações</button>
            </div>
            <?php else: ?>
                <div class="alert alert-info mt-3 border-0"><i class="bi bi-info-circle me-2"></i>Este orçamento está <b>Aprovado</b> e não pode mais ser editado.</div>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include 'modal_clientes.php'; ?>
<?php include '../orcamentos/modal_produtos.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function atualizarTotal() {
    let totalGeral = 0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const q = parseFloat(tr.querySelector('.qtdItem')?.value) || 0;
        const v = parseFloat(tr.querySelector('.valorItem')?.value) || 0;
        const d = parseFloat(tr.querySelector('.descontoItem')?.value) || 0;
        const total = (q * v) - d;
        if(tr.querySelector('.totalItem')) tr.querySelector('.totalItem').textContent = total.toLocaleString('pt-br', {minimumFractionDigits: 2});
        totalGeral += total;
    });
    const cTopo = document.getElementById('valor_total_topo');
    if(cTopo) cTopo.value = totalGeral.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

document.getElementById('tabelaItens').addEventListener('input', e => {
    if(e.target.matches('.qtdItem, .valorItem, .descontoItem')) atualizarTotal();
});

document.getElementById('tabelaItens').addEventListener('click', e => {
    if(e.target.closest('.removerItem')){
        e.target.closest('tr').remove();
        atualizarTotal();
    }
});

// INTEGRAÇÃO COM MODAL DE PRODUTOS
document.addEventListener('click', e => {
    if(e.target.classList.contains('selecionarProduto')){
        const trModal = e.target.closest('tr');
        const id = trModal.dataset.id;
        const nome = trModal.dataset.nome;
        const valor = parseFloat(trModal.dataset.valor || 0);

        const tbody = document.querySelector('#tabelaItens tbody');
        const index = tbody.querySelectorAll('tr').length;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><span class="fw-bold">${nome}</span><input type="hidden" name="itens[${index}][id]" value="${id}"><input type="hidden" name="itens[${index}][nome]" value="${nome}"></td>
            <td><input type="number" name="itens[${index}][qnt]" value="1" class="form-control qtdItem" min="1"></td>
            <td><input type="number" name="itens[${index}][valor]" value="${valor.toFixed(2)}" class="form-control valorItem" step="0.01"></td>
            <td><input type="number" name="itens[${index}][desconto]" value="0.00" class="form-control descontoItem" step="0.01"></td>
            <td class="text-end fw-bold text-primary">R$ <span class="totalItem">${valor.toFixed(2).replace('.', ',')}</span></td>
            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm removerItem"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        atualizarTotal();
        bootstrap.Modal.getInstance(document.getElementById('modalProdutos')).hide();
    }
});

// Seleção de cliente corrigida
document.querySelectorAll('.selecionarCliente').forEach(btn => {
    btn.addEventListener('click', function() {
        const tr = this.closest('tr');
        document.getElementById('clienteid').value = tr.dataset.id;
        document.getElementById('clientenomecompleto').value = tr.dataset.nome;
        document.getElementById('clientewhatsapp').value = tr.dataset.whatsapp;
        document.getElementById('clientecep').value = tr.dataset.cep;
        document.getElementById('clientelogradouro').value = tr.dataset.logradouro;
        document.getElementById('clientenumero').value = tr.dataset.numero;
        document.getElementById('clientebairro').value = tr.dataset.bairro;
        document.getElementById('clientecidade').value = tr.dataset.cidade;
        document.getElementById('clienteobs').value = tr.dataset.obs;
        bootstrap.Modal.getInstance(document.getElementById('modalClientes')).hide();
    });
});

document.addEventListener('DOMContentLoaded', atualizarTotal);
</script>