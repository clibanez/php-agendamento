<?php
session_start();
require_once '../config.php';

// Verificar se o usuário é admin do SaaS
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin_saas') {
    header("Location: ../index.php");
    exit();
}

// Buscar relatórios gerais
try {
    // Total de agendamentos por empresa
    $stmt = $pdo->query("
        SELECT e.nome AS empresa_nome, 
               COUNT(a.id) AS total_agendamentos,
               COUNT(DISTINCT a.funcionario_id) AS total_funcionarios,
               COUNT(DISTINCT a.cliente_id) AS total_clientes
        FROM empresas e
        LEFT JOIN agendamentos a ON e.id = a.empresa_id
        GROUP BY e.id, e.nome
        ORDER BY total_agendamentos DESC
    ");
    $relatorios_empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $relatorios_empresas = [];
    error_log("Erro ao buscar relatórios: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - SaaS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        .card-report {
            transition: transform 0.3s;
        }
        .card-report:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-4">Relatórios do Sistema</h1>
                    
                    <div class="card">
                        <div class="card-header">
                            Resumo de Agendamentos por Empresa
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Empresa</th>
                                            <th>Total de Agendamentos</th>
                                            <th>Total de Funcionários</th>
                                            <th>Total de Clientes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($relatorios_empresas as $relatorio): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($relatorio['empresa_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($relatorio['total_agendamentos']); ?></td>
                                            <td><?php echo htmlspecialchars($relatorio['total_funcionarios']); ?></td>
                                            <td><?php echo htmlspecialchars($relatorio['total_clientes']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
