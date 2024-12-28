<?php
session_start();
require_once '../config.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Buscar planos
$stmt = $pdo->query("SELECT * FROM planos ORDER BY preco_mensal");
$planos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ações de planos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'criar_plano') {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $preco = $_POST['preco'];
        $max_usuarios = $_POST['max_usuarios'];
        $max_servicos = $_POST['max_servicos'];
        $max_agendamentos = $_POST['max_agendamentos'];
        $recursos = implode(',', $_POST['recursos'] ?? []);

        $stmt = $pdo->prepare("INSERT INTO planos (nome, descricao, preco_mensal, max_usuarios, max_servicos, max_agendamentos_mes, recursos) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $preco, $max_usuarios, $max_servicos, $max_agendamentos, $recursos]);
        
        header("Location: planos.php?success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Planos SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF69B4;
            --secondary-color: #FFC0CB;
            --accent-color: #8A4FFF;
            --background-color: #FFE5EC;
            --text-color: #4A4A4A;
        }
        body {
            background-color: var(--background-color);
            font-family: 'Poppins', Arial, sans-serif;
        }
        .plano-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .plano-card:hover {
            transform: translateY(-10px);
        }
        .plano-preco {
            color: var(--primary-color);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Gerenciar Planos SaaS</h1>
        
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">Plano criado com sucesso!</div>
        <?php endif; ?>

        <div class="row">
            <?php foreach($planos as $plano): ?>
            <div class="col-md-4 mb-4">
                <div class="plano-card p-4 text-center">
                    <h3><?php echo htmlspecialchars($plano['nome']); ?></h3>
                    <p><?php echo htmlspecialchars($plano['descricao']); ?></p>
                    <div class="plano-preco h2">R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?>/mês</div>
                    
                    <ul class="list-unstyled mt-3">
                        <?php 
                        $recursos = explode(',', $plano['recursos']);
                        foreach($recursos as $recurso): ?>
                            <li><i class="fas fa-check text-success me-2"></i><?php echo htmlspecialchars($recurso); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="mt-4">
                        <span class="badge bg-primary">Máx. <?php echo $plano['max_usuarios']; ?> usuários</span>
                        <span class="badge bg-info">Máx. <?php echo $plano['max_servicos']; ?> serviços</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        Criar Novo Plano
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="criar_plano">
                            <div class="mb-3">
                                <label>Nome do Plano</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Descrição</label>
                                <textarea name="descricao" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Preço Mensal</label>
                                <input type="number" step="0.01" name="preco" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Máximo de Usuários</label>
                                <input type="number" name="max_usuarios" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Máximo de Serviços</label>
                                <input type="number" name="max_servicos" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Máximo de Agendamentos/Mês</label>
                                <input type="number" name="max_agendamentos" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Recursos</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="recursos[]" value="Suporte por E-mail" id="suporte-email">
                                    <label class="form-check-label" for="suporte-email">Suporte por E-mail</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="recursos[]" value="Relatórios" id="relatorios">
                                    <label class="form-check-label" for="relatorios">Relatórios</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="recursos[]" value="Integração API" id="integracao-api">
                                    <label class="form-check-label" for="integracao-api">Integração API</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Criar Plano</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
