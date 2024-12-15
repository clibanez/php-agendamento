<?php
session_start();
require_once '../config.php';

// Verificar permissão de acesso
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'], ['admin', 'gerente'])) {
    header("Location: ../index.php");
    exit();
}

// Buscar informações da empresa logada
$stmt_empresa = $pdo->prepare("SELECT nome FROM empresas WHERE id = ?");
$stmt_empresa->execute([$_SESSION['empresa_id']]);
$empresa_logada = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

// Buscar usuários do tipo 'admin' e 'profissional'
$stmt_usuarios = $pdo->prepare("SELECT id, nome, tipo_usuario FROM usuarios WHERE empresa_id = ? AND tipo_usuario IN ('admin', 'profissional')");
$stmt_usuarios->execute([$_SESSION['empresa_id']]);
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

// Inicializar variáveis
$agendamentos = [];
$usuario_id = null;

// Filtrar agendamentos por usuário selecionado
if (isset($_POST['usuario_id'])) {
    $usuario_id = intval($_POST['usuario_id']);
    
    // Verificar se o usuário selecionado é 'admin' ou 'profissional'
    $stmt_verifica_usuario = $pdo->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ? AND empresa_id = ?");
    $stmt_verifica_usuario->execute([$usuario_id, $_SESSION['empresa_id']]);
    $usuario = $stmt_verifica_usuario->fetch(PDO::FETCH_ASSOC);

    if ($usuario && in_array($usuario['tipo_usuario'], ['admin', 'profissional'])) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.data_hora,
                    a.status,
                    u.nome as nome_cliente,
                    u.email as email_cliente,
                    s.nome as servico_nome
                FROM agendamentos a
                JOIN usuarios u ON a.cliente_id = u.id
                JOIN servicos s ON a.servico_id = s.id
                WHERE u.empresa_id = ? AND a.profissional_id = ?
                ORDER BY a.data_hora DESC
            ");
            
            $stmt->execute([$_SESSION['empresa_id'], $usuario_id]);
            $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao buscar agendamentos: " . $e->getMessage();
        }
    } else {
        $_SESSION['erro'] = "Usuário selecionado não é um admin ou profissional.";
    }
}

// Atualizar status do agendamento
if (isset($_POST['novo_status']) && isset($_POST['agendamento_id'])) {
    $agendamento_id = intval($_POST['agendamento_id']);
    $novo_status = $_POST['novo_status'];

    try {
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
        $stmt->execute([$novo_status, $agendamento_id]);
        $_SESSION['sucesso'] = "Status do agendamento atualizado com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar status: " . $e->getMessage();
    }
}

// Remover agendamento
if (isset($_POST['remover_agendamento'])) {
    $agendamento_id = intval($_POST['agendamento_id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE id = ?");
        $stmt->execute([$agendamento_id]);
        $_SESSION['sucesso'] = "Agendamento removido com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao remover agendamento: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Agendamentos - <?php echo htmlspecialchars($empresa_logada['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .container {
            padding: 2rem 0;
        }

        .agendamentos-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid var(--secondary-color);
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

        .btn-voltar, .btn-custom {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-voltar:hover, .btn-custom:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="dashboard.php" class="btn btn-voltar me-2">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <a href="agendamento/cadastro_agendamento.php" class="btn btn-custom">
                <i class="fas fa-plus"></i> Novo Agendamento
            </a>
        </div>

        <div class="agendamentos-container">
            <h2 class="page-title">Gerenciar Agendamentos - <?php echo htmlspecialchars($empresa_logada['nome']); ?></h2>
            
            <form method="POST" class="mb-4">
                <div class="mb-3">
                    <label for="usuario_id" class="form-label">Selecionar Profissional/Admin:</label>
                    <select name="usuario_id" id="usuario_id" class="form-select" required>
                        <option value="">Selecione um usuário</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>" <?php echo ($usuario_id == $usuario['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar Agendamentos</button>
            </form>

            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['sucesso'];
                    unset($_SESSION['sucesso']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['erro'];
                    unset($_SESSION['erro']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Serviço</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($agendamentos) && is_array($agendamentos)): ?>
                        <?php foreach ($agendamentos as $agendamento): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($agendamento['data_hora'])); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['nome_cliente']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['servico_nome']); ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'agendado' => 'warning',
                                        'confirmado' => 'success',
                                        'cancelado' => 'danger',
                                        'concluido' => 'info'
                                    ];
                                    $status = strtolower($agendamento['status']);
                                    $badge_class = $status_class[$status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <form action="agendamentos.php" method="POST" class="d-inline">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-sync-alt"></i> Atualizar Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <button type="submit" name="novo_status" value="agendado" class="dropdown-item" <?php echo ($agendamento['status'] == 'agendado') ? 'disabled' : ''; ?>>Agendado</button>
                                                </li>
                                                <li>
                                                    <button type="submit" name="novo_status" value="confirmado" class="dropdown-item" <?php echo ($agendamento['status'] == 'confirmado') ? 'disabled' : ''; ?>>Confirmado</button>
                                                </li>
                                                <li>
                                                    <button type="submit" name="novo_status" value="cancelado" class="dropdown-item" <?php echo ($agendamento['status'] == 'cancelado') ? 'disabled' : ''; ?>>Cancelado</button>
                                                </li>
                                                <li>
                                                    <button type="submit" name="novo_status" value="concluido" class="dropdown-item" <?php echo ($agendamento['status'] == 'concluido') ? 'disabled' : ''; ?>>Concluído</button>
                                                </li>
                                            </ul>
                                        </form>
                                        <form action="editar_agendamento.php" method="GET" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $agendamento['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-info" title="Editar Agendamento">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </form>
                                        <form action="agendamentos.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este agendamento?');">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <button type="submit" name="remover_agendamento" class="btn btn-sm btn-danger" title="Remover Agendamento">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <p class="text-muted my-3">Nenhum agendamento encontrado.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
