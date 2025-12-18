<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Seu JS customizado aqui
</script>

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
                <a href="https://api.whatsapp.com/send?phone=5511934008521&text=Ol%C3%A1%20gostaria%20de%20um%20or%C3%A7amento" target="_blank" class="social-footer-link" title="WhatsApp">
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
    /* Estilo dos links sociais no rodapé claro */
    .social-footer-link {
        color: #a0aec0; /* Cinza claro */
        font-size: 1.1rem;
        margin-left: 20px;
        transition: all 0.2s ease-in-out;
        text-decoration: none;
    }

    .social-footer-link:hover {
        color: #4e73df; /* Cor primária do sistema no hover */
        transform: translateY(-2px);
    }

    /* Ajuste para o rodapé ficar sempre no fim se a página tiver pouco conteúdo */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    
    footer {
        margin-top: auto;
    }

    @media print {
        .no-print { display: none !important; }
    }
</style>
</body>
</html>