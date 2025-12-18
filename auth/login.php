<?php
require_once "../config/config.php";
require_once "../config/database.php";

if (isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Token de segurança CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Defesa contra Bots (Honeypot)
    if (!empty($_POST['website'])) {
        die("Bot detectado.");
    }

    // 2. Validar Token CSRF
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['csrf_token']) {
        die("Acesso inválido.");
    }

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND usuariosituacao = 1");
    $sql->execute([$email]);
    $usuario = $sql->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['usuariocodigo'];
        $_SESSION['usuario_nome'] = $usuario['nome'];

        header("Location: ../index.php");
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Visa Vidros</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --dark-bg: #0f172a;
            --accent-color: #224abe;
        }

        body, html { 
            height: 100%; 
            margin: 0; 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            background-color: var(--dark-bg);
        }

        .login-wrapper { 
            display: flex; 
            min-height: 100vh; 
            width: 100%;
        }

        /* Lado Esquerdo - Imagem */
        .login-side-image {
            flex: 1;
            background: linear-gradient(rgba(15, 23, 42, 0.3), rgba(15, 23, 42, 0.7)), 
                        url('https://images.unsplash.com/photo-1517329711918-a77b5892bee4?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            padding: 60px;
            color: white;
        }

        /* Lado Direito - Painel de Login */
        .login-side-form {
            width: 500px; /* Largura fixa para o formulário no desktop */
            background: #0f172a;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-box { 
            width: 100%; 
            max-width: 350px; 
        }

        .brand-logo { 
            font-size: 2.5rem; 
            font-weight: 800; 
            color: #fff; 
            letter-spacing: -2px;
            text-align: center;
        }

        .brand-subtitle { 
            color: #64748b; 
            margin-bottom: 40px; 
            font-size: 0.95rem; 
            text-align: center;
        }

        .form-label { color: rgba(255,255,255,0.8); font-weight: 600; font-size: 0.8rem; }
        
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff !important;
            padding: 12px;
            border-radius: 10px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .btn-login {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 700;
            color: #fff;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-login:hover { 
            background: var(--accent-color); 
            transform: translateY(-2px);
        }

        /* Rodapé Erwise */
        .social-footer {
            margin-top: 50px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            width: 100%;
        }

        .social-footer p {
            color: #ffffff !important; 
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .social-footer strong a {
            color: #ffffff !important;
            text-decoration: none;
        }

        .social-link {
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.3rem;
            margin: 0 12px;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .social-link:hover { color: #ffffff !important; transform: translateY(-3px); }
        .social-link.insta:hover { color: #E1306C !important; }
        .social-link.whats:hover { color: #25D366 !important; }

        /* Honeypot */
        .hp { display: none !important; }

        /* Responsividade */
        @media (max-width: 992px) {
            .login-side-image { display: none; }
            .login-side-form { width: 100%; flex: 1; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-side-image">
        <div>
            <h1 class="display-4 fw-bold mb-2 text-white">Visa Vidros</h1>
            <p class="lead opacity-75 text-white">Sistema de gestão profissional.</p>
        </div>
    </div>

    <div class="login-side-form">
        <div class="login-box">
            <div class="brand-logo">LOGIN</div>
            <div class="brand-subtitle">Acesse sua conta para continuar</div>

            <?php if($erro): ?>
                <div class="alert alert-danger border-0 small text-center" style="background: rgba(220,53,69,0.2); color: #ff8e97;">
                    <?= $erro ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="text" name="website" class="hp" tabindex="-1">

                <div class="mb-3">
                    <label class="form-label">E-MAIL</label>
                    <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">SENHA</label>
                    <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    ENTRAR NO SISTEMA <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>

            <div class="social-footer">
                <p>
                    Desenvolvido por <strong><a href="https://erwise.com.br" target="_blank">erwise.com.br</a></strong>
                </p>
                <div class="d-flex justify-content-center">
                    <a href="https://www.instagram.com/erwisedev" target="_blank" class="social-link insta">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="https://api.whatsapp.com/send?phone=5511934008521&text=Ol%C3%A1%20gostaria%20de%20um%20or%C3%A7amento" target="_blank" class="social-link whats">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                    <a href="https://erwise.com.br" target="_blank" class="social-link">
                        <i class="bi bi-globe2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>