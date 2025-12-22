<div class="fab-container d-lg-none no-print">
    <div class="fab-options">
        <a href="<?= BASE_URL ?>/pedidos/listar.php" class="fab-child shadow">
            <span>Pedidos</span>
            <i class="bi bi-cart-check"></i>
        </a>
        <a href="<?= BASE_URL ?>/orcamentos/listar.php" class="fab-child shadow">
            <span>Orçamentos</span>
            <i class="bi bi-file-earmark-text"></i>
        </a>
    </div>
    <button class="fab-main shadow-lg" id="fabMain">
        <i class="bi bi-plus-lg"></i>
    </button>
</div>

<footer class="mt-5 py-4 no-print" style="background-color: #f8f9fc; border-top: 1px solid #e3e6f0;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0" style="color: #a0aec0; font-size: 0.85rem;">
                    &copy; <?= date('Y') ?> Visa Vidros. 
                    Desenvolvido por <strong style="color: #718096;"><a href="https://erwise.com.br" target="_blank" style="color: inherit; text-decoration: none;">erwise.com.br</a></strong>
                </p>
            </div>
            
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                <a href="https://www.instagram.com/erwisedev" target="_blank" class="social-footer-link" title="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="https://api.whatsapp.com/send?phone=5511934008521" target="_blank" class="social-footer-link" title="WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </a>
                <a href="https://erwise.com.br" target="_blank" class="social-footer-link" title="Website">
                    <i class="bi bi-globe2"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Estilos do FAB (Botão Flutuante) */
    .fab-container {
        position: fixed;
        bottom: 25px;
        right: 25px;
        z-index: 1050;
        display: flex;
        flex-direction: column-reverse;
        align-items: center;
        gap: 15px;
    }

    .fab-main {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #4361ee;
        border: none;
        color: white;
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .fab-main.active {
        transform: rotate(45deg);
        background: #1e293b;
    }

    .fab-options {
        display: none;
        flex-direction: column-reverse;
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 5px;
    }

    .fab-options.show {
        display: flex;
    }

    .fab-child {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .fab-child span {
        background: #1e293b;
        color: white;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .fab-child i {
        width: 45px;
        height: 45px;
        background: white;
        color: #4361ee;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    /* Outros Estilos */
    .social-footer-link {
        color: #a0aec0;
        font-size: 1.1rem;
        margin-left: 20px;
        transition: all 0.2s ease-in-out;
        text-decoration: none;
    }

    .social-footer-link:hover {
        color: #4e73df;
        transform: translateY(-2px);
    }

    body { display: flex; flex-direction: column; min-height: 100vh; }
    footer { margin-top: auto; }
    @media print { .no-print { display: none !important; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fabMain = document.getElementById('fabMain');
    const fabOptions = document.querySelector('.fab-options');

    if(fabMain) {
        fabMain.addEventListener('click', function() {
            this.classList.toggle('active');
            fabOptions.classList.toggle('show');
        });
    }

    // Fecha o menu ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.fab-container') && fabOptions.classList.contains('show')) {
            fabMain.classList.remove('active');
            fabOptions.classList.remove('show');
        }
    });
});
</script>