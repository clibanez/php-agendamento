document.addEventListener('DOMContentLoaded', function() {
    // Upload de logo da empresa
    const logoEmpresaInput = document.getElementById('logo_empresa');
    const logoPreview = document.getElementById('logo_preview');

    if (logoEmpresaInput && logoPreview) {
        logoEmpresaInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('logo_empresa', file);

                fetch('upload_imagem.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        logoPreview.src = data.url;
                        logoPreview.style.display = 'block';
                        showNotification('Logo atualizada com sucesso!', 'success');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showNotification('Erro ao enviar logo', 'error');
                });
            }
        });
    }

    // Upload de imagem de perfil
    const imagemPerfilInput = document.getElementById('imagem_perfil');
    const perfilPreview = document.getElementById('perfil_preview');

    if (imagemPerfilInput && perfilPreview) {
        imagemPerfilInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('imagem_perfil', file);

                fetch('upload_imagem.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        perfilPreview.src = data.url;
                        perfilPreview.style.display = 'block';
                        showNotification('Imagem de perfil atualizada com sucesso!', 'success');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showNotification('Erro ao enviar imagem de perfil', 'error');
                });
            }
        });
    }

    // Função de notificação
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }, 10);
    }
});
