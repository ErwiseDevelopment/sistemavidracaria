<?php
require_once "../includes/header.php";
require_once "../includes/menu.php";
require_once "../config/database.php";

$id = $_GET['id'] ?? 0;

$sql = $pdo->prepare("SELECT * FROM usuarios WHERE usuariocodigo = ?");
$sql->execute([$id]);
$usuario = $sql->fetch();

if (!$usuario) {
    header("Location: listar.php");
    exit;
}

$erro = "";
$sucesso = "";

if ($_POST) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $situacao = isset($_POST['usuariosituacao']) ? 1 : 0;

    // Atualizar senha somente se preenchida
    if($senha) {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, senha=?, usuariosituacao=? WHERE usuariocodigo=?");
        $sql->execute([$nome,$email,$senhaHash,$situacao,$id]);
    } else {
        $sql = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, usuariosituacao=? WHERE usuariocodigo=?");
        $sql->execute([$nome,$email,$situacao,$id]);
    }

    $sucesso = "Usuário atualizado!";
}
?>

<div class="container mt-4">
    <h4>Editar Usuário</h4>

    <?php if($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="alert alert-success"><?= $sucesso ?></div>
    <?php endif; ?>

    <form method="post" class="mt-3">
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Senha (deixe em branco para não alterar)</label>
            <input type="password" name="senha" class="form-control">
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="usuariosituacao" class="form-check-input" <?= $usuario['usuariosituacao'] ? "checked" : "" ?>>
            <label class="form-check-label">Ativo</label>
        </div>

        <button type="submit" class="btn btn-primary">Atualizar</button>
        <a href="listar.php" class="btn btn-secondary">Voltar</a>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>
