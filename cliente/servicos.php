<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Buscar os serviços do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM servicos WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
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
    <title>Serviços</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container mt-5">
        <h2>Serviços</h2>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Serviço</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servicos)): ?>
                    <tr>
                        <td colspan="4">Nenhum serviço encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($servicos as $servico): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($servico['id']); ?></td>
                            <td><?php echo htmlspecialchars($servico['nome']); ?></td>
                            <td><?php echo htmlspecialchars($servico['descricao']); ?></td>
                            <td>
                                <a href="editar_servico.php?id=<?php echo $servico['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="excluir_servico.php?id=<?php echo $servico['id']; ?>" class="btn btn-danger btn-sm">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
