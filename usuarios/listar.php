<?php
require_once "../includes/header.php";
require_once "../includes/menu.php";
require_once "../config/database.php";

// Buscar todos os usuários
$sql = $pdo->query("SELECT * FROM usuarios ORDER BY usuariocodigo DESC");
$usuarios = $sql->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h4>Usuários</h4>
        <a href="criar.php" class="btn btn-success">Novo Usuário</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Situação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['usuariocodigo'] ?></td>
                        <td><?= htmlspecialchars($u['nome']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?= $u['usuariosituacao'] ? "Ativo" : "Inativo" ?>
                        </td>
                        <td>
                            <a href="editar.php?id=<?= $u['usuariocodigo'] ?>" class="btn btn-sm btn-primary">Editar</a>
                            <a href="excluir.php?id=<?= $u['usuariocodigo'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
