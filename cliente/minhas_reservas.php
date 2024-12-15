<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../index.php");
    exit();
}

$agendamentos = [];

// Consulta para buscar os agendamentos do usuário
$stmt = $pdo->prepare("
    SELECT 
        a.id,
        a.data_hora, 
        a.data_hora_final,
        a.status, 
        u.nome AS profissional_nome,
        u.imagem_perfil_url AS profissional_foto,
        s.nome AS servico_nome
    FROM agendamentos a
    JOIN usuarios u ON a.profissional_id = u.id
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.cliente_id = ?
    ORDER BY a.data_hora DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para formatar data e hora
function formatarDataHora($dataHora) {
    $data = new DateTime($dataHora);
    return $data->format('d/m/Y H:i');
}

// Função para obter classe de status
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'confirmado':
            return 'success';
        case 'pendente':
            return 'warning';
        case 'cancelado':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border: 2px solid var(--secondary-color);
        }

        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .reservations-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: flex-start;
    }

    .reservation-card {
        background: white;
        border-radius: 20px;
        box-shadow: var(--card-shadow);
        border: 2px solid var(--secondary-color);
        margin-bottom: 20px;
        transition: all 0.3s ease;
        overflow: hidden;
        width: calc(33.333% - 14px); /* 3 cards per row with gap consideration */
        min-width: 300px; /* Minimum width for readability */
        flex-grow: 0;
    }

        .reservation-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }

        .reservation-body {
            padding: 20px;
        }

        .professional-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .professional-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid var(--secondary-color);
        }

        .service-details {
            background-color: rgba(255,105,180,0.1);
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .btn-back {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }

        .empty-state p {
            color: var(--text-color);
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        @media (max-width: 1200px) {
        .reservation-card {
            width: calc(50% - 10px); /* 2 cards per row */
        }
    }

    @media (max-width: 768px) {
        .reservation-card {
            width: 100%; /* 1 card per row */
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-calendar-check me-2"></i>Minhas Reservas
            </h2>
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
            </a>
        </div>

        <?php if (!empty($agendamentos)): ?>
    <div class="reservations-container">
        <?php foreach ($agendamentos as $agendamento): ?>
            <div class="reservation-card">
                    <div class="reservation-header">
                        <i class="fas fa-clock me-2"></i>
                        <?= formatarDataHora($agendamento['data_hora']) ?>
                    </div>
                    <div class="reservation-body">
                        <div class="professional-info">
                            <img src="<?= !empty($agendamento['profissional_foto']) ? '../' . htmlspecialchars($agendamento['profissional_foto']) : '../assets/img/avatar_padrao.svg' ?>" 
                                 alt="Foto do Profissional"
                                 class="professional-photo"
                                 onerror="this.src='../assets/img/avatar_padrao.svg'">
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($agendamento['profissional_nome']) ?></h5>
                                <span class="status-badge badge bg-<?= getStatusClass($agendamento['status']) ?>">
                                    <?= htmlspecialchars($agendamento['status']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="service-details">
                            <h6 class="mb-3">Detalhes do Serviço</h6>
                            <p class="mb-2">
                                <i class="fas fa-cut me-2"></i>
                                <strong>Serviço:</strong> <?= htmlspecialchars($agendamento['servico_nome']) ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Data e Hora:</strong> <?= formatarDataHora($agendamento['data_hora']) ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Data Final:</strong> <?= formatarDataHora($agendamento['data_hora_final']) ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?= getStatusClass($agendamento['status']) ?>">
                                    <?= htmlspecialchars($agendamento['status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="reservation-card">
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Você ainda não possui nenhum agendamento.</p>
                    <a href="agendamento/cadastro_agendamento.php" class="btn btn-back">
                        <i class="fas fa-plus me-2"></i>Fazer Novo Agendamento
                    </a>
                    </div>
<?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
