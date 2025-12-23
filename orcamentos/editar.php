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

// 1. BUSCA DADOS COMPLETOS
$sql = $pdo->prepare("SELECT o.*, c.* FROM orcamento o JOIN clientes c ON o.clienteid = c.clientecodigo WHERE orcamentocodigo=?");
$sql->execute([$orcamentoid]);
$orcamento = $sql->fetch();
if(!$orcamento){
    header("Location: listar.php");
    exit;
}

// 1.1 INICIALIZA VARIÁVEIS PARA EVITAR WARNINGS
$statusAnterior = $orcamento['orcamentosituacao'];
$situacaoNova = $_POST['orcamentosituacao'] ?? $statusAnterior;
$situacao = $orcamento['orcamentosituacao'];
$bloqueado = ($situacao === 'Aprovado');

// 2. BUSCAR ITENS
$itensRaw = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=? ORDER BY orcamentoitemseq ASC");
$itensRaw->execute([$orcamentoid]);
$itens = $itensRaw->fetchAll();

// 3. LÓGICA WHATSAPP
$linkWA = "";
if($orcamento['clientewhatsapp'] && ($orcamento['orcamentolinkaprovacao'] ?? '')){
    $numeroWA = preg_replace('/\D/', '', $orcamento['clientewhatsapp']);
    $linkPublico = BASE_URL . "/public/orcamento.php?c=" . $orcamento['orcamentolinkaprovacao'];
    $textoMensagem = "Olá, segue o link do seu orçamento: " . $linkPublico;
    $linkWA = "https://wa.me/{$numeroWA}?text=" . urlencode($textoMensagem);
}

// 4. PROCESSAMENTO DO POST
if($_POST && !$bloqueado){
    try {
        $pdo->beginTransaction();
        
        // Captura dados do POST
        $statusAnterior = $orcamento['orcamentosituacao'];
        $situacaoNova = $_POST['orcamentosituacao'];
        $clienteid = $_POST['clienteid'];
        $previsao = $_POST['orcamentoprevisaoentrega'];
        $formapagamento = $_POST['orcamentoformapagamento'];
        $descontoGeral = floatval($_POST['orcamentovlrdesconto'] ?? 0);
        $orcamento_obs = $_POST['orcamento_obs'] ?? '';

        // ATUALIZA CLIENTE
        $sqlC = $pdo->prepare("UPDATE clientes SET clientenomecompleto=?, clientewhatsapp=?, clientecep=?, clientelogradouro=?, clientenumero=?, clientecpl=?, clientebairro=?, clientecidade=? WHERE clientecodigo=?");
        $sqlC->execute([
            $_POST['clientenomecompleto'], $_POST['clientewhatsapp'], $_POST['clientecep'],
            $_POST['clientelogradouro'], $_POST['clientenumero'], $_POST['clientecpl'],
            $_POST['clientebairro'], $_POST['clientecidade'], $clienteid
        ]);

        // ATUALIZA ITENS
        $pdo->prepare("DELETE FROM orcamentoitem WHERE orcamentocodigo=?")->execute([$orcamentoid]);
        $itensPost = $_POST['itens'] ?? [];
        $totalBruto = 0;
        
        $sqlItemOrc = $pdo->prepare("INSERT INTO orcamentoitem (orcamentocodigo, orcamentoitemseq, produtocodigo, produtodescricao, largura, altura, m2, orcamentoqnt, orcamentovalor, orcamentodesconto, orcamentovalortotal) VALUES (?,?,?,?,?,?,?,?,?,?,?)");

        foreach($itensPost as $idx => $i){
            $larg = floatval($i['largura']);
            $alt = floatval($i['altura']);
            $vlrDigitado = floatval($i['valor']);
            $m2 = ($larg > 0 && $alt > 0) ? ($larg * $alt / 1000000) : 0;
            $subtotal = $vlrDigitado;
            
            $sqlItemOrc->execute([$orcamentoid, $idx+1, $i['id'], $i['nome'], $larg, $alt, $m2, 1, $vlrDigitado, 0, $subtotal]);
            $totalBruto += $subtotal;
        }

        $totalLiquido = $totalBruto - $descontoGeral;

        // ATUALIZA CABEÇALHO DO ORÇAMENTO
        $sqlO = $pdo->prepare("UPDATE orcamento SET orcamentoprevisaoentrega=?, orcamentoformapagamento=?, orcamentosituacao=?, orcamentovlrdesconto=?, orcamentovalortotal=?, orcamento_obs=? WHERE orcamentocodigo=?");
        $sqlO->execute([$previsao, $formapagamento, $situacaoNova, $descontoGeral, $totalLiquido, $orcamento_obs, $orcamentoid]);

        // GERA PEDIDO SE APROVADO AGORA
        if($statusAnterior !== 'Aprovado' && $situacaoNova === 'Aprovado'){
            // AJUSTADO: 9 colunas e 9 parâmetros (incluindo o ? para pedido_obs)
            $sqlPed = $pdo->prepare("INSERT INTO pedido (clienteid, pedidoprevisaoentrega, pedidoformapagamento, pedidosituacao, pedidototal, pedidovlrdesconto, pedidodatacriacao, orcamentocodigo, pedido_obs) VALUES (?,?,?,?,?,?, NOW(), ?, ?)");
            
            $sqlPed->execute([
                $clienteid, 
                $previsao, 
                $formapagamento, 
                'Criado', 
                $totalLiquido, 
                $descontoGeral, 
                $orcamentoid,
                $orcamento_obs // Agora a observação é passada corretamente
            ]);
            
            $pedidoid = $pdo->lastInsertId();

            $sqlItP = $pdo->prepare("INSERT INTO pedidoitem (pedidocodigo, pedidoitemseq, produtocodigo, produtodescricao, largura, altura, m2, pedidoqnt, pedidovalor, pedidodesconto, pedidovalortotal) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            foreach($itensPost as $idx => $i){
                $larg = floatval($i['largura']);
                $alt = floatval($i['altura']);
                $vlrDigitado = floatval($i['valor']);
                $m2 = ($larg > 0 && $alt > 0) ? ($larg * $alt / 1000000) : 0;
                $sqlItP->execute([$pedidoid, $idx+1, $i['id'], $i['nome'], $larg, $alt, $m2, 1, $vlrDigitado, 0, $vlrDigitado]);
            }
        }

        $pdo->commit();
        header("Location: editar.php?id=$orcamentoid&sucesso=1");
        exit;
    } catch(Exception $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        $erro = "Erro ao salvar: " . $e->getMessage();
    }
}

