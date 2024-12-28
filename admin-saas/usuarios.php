<?php
session_start();
require_once '../config.php';

// Verificar se o usuário é admin do SaaS
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'], ['admin_saas', 'admin', 'gerente'])) {
    header("Location: ../index.php");
    exit();
}

// Processar exclusão de usuário
if (isset($_GET['action']) && $_GET['action'] === 'deletar' && isset($_GET['id'])) {
    try {
        $usuario_id = intval($_GET['id']);
        
        // Verificar se o usuário atual tem permissão para deletar
        if (!in_array($_SESSION['usuario_tipo'], ['admin_saas', 'admin', 'gerente'])) {
            $_SESSION['erro'] = "Você não tem permissão para excluir usuários.";
            header("Location: usuarios.php");
            exit();
        }

        // Se for admin ou gerente, só pode deletar usuários da mesma empresa
        $stmt_check = $pdo->prepare("
            SELECT empresa_id 
            FROM usuarios 
            WHERE id = ? AND 
            (
                (? = 'admin_saas') OR 
                (? IN ('admin', 'gerente') AND empresa_id = ?)
            )
        ");
        $stmt_check->execute([
            $usuario_id, 
            $_SESSION['usuario_tipo'], 
            $_SESSION['usuario_tipo'], 
            $_SESSION['empresa_id']
        ]);
        $usuario_permitido = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$usuario_permitido) {
            $_SESSION['erro'] = "Você não tem permissão para excluir este usuário.";
            header("Location: usuarios.php");
            exit();
        }

        // Preparar e executar a exclusão
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['sucesso'] = "Usuário excluído com sucesso!";
        } else {
            $_SESSION['erro'] = "Usuário não encontrado ou já excluído.";
        }
        
        header("Location: usuarios.php");
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir usuário: " . $e->getMessage());
        $_SESSION['erro'] = "Erro ao excluir usuário. Tente novamente.";
        header("Location: usuarios.php");
        exit();
    }
}

// Modificar a consulta de usuários para respeitar permissões
try {
    if ($_SESSION['usuario_tipo'] === 'admin_saas') {
        $stmt = $pdo->query("
            SELECT u.id, u.nome, u.email, u.tipo_usuario, e.nome as empresa_nome 
            FROM usuarios u
            JOIN empresas e ON u.empresa_id = e.id
            ORDER BY e.nome, u.nome
        ");
    } else {
        // Para admin e gerente, mostrar apenas usuários da mesma empresa
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.email, u.tipo_usuario, e.nome as empresa_nome 
            FROM usuarios u
            JOIN empresas e ON u.empresa_id = e.id
            WHERE u.empresa_id = ?
            ORDER BY u.nome
        ");
        $stmt->execute([$_SESSION['empresa_id']]);
    }
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
    error_log("Erro ao buscar usuários: " . $e->getMessage());
}

// Verificar e exibir mensagens de sucesso ou erro
if (isset($_SESSION['sucesso'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['sucesso']) . '</div>';
    unset($_SESSION['sucesso']);
}
if (isset($_SESSION['erro'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['erro']) . '</div>';
    unset($_SESSION['erro']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - SaaS Admin</title>
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
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-4">Usuários do Sistema</h1>
                    
                    <div class="card">
                        <div class="card-header">
                            Lista de Usuários
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>E-mail</th>
                                            <th>Tipo de Usuário</th>
                                            <th>Empresa</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['empresa_nome']); ?></td>
                                            <td>
                                                <a href="usuarios.php?action=deletar&id=<?php echo $usuario['id']; ?>" class="btn btn-danger btn-sm">Excluir</a>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Capturar todos os botões de exclusão
            const deleteButtons = document.querySelectorAll('.btn-danger');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const deleteUrl = this.getAttribute('href');
                    const userName = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                    
                    // Criar modal de confirmação
                    const modalHtml = `
                    <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmar Exclusão</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    Tem certeza que deseja excluir o usuário <strong>${userName}</strong>?
                                    Esta ação não pode ser desfeita.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <a href="${deleteUrl}" class="btn btn-danger">Confirmar Exclusão</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;
                    
                    // Remover modal existente
                    const existingModal = document.getElementById('confirmDeleteModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    // Adicionar modal ao body
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    
                    // Mostrar modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>
