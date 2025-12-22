<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* Reset de Fonte e Estilo da Navbar */
    .navbar {
        font-family: 'Inter', sans-serif;
        padding: 0.7rem 1.2rem;
        /* Gradiente moderno e profundo */
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .navbar-brand {
        font-weight: 800;
        letter-spacing: -1px;
        font-size: 1.25rem;
        color: #f8fafc !important;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 15px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .navbar-brand i {
        color: #38bdf8; /* Azul destaque */
        font-size: 1.4rem;
    }

    /* Links de Navegação */
    .nav-link {
        font-weight: 500;
        font-size: 0.9rem;
        color: #cbd5e1 !important;
        padding: 0.6rem 1rem !important;
        transition: all 0.2s ease;
        border-radius: 10px;
        margin: 0 3px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-link:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-1px);
    }

    .nav-link i {
        font-size: 1.15rem;
        opacity: 0.8;
    }

    /* Perfil do Usuário */
    .user-pill {
        background: rgba(255, 255, 255, 0.05);
        padding: 6px 14px;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-right: 15px;
    }

    .user-pill .navbar-text {
        font-size: 0.85rem;
        font-weight: 600;
        color: #f1f5f9 !important;
    }

    .user-pill img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #38bdf8;
    }

    /* Botão Sair */
    .btn-logout {
        background: rgba(239, 68, 68, 0.1) !important;
        border: 1px solid rgba(239, 68, 68, 0.2) !important;
        color: #ef4444 !important;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 0.5rem 1.2rem;
        border-radius: 10px;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-logout:hover {
        background: #ef4444 !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Ajuste do Toggler (Mobile) */
    .navbar-toggler {
        padding: 0.4rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.05);
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            background: #1e293b;
            margin-top: 15px;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .user-pill { margin-bottom: 15px; width: fit-content; }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark shadow-lg sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand shadow-sm" href="<?= BASE_URL ?>/index.php">
           
            <span>VISA VIDROS</span>
        </a>

        <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#menuSistema">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuSistema">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/usuarios/listar.php">
                        <i class="bi bi-people"></i> Usuários
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/clientes/listar.php">
                        <i class="bi bi-person-badge"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/categorias/listar.php">
                        <i class="bi bi-tags"></i> Categorias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/produtos/listar.php">
                        <i class="bi bi-box-seam"></i> Produtos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/orcamentos/listar.php">
                        <i class="bi bi-file-earmark-text"></i> Orçamentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/pedidos/listar.php">
                        <i class="bi bi-cart-check"></i> Pedidos
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center flex-column flex-lg-row">
                <div class="user-pill d-none d-lg-flex shadow-sm">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['usuario_nome']); ?>&background=38bdf8&color=fff&size=32" alt="User Avatar">
                    <span class="navbar-text text-white p-0">
                        <?= explode(' ', $_SESSION['usuario_nome'] ?? 'Usuário')[0]; ?>
                    </span>
                </div>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-logout w-100 w-lg-auto">
                    <i class="bi bi-box-arrow-right me-2"></i> Sair
                </a>
            </div>
        </div>
    </div>
</nav>