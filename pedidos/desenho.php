<?php
require_once "../config/database.php";

// Pegamos o ID da URL
$id_pedido = $_GET['id'] ?? null;

if (!$id_pedido) {
    exit("Erro: ID do pedido não informado.");
}

// Busca dados do pedido e cliente
$sql = $pdo->prepare("
    SELECT p.*, c.* FROM pedido p 
    JOIN clientes c ON p.clienteid = c.clientecodigo 
    WHERE p.pedidocodigo = ?");
$sql->execute([$id_pedido]);
$pedido = $sql->fetch();

if (!$pedido) {
    exit("Erro: Pedido não encontrado no banco de dados.");
}

// ESTA VARIÁVEL DEFINE O NOME DO ARQUIVO AO SALVAR
$nomeParaSalvar = "Desenho_Pedido_" . $id_pedido;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= $nomeParaSalvar ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Tamanho A4 Real */
        @page { 
            size: A4; 
            margin: 0; /* Margem zero para controlar via CSS */
        }
        
        body { 
            background: #f4f4f4; 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 0;
            padding: 0;
        }

        .print-container { 
            background: white; 
            width: 210mm; 
            height: 297mm; 
            margin: 10px auto; 
            padding: 15mm; 
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* Estilo Identico ao Orçamento */
        .brand-name { color: #1a237e; font-size: 28px; font-weight: 800; line-height: 1; }
        .order-info { background: #f8f9fa; border: 1px solid #333; border-radius: 5px; padding: 10px; text-align: center; }
        
        .info-card { border: 1px solid #333; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .label-sm { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #555; display: block; border-bottom: 1px solid #eee; margin-bottom: 5px; }

        /* QUADRICULADO REFORÇADO PARA IMPRESSÃO */
        .grid-canvas { 
            flex-grow: 1; 
            border: 2px solid #000;
            /* Criando linhas mais escuras para forçar a impressão */
            background-image: 
                linear-gradient(to right, #999 1px, transparent 1px),
                linear-gradient(to bottom, #999 1px, transparent 1px);
            background-size: 10px 10px; /* Quadrados de 0.5cm */
            background-position: top left;
            
            /* Forçar cores na impressão em todos os navegadores */
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @media print {
            body { background: white; }
            .print-container { margin: 0; box-shadow: none; border: none; width: 100%; height: 100vh; }
            .no-print { display: none !important; }
            .grid-canvas { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

<div class="no-print text-center py-4">
    <div class="mb-2 fw-bold text-primary">Nome sugerido: <?= $nomeParaSalvar ?>.pdf</div>
    <button onclick="window.print()" class="btn btn-primary px-5 shadow">
        <i class="bi bi-printer"></i> IMPRIMIR OU SALVAR PDF
    </button>
    <a href="editar.php?id=<?= $id_pedido ?>" class="btn btn-outline-secondary px-4">VOLTAR</a>
</div>

<div class="print-container">
    <div class="row align-items-start mb-3">
        <div class="col-7">
            <div class="brand-name">VISA VIDROS</div>
            <div class="fw-bold small">WWW.VISAVIDROSABC.COM.BR</div>
            <div class="mt-2" style="font-size: 11px;">
                <strong>CNPJ:</strong> 19.302.578/0001-20 | <strong>Fone:</strong> (11) 99450-7922<br>
                Rua Minotauro, 197 - Jd. Estádio - Santo André/SP
            </div>
        </div>
        <div class="col-5">
            <div class="order-info">
                <div class="small fw-bold">FOLHA DE DESENHO TÉCNICO</div>
                <div class="h3 fw-bold mb-0">PEDIDO #<?= $id_pedido ?></div>
            </div>
        </div>
    </div>

    <div class="info-card">
        <span class="label-sm">Dados do Cliente</span>
        <div class="row">
            <div class="col-8">
                <strong>NOME:</strong> <?= htmlspecialchars($pedido['clientenomecompleto'] ?? '') ?><br>
                <strong>ENDEREÇO:</strong> <?= htmlspecialchars($pedido['clientelogradouro'] ?? '') ?>, <?= htmlspecialchars($pedido['clientenumero'] ?? '') ?>
            </div>
            <div class="col-4 text-end">
                <strong>DATA:</strong> <?= date('d/m/Y') ?><br>
                <strong>WHATSAPP:</strong> <?= htmlspecialchars($pedido['clientewhatsapp'] ?? '') ?>
            </div>
        </div>
    </div>

    <div class="grid-canvas"></div>

    <div class="text-center mt-2 opacity-50" style="font-size: 9px;">
        Visa Vidros - Documento de Medição Auxiliar - Gerado via Sistema
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</body>
</html>