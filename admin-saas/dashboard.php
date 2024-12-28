<?php
session_start();
require_once '../config.php';

// Verificar se o usuário é admin do SaaS
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin_saas') {
    header("Location: ../index.php");
    exit();
}

// Buscar estatísticas do SaaS
try {
    $estatisticas = [
        'total_empresas' => 0,
        'total_usuarios' => 0,
        'total_agendamentos' => 0
    ];

    // Total de empresas
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM empresas");
    $estatisticas['total_empresas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de usuários em todas as empresas
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM usuarios");
    $estatisticas['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de agendamentos em todas as empresas
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM agendamentos");
    $estatisticas['total_agendamentos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas do SaaS: " . $e->getMessage());
    $estatisticas = [
        'total_empresas' => 0,
        'total_usuarios' => 0,
        'total_agendamentos' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SaaS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8A4FFF;
            --secondary-color: #FF69B4;
            --accent-color: #4FACFE;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Inter', 'Arial', sans-serif;
        }

        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(138, 79, 255, 0.3);
        }

        .card-saas {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-saas:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-saas .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.7;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid px-4 py-5">
            <div class="row mb-4">
                <div class="col-12 dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-6 mb-3">Painel Administrativo SaaS</h1>
                            <p class="lead mb-0">Bem-vindo, Administrador do Sistema!</p>
                        </div>
                        <div>
                            <a href="../logout.php" class="btn btn-outline-light">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-saas text-center p-4">
                        <div class="card-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h5 class="card-title mb-3">Empresas</h5>
                        <p class="card-text mb-3">Total de Empresas Cadastradas</p>
                        <h3 class="text-primary"><?php echo $estatisticas['total_empresas']; ?></h3>
                        <a href="empresas.php" class="btn btn-primary mt-3">Gerenciar</a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-saas text-center p-4">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="card-title mb-3">Usuários</h5>
                        <p class="card-text mb-3">Total de Usuários em Todas as Empresas</p>
                        <h3 class="text-success"><?php echo $estatisticas['total_usuarios']; ?></h3>
                        <a href="usuarios.php" class="btn btn-success mt-3">Gerenciar</a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-saas text-center p-4">
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h5 class="card-title mb-3">Agendamentos</h5>
                        <p class="card-text mb-3">Total de Agendamentos em Todas as Empresas</p>
                        <h3 class="text-warning"><?php echo $estatisticas['total_agendamentos']; ?></h3>
                        <a href="relatorios.php" class="btn btn-warning mt-3">Relatórios</a>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            Ações Rápidas
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <a href="nova_empresa.php" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-plus me-2"></i>Cadastrar Nova Empresa
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="planos.php" class="btn btn-outline-success w-100 mb-2">
                                        <i class="fas fa-list me-2"></i>Gerenciar Planos
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="configuracoes.php" class="btn btn-outline-secondary w-100 mb-2">
                                        <i class="fas fa-cog me-2"></i>Configurações do Sistema
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>
</html>
