<?php
session_start();
$erro_login = isset($_SESSION['erro_login']) ? $_SESSION['erro_login'] : null;
unset($_SESSION['erro_login']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Agendamento</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para ícones -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: #FF69B4;  /* Rosa vibrante */
        --secondary-color: #FFC0CB;  /* Rosa claro */
        --accent-color: #8A4FFF;  /* Roxo suave */
        --background-gradient-start: #FFB6C1;  /* Rosa bem claro */
        --background-gradient-end: #8A4FFF;  /* Lavanda rosado */
        --text-color: #4A4A4A;  /* Cinza escuro para texto */
    }

    body {
        background: linear-gradient(to bottom, #FFA07A, #FFCCCB);
        margin: 0;
        font-family: 'Poppins', 'Arial', sans-serif;
        color: #4A4A4A;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .login-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2);
        padding: 40px;
        width: 100%;
        border: 2px solid #FFC0CB;
        text-align: center;
    }

    .login-container h2 {
        color: #FF69B4;
        margin-bottom: 30px;
        font-weight: 600;
    }

    .form-control {
        border-radius: 25px;
        padding: 12px 20px;
        border-color: #FFC0CB;
    }

    .btn-login {
        background: linear-gradient(to right, #FF69B4, #8A4FFF);
        border: none;
        border-radius: 25px;
        padding: 12px;
        color: white;
        font-weight: bold;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-login:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(255, 105, 180, 0.4);
    }

    .form-control:focus {
        border-color: #FF69B4;
        box-shadow: 0 0 0 0.2rem rgba(255, 105, 180, 0.25);
    }

    .input-group-text {
        background: transparent;
        border: none;
        color: var(--primary-color);
    }

    .social-login {
        margin-top: 20px;
        text-align: center;
    }

    .social-icons a {
        color: var(--primary-color);
        margin: 0 10px;
        font-size: 24px;
        transition: color 0.3s ease, transform 0.3s ease;
    }

    .social-icons a:hover {
        color: var(--accent-color);
        transform: scale(1.2);
    }

    .forgot-password a {
        color: var(--accent-color);
        text-decoration: none;
        font-size: 0.9em;
        transition: color 0.3s ease;
    }

    .forgot-password a:hover {
        color: var(--primary-color);
        text-decoration: underline;
    }

    .alert-danger {
        background-color: #FFE4E1;
        border-color: var(--primary-color);
        color: #8B4513;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsividade para telas pequenas */
    @media (max-width: 576px) {
        .login-container {
            padding: 20px;
            width: 100%;
        }

        .btn-login {
            font-size: 14px;
        }

        .social-icons a {
            font-size: 20px;
            margin: 0 8px;
        }
    }

    .reservation-card {
        width: calc(33.333% - 20px);
        margin: 10px;
        display: inline-block;
    }

    @media (max-width: 768px) {
        .reservation-card {
            width: calc(50% - 20px);
        }
    }

    @media (max-width: 480px) {
        .reservation-card {
            width: 100%;
        }
    }
</style>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="login-container">
                    <h2><i class="fas fa-user-circle"></i> Login</h2>
                    
                    <?php if ($erro_login): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro_login); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="login" name="login" 
                                       placeholder="Nome ou E-mail" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" 
                                       placeholder="Senha" required>
                            </div>
                        </div>
                        <div class="forgot-password mb-3">
                            <a href="recuperar-senha.php">Esqueceu a senha?</a>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                        </div>
                    </form>
                    
                    <div class="social-login mt-4">
                        <p class="text-muted">Ou faça login com:</p>
                        <div class="social-icons">
                            <a href="#" title="Login com Google"><i class="fab fa-google"></i></a>
                            <a href="#" title="Login com Facebook"><i class="fab fa-facebook"></i></a>
                            <a href="#" title="Login com Apple"><i class="fab fa-apple"></i></a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <p class="text-muted">Não tem uma conta? 
                                <a href="cadastro.php" class="text-decoration-none">Cadastre-se</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
