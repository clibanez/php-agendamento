<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Processar exclusão de serviço
if (isset($_POST['excluir_servico'])) {
    $servico_id = $_POST['servico_id'];
    try {
        // Excluir o serviço do banco de dados
        $stmt = $pdo->prepare("DELETE FROM servicos WHERE id = ?");
        $stmt->execute([$servico_id]);

        // Definir uma mensagem de sucesso
        $_SESSION['sucesso'] = "Serviço excluído com sucesso!";
        header("Location: servicos.php");
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir serviço: " . $e->getMessage());
        $_SESSION['erro'] = "Erro ao excluir serviço.";
    }
}

// Buscar serviços do banco de dados
try {
    $stmt = $pdo->query("SELECT * FROM servicos");
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar serviços: " . $e->getMessage());
    $servicos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Serviços</title>
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
            font-family: 'Poppins', 'Arial', sans-serif;
            color: var(--text-color);
        }

        .container {
            padding: 2rem 0;
        }

        .servicos-container {
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
            <a href="cadastro_servico.php" class="btn btn-custom">
                <i class="fas fa-plus"></i> Novo Serviço
            </a>
        </div>

        <div class="servicos-container">
            <h2 class="page-title">Gerenciar Serviços</h2>
            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['sucesso']); ?>
                    <?php unset($_SESSION['sucesso']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($_SESSION['erro']); ?>
                    <?php unset($_SESSION['erro']); ?>
                </div>
            <?php endif; ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Imagem</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $servico): ?>
                    <tr>
                        <td>
                            <?php if (!empty($servico['imagem_url']) && file_exists( $servico['imagem_url'])): ?>
                                <img src="<?php echo htmlspecialchars($servico['imagem_url']); ?>" alt="Imagem do Serviço" class="img-thumbnail" style="width: 50px; height: 50px;">
                            <?php else: ?>
                                <img src="../assets/img/avatar_padrao.svg" alt="Imagem Padrão" class="img-thumbnail" style="width: 50px; height: 50px;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($servico['nome']); ?></td>
                        <td><?php echo htmlspecialchars($servico['descricao']); ?></td>
                        <td><?php echo htmlspecialchars($servico['preco']); ?></td>
                        <td><?php echo htmlspecialchars($servico['status']); ?></td>
                        <td>
                            <a href="editar_servico.php?id=<?php echo $servico['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este serviço?');">
                                <input type="hidden" name="servico_id" value="<?php echo $servico['id']; ?>">
                                <button type="submit" name="excluir_servico" class="btn btn-sm btn-outline-danger" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
