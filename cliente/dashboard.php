<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../index.php");
    exit();
}

// Buscar informações do usuário e da empresa
try {
    $stmt = $pdo->prepare("SELECT 
        u.nome AS usuario_nome, 
        u.imagem_perfil_url, 
        e.nome AS empresa_nome 
    FROM usuarios u
    JOIN empresas e ON u.empresa_id = e.id
    WHERE u.id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_info) {
        throw new Exception('Usuário não encontrado.');
    }

    // Definir caminho da imagem
    $perfil_url = !empty($usuario_info['imagem_perfil_url']) 
        ? '../' . $usuario_info['imagem_perfil_url'] 
        : '../uploads/logos/logo_padrao.png';

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/sidebar.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF69B4;     /* Vibrant Pink */
            --secondary-color: #FFC0CB;   /* Light Pink */
            --accent-color: #8A4FFF;      /* Soft Purple */
            --background-gradient-start: #FFE5EC;
            --background-gradient-end: #FFF0F5;
            --text-color: #4A4A4A;
            --card-shadow: 0 15px 35px rgba(255,105,180,0.2);
        }

        body {
            background: linear-gradient(135deg, var(--background-gradient-start), var(--background-gradient-end));
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text-color);
            padding: 20px 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border: 2px solid var(--secondary-color);
            text-align: center;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 4px solid var(--primary-color);
            box-shadow: 0 10px 25px rgba(255,105,180,0.3);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 2px solid var(--secondary-color);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255,105,180,0.3);
        }

        .card-body {
            padding: 25px;
            text-align: center;
        }

        .card-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .card-text {
            color: var(--text-color);
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .btn {
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,105,180,0.4);
        }

        .welcome-text {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .company-name {
            color: var(--accent-color);
            font-size: 1.2rem;
            margin-bottom: 25px;
        }

        .btn-logout {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .profile-image {
                width: 120px;
                height: 120px;
            }

            .welcome-text {
                font-size: 1.5rem;
            }

            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="welcome-text">Bem-vindo, <?= htmlspecialchars($usuario_info['usuario_nome']) ?>!</h2>
            <form action="../logout.php" method="post" class="d-inline">
                <button type="submit" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </button>
            </form>
        </div>

        <div class="profile-section">
            <img src="<?= htmlspecialchars($perfil_url) ?>" 
                 alt="Imagem do Cliente" 
                 class="profile-image" 
                 onerror="this.src='../uploads/logos/logo_padrao.png'">
            <h3 class="company-name"><?= htmlspecialchars($usuario_info['empresa_nome']) ?></h3>
        </div>

        <div class="dashboard-container">
            <div class="dashboard-card">
                <div class="card-body">
                    <i class="fas fa-calendar-plus fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Agendar</h5>
                    <p class="card-text">Agende um novo serviço com facilidade.</p>
                    <a href="agendamento/cadastro_agendamento.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Agendar Agora
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Verificar Disponibilidade</h5>
                    <p class="card-text">Confira a disponibilidade dos profissionais.</p>
                    <a href="verificar_disponibilidade.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Verificar
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-body">
                    <i class="fas fa-calendar-check fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Minhas Reservas</h5>
                    <p class="card-text">Veja suas reservas atuais e futuras.</p>
                    <a href="minhas_reservas.php" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>Minhas Reservas
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-body">
                    <i class="fas fa-cog fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h5 class="card-title">Configurações</h5>
                    <p class="card-text">Gerencie suas configurações de conta.</p>
                    <a href="configuracoes.php" class="btn btn-primary">
                        <i class="fas fa-wrench me-2"></i>Configurações
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
