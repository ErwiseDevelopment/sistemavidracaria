<?php
require_once "../config/database.php";

// Buscar apenas código e nome, já que não existe o campo de valor
$stmt = $pdo->query("SELECT produtocodigo, produtonome FROM produtos ORDER BY produtonome");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="modal fade" id="modalProdutos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Produtos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="buscaProduto" class="form-control mb-2" placeholder="Buscar pelo nome">
        <table class="table table-hover" id="listaProdutos">
          <thead>
            <tr>
              <th>Produto</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($produtos): ?>
              <?php foreach($produtos as $p): ?>
              <tr data-id="<?= $p['produtocodigo'] ?>" 
                  data-nome="<?= htmlspecialchars($p['produtonome']) ?>">
                <td><?= htmlspecialchars($p['produtonome']) ?></td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-success selecionarProduto">Selecionar</button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="2" class="text-center">Nenhum produto cadastrado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Filtro de busca simples
document.getElementById('buscaProduto')?.addEventListener('input', function(){
    const filtro = this.value.toLowerCase();
    document.querySelectorAll('#listaProdutos tbody tr').forEach(tr => {
        const nome = tr.dataset.nome ? tr.dataset.nome.toLowerCase() : '';
        tr.style.display = nome.includes(filtro) ? '' : 'none';
    });
});
</script>