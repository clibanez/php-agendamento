<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../index.php");
    exit();
}

// Buscar serviços disponíveis e profissionais
try {
    // Buscar serviços da mesma empresa do cliente
    $stmt = $pdo->prepare("
        SELECT s.id, s.nome 
        FROM servicos s 
        WHERE s.ativo = 1 
        AND s.empresa_id = (SELECT empresa_id FROM usuarios WHERE id = ?)
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar profissionais ativos da mesma empresa
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.tipo_usuario
        FROM usuarios u
        WHERE u.status = 'ativo' 
        AND u.empresa_id = (SELECT empresa_id FROM usuarios WHERE id = ?)
        AND (u.tipo_usuario IN ('admin', 'profissional'))
        AND u.tipo_acesso IN ('admin_empresa', 'gerente', 'funcionario')
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $erro = 'Erro ao carregar dados: ' . $e->getMessage();
}

// Processar busca de disponibilidade
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'] ?? '';
    $servico_id = $_POST['servico_id'] ?? '';
    $profissional_id = $_POST['profissional_id'] ?? '';

    try {
        // Primeiro, verificar disponibilidade na tabela disponibilidade_profissionais
        $stmt = $pdo->prepare("
            SELECT hora_inicio, hora_fim 
            FROM disponibilidade_profissionais 
            WHERE profissional_id = ? 
            AND data = ?
            ORDER BY hora_inicio ASC
        ");
        $stmt->execute([$profissional_id, $data]);
        $disponibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Depois, buscar agendamentos existentes para verificar conflitos
        $stmt = $pdo->prepare("
            SELECT data_hora, data_hora_final 
            FROM agendamentos 
            WHERE profissional_id = ? 
            AND DATE(data_hora) = ?
            AND status NOT IN ('cancelado')
        ");
        $stmt->execute([$profissional_id, $data]);
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar duração do serviço
        $stmt = $pdo->prepare("SELECT duracao_minutos FROM servicos WHERE id = ?");
        $stmt->execute([$servico_id]);
        $servico = $stmt->fetch(PDO::FETCH_ASSOC);
        $duracao_servico = $servico['duracao_minutos'];

        // Gerar horários disponíveis
        $horarios_disponiveis = [];
        foreach ($disponibilidades as $disponibilidade) {
            $inicio = strtotime($disponibilidade['hora_inicio']);
            $fim = strtotime($disponibilidade['hora_fim']);
            
            // Gerar slots de acordo com a duração do serviço
            for ($time = $inicio; $time <= $fim - ($duracao_servico * 60); $time += 1800) { // 30 minutos de intervalo
                $horario_inicio = date('H:i', $time);
                $horario_fim = date('H:i', $time + ($duracao_servico * 60));
                
                // Verificar conflitos com agendamentos existentes
                $disponivel = true;
                foreach ($agendamentos as $agendamento) {
                    $agendamento_inicio = date('H:i', strtotime($agendamento['data_hora']));
                    $agendamento_fim = date('H:i', strtotime($agendamento['data_hora_final']));
                    
                    if (($horario_inicio >= $agendamento_inicio && $horario_inicio < $agendamento_fim) ||
                        ($horario_fim > $agendamento_inicio && $horario_fim <= $agendamento_fim)) {
                        $disponivel = false;
                        break;
                    }
                }
                
                if ($disponivel) {
                    $horarios_disponiveis[] = [
                        'inicio' => $horario_inicio,
                        'fim' => $horario_fim
                    ];
                }
            }
        }
    } catch (Exception $e) {
        $erro = 'Erro ao verificar disponibilidade: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Disponibilidade</title>
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
        }

        body {
            background: linear-gradient(135deg, var(--background-gradient-start), var(--background-gradient-end));
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .main-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            padding: 40px;
            border: 2px solid var(--secondary-color);
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out;
        }

        .form-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 2rem;
        }

        .form-control, .form-select {
            border-radius: 25px;
            padding: 12px 20px;
            border: 2px solid var(--secondary-color);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
        }

        .input-group-text {
            border-radius: 25px 0 0 25px;
            background: transparent;
            border: 2px solid var(--secondary-color);
            border-right: none;
        }

        .input-group .form-control {
            border-radius: 0 25px 25px 0;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,105,180,0.4);
        }

        .horarios-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .horario-btn {
            background: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 15px;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .horario-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,105,180,0.3);
        }

        .alert {
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-voltar {
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    border-radius: 25px;
    padding: 10px 20px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    background: white;
}

.btn-voltar:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255,105,180,0.3);
}

.btn-voltar i {
    transition: transform 0.3s ease;
}

.btn-voltar:hover i {
    transform: translateX(-3px);
}

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }

            .form-container {
                padding: 20px;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .horarios-container {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-outline-primary btn-voltar">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
            </a>
        </div>

        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-calendar-check me-2"></i>
                Verificar Disponibilidade
            </h2>

            <form method="POST" class="row g-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-calendar"></i>
                        </span>
                        <input type="date" class="form-control" id="data" name="data" 
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-cut"></i>
                        </span>
                        <select class="form-select" id="servico_id" name="servico_id" required>
                            <option value="">Serviço</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?= $servico['id'] ?>">
                                    <?= htmlspecialchars($servico['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <select class="form-select" id="profissional_id" name="profissional_id" required>
                            <option value="">Profissional</option>
                            <?php foreach ($profissionais as $profissional): ?>
                                <option value="<?= $profissional['id'] ?>">
                                    <?= htmlspecialchars($profissional['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>
                        Verificar Disponibilidade
                    </button>
                </div>
            </form>
        </div>

        <?php if (isset($horarios_disponiveis) && !empty($horarios_disponiveis)): ?>
            <div class="form-container">
                <h3 class="form-title h4">
                    <i class="fas fa-clock me-2"></i>
                    Horários Disponíveis
                </h3>
                <div class="horarios-container">
                    <?php foreach ($horarios_disponiveis as $horario): ?>
                        <a href="agendamento/cadastro_agendamento.php?data=<?= $data ?>&horario_inicio=<?= $horario['inicio'] ?>&horario_fim=<?= $horario['fim'] ?>&servico_id=<?= $servico_id ?>&profissional_id=<?= $profissional_id ?>" 
                           class="horario-btn">
                            <i class="far fa-clock"></i>
                            <?= $horario['inicio'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif (isset($horarios_disponiveis)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Não há horários disponíveis para a data selecionada.
            </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $erro ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>