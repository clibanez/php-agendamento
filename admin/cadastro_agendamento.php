<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Buscar clientes associados à empresa
$stmt_clientes = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'cliente' AND empresa_id = ?");
$stmt_clientes->execute([$_SESSION['empresa_id']]);
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Processar o formulário de cadastro de agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $servico_id = $_POST['servico_id'] ?? '';
    $cliente_id = $_POST['cliente_id'] ?? '';
    $profissional_id = $_POST['profissional_id'] ?? '';

    // Validação básica
    if (empty($data) || empty($hora) || empty($servico_id) || empty($cliente_id) || empty($profissional_id)) {
        $erro = "Todos os campos são obrigatórios.";
    } else {
        try {
            // Verificar se a empresa está definida na sessão
            if (!isset($_SESSION['empresa_id'])) {
                die('Erro: Empresa não definida na sessão.');
            }
            // Inserir o novo agendamento no banco de dados
            $stmt = $pdo->prepare("INSERT INTO agendamentos (data_hora, servico_id, cliente_id, profissional_id, empresa_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data . ' ' . $hora, $servico_id, $cliente_id, $profissional_id, $_SESSION['empresa_id']]);
            header("Location: agendamentos.php");
            exit();
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar agendamento: " . $e->getMessage());
            $erro = "Erro ao cadastrar agendamento: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/sidebar.css" rel="stylesheet">
</head>

<style>
        :root {
            --primary-color: #FF69B4;
            --secondary-color: #FFC0CB;
            --accent-color: #8A4FFF;
        }
        body {
            background: linear-gradient(135deg, #FFE5EC, #FFF0F5);
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text-color);
            min-height: 100vh;
        }
        .dashboard-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        .page-title {
            color: var(--primary-color);
            margin-bottom: 30px;
        }
    </style>
<body>

<div class="dashboard-content">
    <div class="container">
        <div class="form-container">
            <h1 class="page-title">
                <i class="fas fa-calendar-plus me-2"></i>
                Cadastrar Agendamento
            </h1>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <form action="cadastro_agendamento.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="data" class="form-label">Data</label>
                        <input type="date" class="form-control" id="data" name="data" required>
                    </div>

                    <div class="form-group">
                        <label for="hora" class="form-label">Hora</label>
                        <input type="time" class="form-control" id="hora" name="hora" required>
                    </div>

                    <div class="form-group">
                        <label for="servico_id" class="form-label">Serviço</label>
                        <select class="form-select" id="servico_id" name="servico_id" required>
                            <option value="">Selecione um serviço</option>
                            <?php
                            $stmt = $pdo->query("SELECT id, nome FROM servicos");
                            while ($servico = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($servico['id']) . "'>" . 
                                     htmlspecialchars($servico['nome']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo htmlspecialchars($cliente['id']); ?>">
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="profissional_id" class="form-label">Profissional</label>
                        <select class="form-select" id="profissional_id" name="profissional_id" required>
                            <option value="">Selecione um profissional</option>
                            <?php
                            $stmt_profissionais = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'profissional' AND empresa_id = ?");
                            $stmt_profissionais->execute([$_SESSION['empresa_id']]);
                            while ($profissional = $stmt_profissionais->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($profissional['id']) . "'>" . 
                                     htmlspecialchars($profissional['nome']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Cadastrar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