require_once "../includes/header.php";
require_once "../includes/menu.php";
?>

<style>
    body { background-color: #f4f7fe; padding-bottom: 80px; }
    .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 1rem; }
    .sticky-header { background: white; z-index: 1020; border-bottom: 1px solid #eee; padding: 10px 0; }
    .m2-badge { font-size: 0.75rem; color: #666; background: #e9ecef; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; }
    .input-medida { background: #fffdf0 !important; border: 1px solid #ffeb3b !important; font-weight: bold; }
    
    @media (max-width: 768px) {
        .item-row { display: block; border: 1px solid #eee; padding: 15px; border-radius: 10px; margin-bottom: 10px; background: #fff; position: relative; }
        .item-row td { display: block; width: 100% !important; border: none !important; padding: 4px 0 !important; }
        .thead-dark { display: none; }
        .btn-acao-flutuante { position: fixed; bottom: 0; left: 0; width: 100%; background: white; padding: 10px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); display: flex; gap: 5px; z-index: 1030; }
    }
</style>

<div class="sticky-header sticky-top shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-bold text-primary">Orçamento #<?= $orcamentoid ?></h6>
            <small class="badge bg-light text-dark border"><?= $situacao ?></small>
        </div>
        <div class="text-end">
            <span class="small text-muted d-block">Total Líquido</span>
            <h5 class="mb-0 fw-bold text-success" id="topo_total">R$ <?= number_format($orcamento['orcamentovalortotal'], 2, ',', '.') ?></h5>
        </div>
    </div>
</div>

<div class="container mt-3">
    <?php if($erro): ?>
        <div class="alert alert-danger shadow-sm"><?= $erro ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Salvo com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post" id="formEdicao">
        <input type="hidden" name="clienteid" id="clienteid" value="<?= $orcamento['clienteid'] ?>">

        <div class="row">
            <div class="col-lg-8">
                <div class="card p-3">
                    <span class="fw-bold text-muted small mb-2">DADOS DO CLIENTE</span>
                    <div class="row g-2">
                        <div class="col-md-7"><input type="text" name="clientenomecompleto" id="clientenomecompleto" class="form-control" value="<?= htmlspecialchars($orcamento['clientenomecompleto']) ?>"></div>
                        <div class="col-md-5"><input type="text" name="clientewhatsapp" id="clientewhatsapp" class="form-control" value="<?= htmlspecialchars($orcamento['clientewhatsapp']) ?>"></div>
                        <div class="col-md-3"><input type="text" name="clientecep" id="clientecep" class="form-control" value="<?= htmlspecialchars($orcamento['clientecep']) ?>"></div>
                        <div class="col-md-6"><input type="text" name="clientelogradouro" id="clientelogradouro" class="form-control" value="<?= htmlspecialchars($orcamento['clientelogradouro']) ?>"></div>
                        <div class="col-md-3"><input type="text" name="clientenumero" id="clientenumero" class="form-control" value="<?= htmlspecialchars($orcamento['clientenumero']) ?>"></div>
                        <div class="col-md-4"><input type="text" name="clientebairro" id="clientebairro" class="form-control" value="<?= htmlspecialchars($orcamento['clientebairro']) ?>"></div>
                        <div class="col-md-4"><input type="text" name="clientecidade" id="clientecidade" class="form-control" value="<?= htmlspecialchars($orcamento['clientecidade']) ?>"></div>
                        <div class="col-md-4"><input type="text" name="clientecpl" id="clientecpl" class="form-control" value="<?= htmlspecialchars($orcamento['clientecpl']) ?>"></div>
                    </div>
                </div>

                <div class="card p-3">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold text-muted small">ITENS DO SERVIÇO</span>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalProdutos" <?= $bloqueado ? 'disabled' : '' ?>>+ Item</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="tabelaItens">
                            <thead class="thead-dark small text-muted">
                                <tr>
                                    <th>Produto</th>
                                    <th width="90">Larg</th>
                                    <th width="90">Alt</th>
                                    <th width="120">Valor Final</th>
                                    <th width="30"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($itens as $idx => $item): ?>
                                <tr class="item-row">
                                    <td>
                                        <div class="fw-bold text-truncate" style="max-width:200px;"><?= htmlspecialchars($item['produtodescricao']) ?></div>
                                        <span class="m2-badge"><span class="val-m2"><?= number_format($item['m2'], 3) ?></span> m²</span>
                                        <input type="hidden" name="itens[<?= $idx ?>][id]" value="<?= $item['produtocodigo'] ?>">
                                        <input type="hidden" name="itens[<?= $idx ?>][nome]" value="<?= htmlspecialchars($item['produtodescricao']) ?>">
                                    </td>
                                    <td><input type="number" name="itens[<?= $idx ?>][largura]" class="form-control input-medida largura" value="<?= $item['largura'] ?>" <?= $bloqueado ? 'readonly' : '' ?>></td>
                                    <td><input type="number" name="itens[<?= $idx ?>][altura]" class="form-control input-medida altura" value="<?= $item['altura'] ?>" <?= $bloqueado ? 'readonly' : '' ?>></td>
                                    <td><input type="number" name="itens[<?= $idx ?>][valor]" class="form-control valorItem fw-bold text-primary" step="0.01" value="<?= $item['orcamentovalor'] ?>" <?= $bloqueado ? 'readonly' : '' ?>></td>
                                    <td class="text-end">
                                        <?php if(!$bloqueado): ?>
                                            <button type="button" class="btn btn-sm text-danger removerItem"><i class="bi bi-trash"></i></button>
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
                <div class="card p-3 bg-white border-primary shadow-sm">
                    <div class="mb-3">
                        <label class="fw-bold small">Situação</label>
                        <select name="orcamentosituacao" class="form-select fw-bold <?= $bloqueado ? 'bg-light' : '' ?>" <?= $bloqueado ? 'disabled' : '' ?>>
                            <?php foreach(['Criado','Aguardando Retorno','Cancelado','Aprovado'] as $s): ?>
                                <option <?= ($situacao==$s)?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-danger">Desconto Geral (R$)</label>
                        <input type="number" name="orcamentovlrdesconto" id="desconto" class="form-control form-control-lg text-danger fw-bold" step="0.01" value="<?= $orcamento['orcamentovlrdesconto'] ?>" <?= $bloqueado ? 'readonly' : '' ?>>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 btn-lg mb-2" <?= $bloqueado ? 'disabled' : '' ?>>SALVAR ALTERAÇÕES</button>
                    
                    <?php if($linkWA): ?>
                        <a href="<?= $linkWA ?>" target="_blank" class="btn btn-success w-100 mb-2"><i class="bi bi-whatsapp"></i> ENVIAR WHATSAPP</a>
                    <?php endif; ?>
                    <a href="imprimir.php?id=<?= $orcamentoid ?>" target="_blank" class="btn btn-dark w-100"><i class="bi bi-printer"></i> IMPRIMIR PDF</a>
                </div>

                <div class="card p-3">
                    <label class="small fw-bold">CONFIGURAÇÕES ADICIONAIS</label>
                    <div class="mb-2">
                        <small class="text-muted">Previsão Entrega</small>
                        <input type="datetime-local" name="orcamentoprevisaoentrega" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($orcamento['orcamentoprevisaoentrega'])) ?>" <?= $bloqueado ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Forma Pagamento</small>
                        <input type="text" name="orcamentoformapagamento" class="form-control" value="<?= htmlspecialchars($orcamento['orcamentoformapagamento']) ?>" <?= $bloqueado ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">Observações</small>
                        <textarea name="orcamento_obs" class="form-control" rows="3" <?= $bloqueado ? 'readonly' : '' ?>><?= htmlspecialchars($orcamento['orcamento_obs']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../orcamentos/modal_produtos.php'; ?>

<script>
let itemIndex = <?= count($itens) ?>;

function calcular() {
    let bruto = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const l = parseFloat(row.querySelector('.largura').value) || 0;
        const a = parseFloat(row.querySelector('.altura').value) || 0;
        const v = parseFloat(row.querySelector('.valorItem').value) || 0;
        const m2 = (l * a / 1000000);
        const elM2 = row.querySelector('.val-m2');
        if(elM2) elM2.innerText = m2.toFixed(3);
        bruto += v;
    });

    const desc = parseFloat(document.getElementById('desconto').value) || 0;
    const liq = bruto - desc;
    document.getElementById('topo_total').innerText = liq.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
}

document.addEventListener('input', e => {
    if(e.target.matches('.largura, .altura, .valorItem, #desconto')) calcular();
});

document.addEventListener('click', e => {
    if(e.target.closest('.removerItem')){
        e.target.closest('.item-row').remove();
        calcular();
    }
});

// Integração com o Modal de Produtos
document.addEventListener('click', e => {
    if(e.target.classList.contains('selecionarProduto')){
        const tr = e.target.closest('tr');
        const nome = tr.dataset.nome;
        const id = tr.dataset.id;
        const valor = parseFloat(tr.dataset.preco || 0);

        const row = `
        <tr class="item-row">
            <td>
                <div class="fw-bold text-truncate" style="max-width:200px;">${nome}</div>
                <span class="m2-badge"><span class="val-m2">0.000</span> m²</span>
                <input type="hidden" name="itens[${itemIndex}][id]" value="${id}">
                <input type="hidden" name="itens[${itemIndex}][nome]" value="${nome}">
            </td>
            <td><input type="number" name="itens[${itemIndex}][largura]" class="form-control input-medida largura" placeholder="0"></td>
            <td><input type="number" name="itens[${itemIndex}][altura]" class="form-control input-medida altura" placeholder="0"></td>
            <td><input type="number" name="itens[${itemIndex}][valor]" class="form-control valorItem fw-bold text-primary" step="0.01" value="${valor}"></td>
            <td class="text-end"><button type="button" class="btn btn-sm text-danger removerItem"><i class="bi bi-trash"></i></button></td>
        </tr>`;
        
        document.querySelector('#tabelaItens tbody').insertAdjacentHTML('beforeend', row);
        itemIndex++;
        
        // Fechar modal usando Bootstrap API
        const modalEl = document.getElementById('modalProdutos');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if(modal) modal.hide();
        
        calcular();
    }
});

document.addEventListener('DOMContentLoaded', calcular);
</script>

<?php require_once "../includes/footer.php"; ?>