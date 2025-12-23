<?php
require_once "../config/database.php";

$codigo = $_GET['c'] ?? null;
if(!$codigo){
    die("Código do orçamento não informado.");
}

// Busca orçamento e dados completos do cliente
$sql = $pdo->prepare("SELECT o.*, c.* FROM orcamento o JOIN clientes c ON o.clienteid = c.clientecodigo WHERE orcamentolinkaprovacao=?");
$sql->execute([$codigo]);
$orcamento = $sql->fetch(PDO::FETCH_ASSOC);

if(!$orcamento){
    die("Orçamento não encontrado.");
}

// Busca itens do orçamento
$sqlItens = $pdo->prepare("SELECT * FROM orcamentoitem WHERE orcamentocodigo=?");
$sqlItens->execute([$orcamento['orcamentocodigo']]);
$itens = $sqlItens->fetchAll(PDO::FETCH_ASSOC);

$totalGeral = $orcamento['orcamentovalortotal'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #<?= $orcamento['orcamentocodigo'] ?> | Visa Vidros</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #4361ee; --dark: #0f172a; --bg-body: #f1f5f9; }
        body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: #334155; }
        
        .main-card { background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.08); border: none; overflow: hidden; margin-top: 2rem; margin-bottom: 3rem; }
        
        .header-top { background: var(--dark); color: white; padding: 40px; border-bottom: 5px solid var(--primary); }
        .brand-name { font-weight: 800; font-size: 1.8rem; letter-spacing: -1px; margin-bottom: 0; }
        
        .section-title { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--primary); margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .section-title::after { content: ""; height: 1px; background: #e2e8f0; flex-grow: 1; }
        
        .info-label { color: #94a3b8; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-weight: 600; color: var(--dark); font-size: 0.95rem; margin-bottom: 12px; }

        /* Grid de Endereço */
        .address-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; }

        .table thead th { background: #f8fafc; text-transform: uppercase; font-size: 0.65rem; color: #64748b; padding: 12px; border: none; }
        .table tbody td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .badge-medida { background: #eef2ff; color: var(--primary); font-weight: 700; font-size: 0.75rem; padding: 5px 10px; border-radius: 6px; }

        .obs-wrapper { background: #fdfcf0; border: 1px solid #fef3c7; border-left: 5px solid #f59e0b; border-radius: 12px; padding: 25px; margin: 30px 0; position: relative; }
        .obs-icon { position: absolute; top: -12px; left: 20px; background: #f59e0b; color: white; padding: 2px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; }
        .obs-content { color: #92400e; font-size: 0.95rem; line-height: 1.7; white-space: pre-wrap; }

        .total-banner { background: var(--dark); color: white; border-radius: 20px; padding: 30px; text-align: right; }
        .total-amount { font-size: 2.5rem; font-weight: 800; display: block; line-height: 1; }

        @media (max-width: 768px) {
            .address-grid { grid-template-columns: 1fr; gap: 0; }
            .header-top { padding: 30px 20px; text-align: center; }
            .total-banner { text-align: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="main-card">
        <div class="header-top d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="brand-name text-uppercase">Visa Vidros</h1>
                <p class="mb-0 opacity-75 small fw-bold">ORÇAMENTO DIGITAL</p>
            </div>
            <div class="text-md-end mt-3 mt-md-0">
                <div class="h4 fw-800 mb-0">Nº <?= $orcamento['orcamentocodigo'] ?></div>
                <div class="small opacity-75">Emitido em: <?= date('d/m/Y', strtotime($orcamento['orcamentodatacriacao'])) ?></div>
            </div>
        </div>

        <div class="p-4 p-md-5">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="section-title">Dados do Cliente</div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Nome Completo</div>
                    <div class="info-value"><?= htmlspecialchars($orcamento['clientenomecompleto']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label">WhatsApp</div>
                    <div class="info-value"><?= $orcamento['clientewhatsapp'] ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label">CEP</div>
                    <div class="info-value"><?= $orcamento['clientecep'] ?></div>
                </div>

                <div class="col-12">
                    <div class="address-grid">
                        <div>
                            <div class="info-label">Logradouro</div>
                            <div class="info-value"><?= htmlspecialchars($orcamento['clientelogradouro']) ?></div>
                        </div>
                        <div>
                            <div class="info-label">Número</div>
                            <div class="info-value"><?= $orcamento['clientenumero'] ?: 'S/N' ?></div>
                        </div>
                        <div>
                            <div class="info-label">Bairro</div>
                            <div class="info-value"><?= htmlspecialchars($orcamento['clientebairro']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-label">Cidade / UF</div>
                    <div class="info-value"><?= htmlspecialchars($orcamento['clientecidade']) ?> / SP</div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Complemento</div>
                    <div class="info-value"><?= htmlspecialchars($orcamento['clientecpl'] ?: 'Não informado') ?></div>
                </div>
            </div>

            <div class="section-title">Itens do Orçamento</div>
            <div class="table-responsive d-none d-md-block mb-4">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th width="50%">Descrição</th>
                            <th class="text-center">Dimensões</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($itens as $i): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($i['produtodescricao']) ?></div>
                                <div class="small text-muted"><?= number_format($i['m2'] ?? 0, 3) ?> m²</div>
                            </td>
                            <td class="text-center"><span class="badge-medida"><?= $i['largura'] ?> x <?= $i['altura'] ?> mm</span></td>
                            <td class="text-center"><?= (int)$i['orcamentoqnt'] ?></td>
                            <td class="text-end fw-bold text-dark">R$ <?= number_format($i['orcamentovalortotal'], 2, ",", ".") ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-md-none mb-4">
                <?php foreach($itens as $i): ?>
                <div class="p-3 border rounded-3 mb-2">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($i['produtodescricao']) ?></div>
                    <div class="small text-muted mb-2">Medidas: <?= $i['largura'] ?>x<?= $i['altura'] ?> mm</div>
                    <div class="d-flex justify-content-between">
                        <span>Qtd: <?= (int)$i['orcamentoqnt'] ?></span>
                        <span class="fw-bold text-primary">R$ <?= number_format($i['orcamentovalortotal'], 2, ",", ".") ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if(!empty($orcamento['orcamento_obs'])): ?>
            <div class="obs-wrapper shadow-sm">
                <div class="obs-icon">Notas Técnicas / Observações</div>
                <div class="obs-content"><?= nl2br(htmlspecialchars($orcamento['orcamento_obs'])) ?></div>
            </div>
            <?php endif; ?>

            <div class="row align-items-end g-4">
                <div class="col-md-6">
                    <div class="info-label">Forma de Pagamento Prevista</div>
                    <div class="info-value mb-0"><?= $orcamento['orcamentoformapagamento'] ?: 'A combinar' ?></div>
                </div>
                <div class="col-md-6">
                    <div class="total-banner">
                        <span class="text-white-50 small fw-bold text-uppercase">Total do Orçamento</span>
                        <span class="total-amount">R$ <?= number_format($totalGeral, 2, ",", ".") ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>