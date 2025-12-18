<?php
require_once "../includes/header.php";
require_once "../includes/menu.php";
require_once "../config/database.php";

$erro = "";
$sucesso = "";

if ($_POST) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $situacao = isset($_POST['usuariosituacao']) ? 1 : 0;

    // Verifica se email já existe
    $check = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        $erro = "Email já cadastrado!";
    } else {
        $sql = $pdo->prepare("INSERT INTO usuarios (nome,email,senha,usuariosituacao) VALUES (?,?,?,?)");
        $sql->execute([$nome,$email,$senha,$situacao]);
        $sucesso = "Usuário criado com sucesso!";
    }
}
?>

<div class="container mt-4">
    <h4>Criar Usuário</h4>

    <?php if($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="alert alert-success"><?= $sucesso ?></div>
    <?php endif; ?>

    <form method="post" class="mt-3">
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="nome" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Senha</label>
            <input type="password" name="senha" class="form-control" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="usuariosituacao" class="form-check-input" checked>
            <label class="form-check-label">Ativo</label>
        </div>

        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="listar.php" class="btn btn-secondary">Voltar</a>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>
