<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header("Location: ../index.php");
    exit();
}

// Buscar serviços e funcionários
try {
    $stmt = $pdo->prepare("SELECT id, nome FROM servicos");
    $stmt->execute();
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar serviços: " . $e->getMessage());
    $servicos = [];
}

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM funcionarios");
    $stmt->execute();
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar funcionários: " . $e->getMessage());
    $funcionarios = [];
}

// Buscar os agendamentos do usuário
try {
    $stmt = $pdo->prepare("SELECT a.id AS agendamento_id, a.data_hora, a.servico, a.status 
                            FROM agendamentos a 
                            WHERE a.usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar agendamentos: " . $e->getMessage());
    $agendamentos = [];
}

// Processar o formulário de novo agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_hora = $_POST['data_hora'];
    $servico_id = $_POST['servico'];
    $funcionario_id = $_POST['funcionario_id'];

    // Validação básica
    if (empty($data_hora) || empty($servico_id) || empty($funcionario_id)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        try {
            // Verificar disponibilidade do funcionário
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE funcionario_id = ? AND data_hora = ?");
            $stmt->execute([$funcionario_id, $data_hora]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Funcionário não disponível nesse horário.";
            } else {
                // Criar o agendamento
                $stmt = $pdo->prepare("INSERT INTO agendamentos (data_hora, servico_id, funcionario_id, usuario_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$data_hora, $servico_id, $funcionario_id, $_SESSION['usuario_id']]);
                $success = "Agendamento criado com sucesso!";
            }
        } catch (PDOException $e) {
            error_log("Erro ao criar agendamento: " . $e->getMessage());
            $error = "Erro ao criar agendamento.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to bottom, #FFA07A, #FFCCCB);
            font-family: 'Poppins', Arial, sans-serif;
        }
        .container {
            margin-top: 20px;
        }
        .card {
            background-color: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Meus Agendamentos</h2>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Agendamentos Atuais</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data e Hora</th>
                                    <th>Serviço</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $agendamento): ?>
                                    <tr>
                                        <td><?php echo $agendamento['agendamento_id']; ?></td>
                                        <td><?php echo $agendamento['data_hora']; ?></td>
                                        <td><?php echo $agendamento['servico']; ?></td>
                                        <td><?php echo $agendamento['status']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Novo Agendamento</h4>
                    </div>
                    <div class="card-body">
                        <form id="agendamentoForm" method="POST" action="meus_agendamentos.php">
                            <div class="form-group">
                                <label for="data_hora">Data e Hora</label>
                                <input type="datetime-local" class="form-control" id="data_hora" name="data_hora" required>
                            </div>
                            <div class="form-group">
                                <label for="servico">Serviço</label>
                                <select class="form-control" id="servico" name="servico" required>
                                    <option value="">Selecione um serviço</option>
                                    <?php foreach ($servicos as $servico): ?>
                                        <option value="<?php echo $servico['id']; ?>"><?php echo $servico['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="funcionario_id">Funcionário</label>
                                <select class="form-control" id="funcionario_id" name="funcionario_id" required>
                                    <option value="">Selecione um funcionário</option>
                                    <?php foreach ($funcionarios as $funcionario): ?>
                                        <option value="<?php echo $funcionario['id']; ?>"><?php echo $funcionario['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Agendar</button>
                        </form>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
                        <?php elseif (isset($success)): ?>
                            <div class="alert alert-success mt-3"><?php echo $success; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('agendamentoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const dataHora = document.getElementById('data_hora').value;
            const servicoId = document.getElementById('servico').value;
            const funcionarioId = document.getElementById('funcionario_id').value;
            const resultado = document.getElementById('resultado');

            fetch('verificar_disponibilidade.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `data=${dataHora.split('T')[0]}&hora=${dataHora.split('T')[1]}&dia_semana=${new Date(dataHora).toLocaleString('pt-BR', { weekday: 'long' })}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    resultado.innerHTML = `<div class='alert alert-success'>Profissionais disponíveis:</div>`;
                    data.forEach(prof => {
                        resultado.innerHTML += `<div>${prof.nome} - ${prof.hora_inicio} a ${prof.hora_fim}</div>`;
                    });
                } else {
                    resultado.innerHTML = `<div class='alert alert-danger'>Nenhum profissional disponível.</div>`;
                }
            })
            .catch(error => {
                resultado.innerHTML = `<div class='alert alert-danger'>Erro ao verificar disponibilidade: ${error}</div>`;
            });
        });
    </script>
</body>
</html>
