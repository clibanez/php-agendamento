<?php
session_start();
require_once '../config.php';

// Verificar se o usuário é admin do SaaS
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin_saas') {
    header("Location: ../index.php");
    exit();
}

// Processar ações de empresas
$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

// Listar empresas
try {
    $stmt = $pdo->query("
        SELECT 
            id, 
            nome, 
            email_contato AS email, 
            data_cadastro AS data_criacao,
            logo_url
        FROM empresas
        ORDER BY id DESC
    ");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar empresas: " . $e->getMessage();
    $empresas = [];
}

// Processar exclusão de empresa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_empresa'])) {
    $empresa_id = filter_input(INPUT_POST, 'empresa_id', FILTER_VALIDATE_INT);
    
    if ($empresa_id) {
        try {
            // Iniciar transação para exclusão segura
            $pdo->beginTransaction();
            
            // Excluir usuários da empresa
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE empresa_id = ?");
            $stmt->execute([$empresa_id]);
            
            // Excluir agendamentos da empresa
            $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE empresa_id = ?");
            $stmt->execute([$empresa_id]);
            
            // Excluir empresa
            $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = ?");
            $stmt->execute([$empresa_id]);
            
            $pdo->commit();
            
            // Redirecionar com mensagem de sucesso
            $_SESSION['mensagem'] = "Empresa excluída com sucesso!";
            header("Location: empresas.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            
            // Redirecionar com mensagem de erro
            $_SESSION['erro'] = "Erro ao excluir empresa: " . $e->getMessage();
            header("Location: empresas.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Empresas - SaaS Admin</title>
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
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
            }
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.075);
            transition: background-color 0.3s;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-4">Gerenciar Empresas</h1>
                    
                    <?php if ($mensagem): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($mensagem); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($erro); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Lista de Empresas</span>
                            <a href="nova_empresa.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Nova Empresa
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>E-mail</th>
                                            <th>Data Criação</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($empresas as $empresa): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $logo_url = !empty($empresa['logo_url']) 
                                                    ? '../' . htmlspecialchars($empresa['logo_url']) 
                                                    : '../assets/img/logo_padrao.svg';
                                                ?>
                                                <img src="<?php echo $logo_url; ?>" 
                                                     alt="Logo <?php echo htmlspecialchars($empresa['nome']); ?>" 
                                                     class="rounded" 
                                                     style="width: 50px; height: 50px; object-fit: cover; background-color: #f8f9fa;"
                                                     onerror="this.src='../assets/img/logo_padrao.svg'; this.onerror=null;"
                                                     loading="lazy">
                                            </td>
                                            <td><?php echo htmlspecialchars($empresa['id']); ?></td>
                                            <td><?php echo htmlspecialchars($empresa['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($empresa['email']); ?></td>
                                            <td><?php 
                                                // Fallback for data_criacao if column doesn't exist
                                                $data_criacao = !empty($empresa['data_criacao']) 
                                                    ? date('d/m/Y H:i', strtotime($empresa['data_criacao']))
                                                    : 'Não disponível';
                                                echo htmlspecialchars($data_criacao); 
                                            ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="editar_empresa.php?id=<?php echo $empresa['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta empresa?');" class="d-inline">
                                                        <input type="hidden" name="empresa_id" value="<?php echo $empresa['id']; ?>">
                                                        <button type="submit" name="excluir_empresa" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
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
