<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* Estilização moderna para a Navbar */
    .navbar {
        padding: 0.8rem 1rem;
        background: linear-gradient(135deg, #1a237e 0%, #4e73df 100%) !important;
    }

    .navbar-brand {
        font-weight: 800;
        letter-spacing: -0.5px;
        font-size: 1.4rem;
    }

    .nav-link {
        font-weight: 500;
        padding: 0.5rem 1rem !important;
        transition: all 0.3s ease;
        border-radius: 8px;
        margin: 0 2px;
        display: flex;
        align-items: center;
        gap: 8px; /* Espaço entre ícone e texto */
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    .nav-link i {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    /* Estilo do usuário logado */
    .user-profile {
        background: rgba(255, 255, 255, 0.1);
        padding: 5px 15px;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-logout {
        border-radius: 8px;
        font-weight: 600;
        padding: 0.4rem 1rem;
        transition: all 0.2s;
    }

    .btn-logout:hover {
        background-color: #ff4b5c !important;
        border-color: #ff4b5c !important;
        color: white !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-box-seam-fill me-2"></i>
            <span>VISA SISTEMA</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menuSistema">
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

            <div class="d-flex align-items-center">
                <div class="user-profile me-3 d-none d-lg-block">
                    <span class="navbar-text text-white p-0">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= $_SESSION['usuario_nome']; ?>
                    </span>
                </div>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-light btn-sm btn-logout">
                    <i class="bi bi-box-arrow-right me-1"></i> Sair
                </a>
            </div>
        </div>
    </div>
</nav>