<?php
session_start();
require_once '../config.php';

// Verificar permissão de acesso
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'], ['admin', 'gerente'])) {
    header("Location: ../index.php");
    exit();
}

// Buscar agendamento se o ID for fornecido
if (isset($_GET['id'])) {
    $agendamento_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = ?");
    $stmt->execute([$agendamento_id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agendamento) {
        $_SESSION['erro'] = "Agendamento não encontrado.";
        header("Location: agendamentos.php");
        exit();
    }
} else {
    $_SESSION['erro'] = "ID do agendamento não fornecido.";
    header("Location: agendamentos.php");
    exit();
}

// Processar a edição do agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_hora = $_POST['data_hora'];
    $servico_id = $_POST['servico_id'];
    $cliente_id = $_POST['cliente_id'];
    $profissional_id = $_POST['profissional_id'];

    // Atualizar agendamento no banco de dados
    $stmt_update = $pdo->prepare("UPDATE agendamentos SET data_hora = ?, servico_id = ?, cliente_id = ?, profissional_id = ? WHERE id = ?");
    $stmt_update->execute([$data_hora, $servico_id, $cliente_id, $profissional_id, $agendamento_id]);
    $_SESSION['sucesso'] = "Agendamento atualizado com sucesso!";
    header("Location: agendamentos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/sidebar.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF69B4;  /* Rosa vibrante */
            --secondary-color: #FFC0CB;  /* Rosa claro */
            --accent-color: #8A4FFF;  /* Roxo suave */
            --background-gradient-start: #FFE5EC;  /* Rosa bem claro */
            --background-gradient-end: #FFF0F5;  /* Lavanda rosado */
            --text-color: #4A4A4A;  /* Cinza escuro para texto */
        }

        body {
            background: linear-gradient(135deg, var(--background-gradient-start), var(--background-gradient-end));
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text-color);
            min-height: 100vh;
        }

        .dashboard-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

      
    </style>
</head>
<body>

<div class="dashboard-content">
<?php include 'sidebar.php'; ?>
    <div class="container mt-5">
        <h2>Editar Agendamento</h2>
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['erro']; unset($_SESSION['erro']); ?>
            </div>
        <?php endif; ?>
        <form action="editar_agendamento.php?id=<?php echo $agendamento['id']; ?>" method="POST">
            <div class="mb-3">
                <label for="data_hora" class="form-label">Data e Hora</label>
                <input type="datetime-local" class="form-control" id="data_hora" name="data_hora" value="<?php echo date('Y-m-d\TH:i', strtotime($agendamento['data_hora'])); ?>" required>
            </div>
            <div class="mb-3">
                <label for="servico_id" class="form-label">Serviço</label>
                <select class="form-select" id="servico_id" name="servico_id" required>
                    <option value="">Selecione um serviço</option>
                    <?php
                    // Buscar serviços para preencher o dropdown
                    $stmt_servicos = $pdo->prepare("SELECT id, nome FROM servicos WHERE empresa_id = ?");
                    $stmt_servicos->execute([$_SESSION['empresa_id']]);
                    while ($servico = $stmt_servicos->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($servico['id'] == $agendamento['servico_id']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($servico['id']) . "' $selected>" . htmlspecialchars($servico['nome']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select class="form-select" id="cliente_id" name="cliente_id" required>
                    <option value="">Selecione um cliente</option>
                    <?php
                    // Buscar clientes para preencher o dropdown
                    $stmt_clientes = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'cliente' AND empresa_id = ?");
                    $stmt_clientes->execute([$_SESSION['empresa_id']]);
                    while ($cliente = $stmt_clientes->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($cliente['id'] == $agendamento['cliente_id']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($cliente['id']) . "' $selected>" . htmlspecialchars($cliente['nome']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="profissional_id" class="form-label">Profissional</label>
                <select class="form-select" id="profissional_id" name="profissional_id" required>
                    <option value="">Selecione um profissional</option>
                    <?php
                    // Buscar profissionais para preencher o dropdown
                    $stmt_profissionais = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'profissional' AND empresa_id = ?");
                    $stmt_profissionais->execute([$_SESSION['empresa_id']]);
                    while ($profissional = $stmt_profissionais->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($profissional['id'] == $agendamento['profissional_id']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($profissional['id']) . "' $selected>" . htmlspecialchars($profissional['nome']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Atualizar Agendamento</button>
        </form>
    </div>
                </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
