<div class="modal fade" id="modalClientes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Selecionar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered" id="tabelaClientes">
          <thead>
            <tr><th>Nome</th><th>WhatsApp</th><th>Ação</th></tr>
          </thead>
          <tbody>
            <?php
            $clientes = $pdo->query("SELECT * FROM clientes WHERE clientesituacao=1")->fetchAll();
            foreach ($clientes as $c):
            ?>
            <tr data-id="<?= $c['clientecodigo'] ?>" data-nome="<?= htmlspecialchars($c['clientenomecompleto']) ?>" data-whatsapp="<?= htmlspecialchars($c['clientewhatsapp']) ?>" data-cep="<?= $c['clientecep'] ?>" data-logradouro="<?= htmlspecialchars($c['clientelogradouro']) ?>" data-numero="<?= $c['clientenumero'] ?>" data-cpl="<?= htmlspecialchars($c['clientecpl']) ?>" data-bairro="<?= htmlspecialchars($c['clientebairro']) ?>" data-cidade="<?= htmlspecialchars($c['clientecidade']) ?>" data-obs="<?= htmlspecialchars($c['clienteobs']) ?>">
              <td><?= htmlspecialchars($c['clientenomecompleto']) ?></td>
              <td><?= htmlspecialchars($c['clientewhatsapp']) ?></td>
              <td><button type="button" class="btn btn-primary selecionarCliente">Selecionar</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.selecionarCliente').forEach(btn=>{
    btn.addEventListener('click', function(){
        const tr = this.closest('tr');
        document.getElementById('clienteid').value = tr.dataset.id;
        document.getElementById('clientenomecompleto').value = tr.dataset.nome;
        document.getElementById('clientewhatsapp').value = tr.dataset.whatsapp;
        document.getElementById('clientecep').value = tr.dataset.cep;
        document.getElementById('clientelogradouro').value = tr.dataset.logradouro;
        document.getElementById('clientenumero').value = tr.dataset.numero;
        document.getElementById('clientecpl').value = tr.dataset.cpl;
        document.getElementById('clientebairro').value = tr.dataset.bairro;
        document.getElementById('clientecidade').value = tr.dataset.cidade;
        document.getElementById('clienteobs').value = tr.dataset.obs;
        bootstrap.Modal.getInstance(document.getElementById('modalClientes')).hide();
    });
});
</script>
