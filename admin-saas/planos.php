<?php
session_start();
require_once '../config.php';

// Verificar se o usuário é admin do SaaS
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin_saas') {
    header("Location: ../index.php");
    exit();
}

// Processar ações de planos
$mensagem = '';
$erro = '';

// Processar criação/edição de plano
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $acao = filter_input(INPUT_POST, 'acao');
        
        if ($acao === 'criar_plano') {
            $stmt = $pdo->prepare("
                INSERT INTO planos 
                (nome, preco_mensal, max_usuarios, max_agendamentos, recursos) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                filter_input(INPUT_POST, 'nome'),
                filter_input(INPUT_POST, 'preco_mensal', FILTER_VALIDATE_FLOAT),
                filter_input(INPUT_POST, 'max_usuarios', FILTER_VALIDATE_INT),
                filter_input(INPUT_POST, 'max_agendamentos', FILTER_VALIDATE_INT),
                json_encode(filter_input(INPUT_POST, 'recursos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [])
            ]);
            $mensagem = "Plano criado com sucesso!";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao processar plano: " . $e->getMessage();
    }
}

// Buscar planos existentes
try {
    $stmt = $pdo->query("SELECT * FROM planos ORDER BY preco_mensal");
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $planos = [];
    error_log("Erro ao buscar planos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos - SaaS Admin</title>
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
        .card-plano {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .card-plano:hover {
            transform: scale(1.03);
        }
        .badge-recurso {
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-4">Gerenciar Planos</h1>
                    
                    <?php if ($mensagem): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    Planos Existentes
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($planos as $plano): ?>
                                        <div class="col-md-4">
                                            <div class="card card-plano">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($plano['nome']); ?></h5>
                                                    <p class="card-text">
                                                        <strong>R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?>/mês</strong>
                                                    </p>
                                                    <div class="mb-3">
                                                        <small>
                                                            <i class="fas fa-users me-2"></i>
                                                            Máximo de <?php echo htmlspecialchars($plano['max_usuarios']); ?> usuários
                                                        </small><br>
                                                        <small>
                                                            <i class="fas fa-calendar-check me-2"></i>
                                                            Máximo de <?php echo htmlspecialchars($plano['max_agendamentos']); ?> agendamentos
                                                        </small>
                                                    </div>
                                                    <div>
                                                        <?php 
                                                        $recursos = json_decode($plano['recursos'], true) ?? [];
                                                        foreach ($recursos as $recurso): ?>
                                                            <span class="badge bg-primary badge-recurso">
                                                                <?php echo htmlspecialchars($recurso); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    Criar Novo Plano
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="acao" value="criar_plano">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome do Plano</label>
                                            <input type="text" class="form-control" id="nome" name="nome" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="preco_mensal" class="form-label">Preço Mensal (R$)</label>
                                            <input type="number" step="0.01" class="form-control" id="preco_mensal" name="preco_mensal" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="max_usuarios" class="form-label">Máximo de Usuários</label>
                                            <input type="number" class="form-control" id="max_usuarios" name="max_usuarios" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="max_agendamentos" class="form-label">Máximo de Agendamentos</label>
                                            <input type="number" class="form-control" id="max_agendamentos" name="max_agendamentos" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Recursos</label>
                                            <div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="recursos[]" value="Suporte 24h" id="recurso1">
                                                    <label class="form-check-label" for="recurso1">Suporte 24h</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="recursos[]" value="Relatórios Avançados" id="recurso2">
                                                    <label class="form-check-label" for="recurso2">Relatórios Avançados</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="recursos[]" value="Integração API" id="recurso3">
                                                    <label class="form-check-label" for="recurso3">Integração API</label>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Criar Plano
                                        </button>
                                    </form>
                                </div>
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
