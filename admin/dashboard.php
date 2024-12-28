<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Buscar informações do usuário e da empresa
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.nome AS usuario_nome, 
            u.imagem_perfil_url, 
            e.nome AS empresa_nome, 
            e.logo_url 
        FROM usuarios u
        JOIN empresas e ON u.empresa_id = e.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Definir imagens padrão se não existirem
    $logo_url = $usuario_info['logo_url'] ?? 'uploads/logos/logo_padrao.svg';
    $perfil_url = $usuario_info['imagem_perfil_url'] ?? 'uploads/perfis/admin_padrao.svg';
    
    // Debug: Log informações da imagem
    error_log("Debug - Logo URL: " . print_r($usuario_info['logo_url'], true));
    error_log("Debug - Logo URL Completo: " . $logo_url);
    error_log("Debug - Arquivo existe: " . (file_exists('../' . $logo_url) ? 'Sim' : 'Não'));

    if (!empty($usuario_info['logo_url']) && file_exists('../' . $usuario_info['logo_url'])) {
        $logo_url = $usuario_info['logo_url'];
    }

    if (!empty($usuario_info['imagem_perfil_url']) && file_exists('../' . $usuario_info['imagem_perfil_url'])) {
        $perfil_url = $usuario_info['imagem_perfil_url'];
    }
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro ao buscar informações do usuário: " . $e->getMessage());
    $logo_url = 'uploads/logos/logo_padrao.svg';
    $perfil_url = 'uploads/perfis/admin_padrao.svg';
}

// Buscar estatísticas
$estatisticas = [
    'total_usuarios' => 0,
    'total_agendamentos' => 0,
    'total_servicos' => 0
];

try {
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM usuarios WHERE empresa_id = " . $_SESSION['empresa_id']);
    $estatisticas['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de agendamentos
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM agendamentos WHERE empresa_id = " . $_SESSION['empresa_id']);
    $estatisticas['total_agendamentos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de serviços
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM servicos");
    $estatisticas['total_servicos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF69B4;
            --secondary-color: #FFC0CB;
            --accent-color: #8A4FFF;
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

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border: 2px solid var(--secondary-color);
        }

        .profile-section {
            text-align: center;
            padding: 20px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            box-shadow: 0 10px 25px rgba(255,105,180,0.3);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .company-name {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 15px 0;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            padding: 20px 0;
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

        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            padding: 20px;
            text-align: center;
        }

        .card-body {
            padding: 25px;
            text-align: center;
        }

        .card-title {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .card-text {
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--accent-color);
            margin: 15px 0;
        }

        .btn-custom {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,105,180,0.4);
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            color: white;
        }

        .btn-logout {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
            
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .profile-image {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Bem-vindo, <?= htmlspecialchars($usuario_info['usuario_nome']) ?>!</h2>
                <form action="../logout.php" method="post" class="d-inline">
                    <button type="submit" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                    </button>
                </form>
            </div>
            <div class="profile-section">
                <img src="<?= '../' . htmlspecialchars($perfil_url) ?>" 
                     alt="Imagem do Administrador" 
                     class="profile-image" 
                     onerror="this.src='../uploads/logos/logo_padrao.png'">
                <h3 class="company-name"><?= htmlspecialchars($usuario_info['empresa_nome']) ?></h3>
            </div>
        </div>
        

        <div class="dashboard-container">

        <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h5 class="mb-0">disponibilidade</h5>
                </div>
                <div class="card-body">
                    <div class="stats-number"><?= $estatisticas['total_usuarios'] ?></div>
                    <p class="card-text">disponibilidade</p>
                    <a href="disponibilidade.php" class="btn btn-custom">
                        <i class="fas fa-user-cog me-2"></i>disponibilidade
                    </a>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h5 class="mb-0">Usuários</h5>
                </div>
                <div class="card-body">
                    <div class="stats-number"><?= $estatisticas['total_usuarios'] ?></div>
                    <p class="card-text">Total de usuários cadastrados</p>
                    <a href="usuarios.php" class="btn btn-custom">
                        <i class="fas fa-user-cog me-2"></i>Gerenciar Usuários
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                    <h5 class="mb-0">Agendamentos</h5>
                </div>
                <div class="card-body">
                    <div class="stats-number"><?= $estatisticas['total_agendamentos'] ?></div>
                    <p class="card-text">Total de agendamentos realizados</p>
                    <a href="agendamentos.php" class="btn btn-custom">
                        <i class="fas fa-calendar-alt me-2"></i>Ver Agendamentos
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-concierge-bell fa-2x mb-2"></i>
                    <h5 class="mb-0">Serviços</h5>
                </div>
                <div class="card-body">
                    <div class="stats-number"><?= $estatisticas['total_servicos'] ?></div>
                    <p class="card-text">Total de serviços disponíveis</p>
                    <a href="servicos.php" class="btn btn-custom">
                        <i class="fas fa-cog me-2"></i>Gerenciar Serviços
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h5 class="mb-0">Relatórios</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Acesse relatórios e estatísticas</p>
                    <a href="relatorios.php" class="btn btn-custom">
                        <i class="fas fa-chart-bar me-2"></i>Ver Relatórios
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
