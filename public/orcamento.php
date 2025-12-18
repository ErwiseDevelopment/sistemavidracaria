<?php
require_once "../config/database.php";

$codigo = $_GET['c'] ?? null;
if(!$codigo){
    die("Código do orçamento não informado.");
}

$sql = $pdo->prepare("SELECT o.*, c.* FROM orcamento o JOIN clientes c ON o.clienteid = c.clientecodigo WHERE orcamentolinkaprovacao=?");
$sql->execute([$codigo]);
$orcamento = $sql->fetch();
if(!$orcamento){
    die("Orçamento não encontrado.");
}

$itens = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=?");
$itens->execute([$orcamento['orcamentocodigo']]);
$itens = $itens->fetchAll();

$totalGeral = array_sum(array_column($itens,'orcamentovalortotal'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #<?= $orcamento['orcamentocodigo'] ?> | Visa Vidros</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4e73df; --dark: #0f172a; --light-bg: #f8f9fc; }
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; color: #333; }
        
        .main-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .header-gradient { background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%); color: white; padding: 30px 20px; }
        
        .section-title { border-left: 4px solid var(--primary); padding-left: 10px; font-weight: 700; color: var(--dark); margin-bottom: 20px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px; }
        
        .info-label { color: #8492a6; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
        .info-value { font-weight: 500; color: var(--dark); margin-bottom: 15px; }

        /* Estilo para Tabela/Cards Mobile */
        @media (max-width: 768px) {
            .hide-mobile { display: none; }
            .item-card { background: #fff; border: 1px solid #edf2f7; border-radius: 10px; padding: 15px; margin-bottom: 15px; }
            .item-row { display: flex; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px dashed #edf2f7; padding-bottom: 5px; }
            .item-total { background: #f8faff; padding: 10px; border-radius: 8px; font-weight: 700; color: var(--primary); }
        }

        .total-box { background: var(--dark); color: white; border-radius: 12px; padding: 25px; text-align: right; }
        .btn-approve { background: #25D366; border: none; color: white; font-weight: 700; padding: 15px; border-radius: 12px; transition: 0.3s; width: 100%; font-size: 1.1rem; }
        .btn-approve:hover { background: #128c7e; transform: translateY(-2px); }
        
        .footer-erwise { font-size: 0.8rem; color: #a0aec0; text-align: center; margin-top: 40px; }
        .footer-erwise a { color: #718096; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="container mt-4 mb-5">
    <div class="main-card">
        <div class="header-gradient d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0 fw-bold">VISA VIDROS</h2>
                <p class="mb-0 opacity-75">Orçamento Digital</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary px-3 py-2 mb-2">#<?= $orcamento['orcamentocodigo'] ?></span><br>
                <small class="opacity-75"><?= date('d/m/Y', strtotime($orcamento['orcamentodatacriacao'])) ?></small>
            </div>
        </div>

        <div class="p-4">
            <div class="row mb-4">
                <div class="col-md-6 border-end">
                    <h6 class="section-title">Dados do Cliente</h6>
                    <div class="row">
                        <div class="col-12">
                            <div class="info-label">Nome Completo</div>
                            <div class="info-value"><?= htmlspecialchars($orcamento['clientenomecompleto']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">WhatsApp</div>
                            <div class="info-value"><?= $orcamento['clientewhatsapp'] ?></div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Cidade</div>
                            <div class="info-value"><?= htmlspecialchars($orcamento['clientecidade']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">Endereço</div>
                            <div class="info-value"><?= htmlspecialchars($orcamento['clientelogradouro']) ?>, <?= $orcamento['clientenumero'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4 mt-4 mt-md-0">
                    <h6 class="section-title">Dados do Fornecedor</h6>
                    <div class="info-label">Empresa</div>
                    <div class="info-value text-primary fw-bold">Visa Vidros ABC</div>
                    <div class="info-label">Contato</div>
                    <div class="info-value">(11) 99450-7922</div>
                    <div class="info-label">Endereço</div>
                    <div class="info-value">Rua Minotauro, 197 - Jd. Estádio</div>
                </div>
            </div>

            <h6 class="section-title">Detalhamento dos Serviços</h6>
            <div class="table-responsive hide-mobile">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Descrição</th>
                            <th class="text-center border-0">Qtd</th>
                            <th class="text-end border-0">Unitário</th>
                            <th class="text-end border-0">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($itens as $i): ?>
                        <tr>
                            <td class="fw-500 text-dark"><?= htmlspecialchars($i['produtodescricao']) ?></td>
                            <td class="text-center"><?= number_format($i['orcamentoqnt'],0) ?></td>
                            <td class="text-end">R$ <?= number_format($i['orcamentovalor'],2,",",".") ?></td>
                            <td class="text-end fw-bold">R$ <?= number_format($i['orcamentovalortotal'],2,",",".") ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-md-none">
                <?php foreach($itens as $i): ?>
                <div class="item-card">
                    <div class="fw-bold text-dark mb-2"><?= htmlspecialchars($i['produtodescricao']) ?></div>
                    <div class="item-row">
                        <span class="text-muted small">Quantidade</span>
                        <span><?= number_format($i['orcamentoqnt'],0) ?></span>
                    </div>
                    <div class="item-row">
                        <span class="text-muted small">Valor Unitário</span>
                        <span>R$ <?= number_format($i['orcamentovalor'],2,",",".") ?></span>
                    </div>
                    <div class="item-total d-flex justify-content-between mt-2">
                        <span>Total do Item</span>
                        <span>R$ <?= number_format($i['orcamentovalortotal'],2,",",".") ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="row mt-5 align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="info-label">Forma de Pagamento</div>
                    <div class="info-value mb-0"><?= $orcamento['orcamentoformapagamento'] ?? 'A combinar' ?></div>
                </div>
                <div class="col-md-6">
                    <div class="total-box">
                        <div class="text-white-50 small fw-bold">VALOR TOTAL DO ORÇAMENTO</div>
                        <div class="display-6 fw-bold">R$ <?= number_format($totalGeral,2,",",".") ?></div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="https://api.whatsapp.com/send?phone=5511994507922&text=Olá, gostaria de aprovar meu orçamento de número <?= $orcamento['orcamentocodigo'] ?>!" class="btn btn-approve">
                    <i class="bi bi-whatsapp me-2"></i> APROVAR ORÇAMENTO AGORA
                </a>
                <p class="text-center mt-3 small text-muted">Válido até: <?= date('d/m/Y', strtotime('+7 days', strtotime($orcamento['orcamentodatacriacao']))) ?></p>
            </div>
        </div>
    </div>

    <div class="footer-erwise">
        <p>Desenvolvido por <strong><a href="https://erwise.com.br" target="_blank">erwise.com.br</a></strong></p>
        <div>
            <a href="https://www.instagram.com/erwisedev" class="mx-2"><i class="bi bi-instagram"></i></a>
            <a href="https://api.whatsapp.com/send?phone=5511934008521" class="mx-2"><i class="bi bi-whatsapp"></i></a>
        </div>
    </div>
</div>

</body>
</html>