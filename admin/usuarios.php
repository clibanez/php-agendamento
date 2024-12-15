<?php
session_start();
require_once '../config.php';

// Verificar permissão de acesso
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'], ['admin', 'gerente'])) {
    header("Location: ../index.php");
    exit();
}

// Processar a remoção de um usuário
if (isset($_GET['remover'])) {
    $id_usuario = $_GET['remover'];
    
    // Preparar a consulta para deletar o usuário
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);

    // Definir uma mensagem de sucesso
    $_SESSION['sucesso'] = "Usuário removido com sucesso!";
    header("Location: usuarios.php"); // Redirecionar após a exclusão
    exit();
}

// Buscar usuários cadastrados
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE empresa_id = ?");
$stmt->execute([$_SESSION['empresa_id']]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - <?php echo htmlspecialchars($empresa_logada['nome']); ?></title>
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

        .usuarios-container {
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
            <a href="../cadastro.php" class="btn btn-custom me-2">
                <i class="fas fa-user-plus"></i> Cadastrar Cliente
            </a>
            <a href="adicionar_funcionario.php" class="btn btn-custom">
                <i class="fas fa-user-plus"></i> Cadastrar Funcionário
            </a>
        </div>

        <div class="usuarios-container">
            <h2 class="page-title">Gerenciar Usuários</h2>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Tipo de Usuário</th>
                        <th>Telefone</th>
                        <th>Empresa</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): 
                        // Verifica se a URL da imagem do perfil está definida e não está vazia
                        $imagem_url = !empty($usuario['imagem_perfil_url']) 
                            ? '../' . htmlspecialchars($usuario['imagem_perfil_url']) 
                            : '../assets/img/avatar_padrao.svg'; // URL padrão se a imagem não estiver disponível
                    ?>
                    <tr>
                        <td>
                            <img src="<?= $imagem_url ?>" alt="Foto" class="img-thumbnail" style="width: 50px; height: 50px;">
                        </td>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['empresa']); ?></td>
                        <td>
                            <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="usuarios.php?remover=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                <i class="fas fa-trash"></i>
                            </a>
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
