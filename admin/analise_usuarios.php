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

// Buscar estatísticas dos usuários
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.nome,
            u.email,
            u.tipo_usuario,
            u.tipo_acesso,
            COUNT(DISTINCT a.id) as total_agendamentos,
            MAX(a.data_agendamento) as ultimo_agendamento,
            MIN(a.data_agendamento) as primeiro_agendamento
        FROM usuarios u
        LEFT JOIN agendamentos a ON u.id = a.usuario_id
        WHERE u.empresa_id = ?
        GROUP BY u.id, u.nome, u.email, u.tipo_usuario, u.tipo_acesso
        ORDER BY total_agendamentos DESC
    ");
    
    $stmt->execute([$_SESSION['empresa_id']]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao buscar dados dos usuários: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="dashboard-content">
        <h1 class="page-title">Análise de Usuários - <?php echo htmlspecialchars($empresa_logada['nome']); ?></h1>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['erro'];
                unset($_SESSION['erro']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card card-dashboard mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Estatísticas de Usuários</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo de Usuário</th>
                                <th>Total Agendamentos</th>
                                <th>Primeiro Agendamento</th>
                                <th>Último Agendamento</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $usuario['total_agendamentos']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        echo $usuario['primeiro_agendamento'] 
                                            ? date('d/m/Y', strtotime($usuario['primeiro_agendamento']))
                                            : '<span class="text-muted">-</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo $usuario['ultimo_agendamento']
                                            ? date('d/m/Y', strtotime($usuario['ultimo_agendamento']))
                                            : '<span class="text-muted">-</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['total_agendamentos'] > 0): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
