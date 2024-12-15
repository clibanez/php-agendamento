document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.modern-sidebar');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebarMenuItems = document.querySelectorAll('.sidebar-menu-item');

    // Função para alternar menu mobile
    function toggleMobileMenu() {
        sidebar.classList.toggle('open');
    }

    // Adicionar evento de clique no botão mobile
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }

    // Marcar item de menu ativo
    function setActiveMenuItem() {
        const currentPath = window.location.pathname;
        sidebarMenuItems.forEach(item => {
            const link = item.querySelector('a');
            if (link.getAttribute('href') === currentPath) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Fechar menu mobile ao clicar fora
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && 
            sidebar.classList.contains('open') && 
            !sidebar.contains(event.target) && 
            event.target !== mobileMenuToggle) {
            sidebar.classList.remove('open');
        }
    });

    // Executar ao carregar a página
    setActiveMenuItem();
});
