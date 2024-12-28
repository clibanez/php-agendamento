<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Buscar o serviço a ser editado
if (isset($_GET['id'])) {
    $servico_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = ?");
        $stmt->execute([$servico_id]);
        $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar serviço: " . $e->getMessage());
        header("Location: servicos.php");
        exit();
    }
} else {
    header("Location: servicos.php");
    exit();
}

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco = $_POST['preco'] ?? '';
    $duracao_minutos = $_POST['duracao_minutos'] ?? '';
    $status = $_POST['status'] ?? 'ativo';
    $imagem = $_FILES['imagem'] ?? null;

    try {
        // Atualizar os dados do serviço
        $stmt = $pdo->prepare("UPDATE servicos SET nome = ?, descricao = ?, preco = ?, duracao_minutos = ?, status = ? WHERE id = ?");
        $stmt->execute([$nome, $descricao, $preco, $duracao_minutos, $status, $servico_id]);

        // Se uma nova imagem for enviada, processar o upload
        if ($imagem && $imagem['error'] == 0) {
            $empresa_nome = $_SESSION['empresa_nome'];
            $target_dir = "../uploads/empresas/" . $empresa_nome . "/servicos/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $target_file = $target_dir . basename($imagem['name']);
            move_uploaded_file($imagem['tmp_name'], $target_file);
            // Atualizar o caminho da imagem no banco de dados
            $stmt = $pdo->prepare("UPDATE servicos SET imagem_url = ? WHERE id = ?");
            $stmt->execute([$target_file, $servico_id]);
        }

        header("Location: servicos.php");
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar serviço: " . $e->getMessage());
        $erro = "Erro ao atualizar serviço: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Serviço - <?php echo htmlspecialchars($servico['nome']); ?></title>
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Poppins', 'Arial', sans-serif;
            color: var(--text-color);
        }

         /* Voltar */
         .page-header {
            width: 20%;
            background: white;
            border-radius: 20px;
            padding: 10px; /* Diminuído para reduzir a altura */
            width: 100%;
            max-width: 450px; /* Slightly reduced for better mobile view */
            max-height: 90vh; /* Altura máxima do container */
            margin-top: 20%;       
            box-shadow: var(--card-shadow);
            border: 2px solid var(--secondary-color);
            margin: 0 auto;
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
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            padding: 20px; /* Diminuído para reduzir a altura */
            width: 100%;
            max-width: 450px; /* Slightly reduced for better mobile view */
            max-height: 90vh; /* Altura máxima do container */
            overflow-y: auto; /* Adiciona scroll se o conteúdo exceder a altura */
            margin: 0 auto;
            border: 2px solid var(--secondary-color);
        }
        .form-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px; /* Diminuído para reduzir a altura */
            font-weight: 600;
        }
        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border-color: var(--secondary-color);
        }
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 25px;
            padding: 12px;
            color: white;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
        }
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255,105,180,0.4);
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
        }
        .circular-upload-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .circular-avatar-wrapper {
            position: relative;
            width: 150px; /* Diminuído para reduzir o tamanho da imagem */
            height: 150px; /* Diminuído para reduzir o tamanho da imagem */
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            border: 4px solid #FF69B4;
            box-shadow: 0 10px 25px rgba(255,105,180,0.3);
            transition: all 0.3s ease;
        }

        .avatar-file-input {
            display: none;
        }

        .circular-avatar-preview {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: transform 0.3s ease;
        }

        .circular-avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .circular-avatar-wrapper:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 35px rgba(255,105,180,0.4);
        }

        .circular-avatar-wrapper:hover .circular-avatar-overlay {
            opacity: 1;
        }

        .overlay-content {
            text-align: center;
            color: white;
        }

        .overlay-content i {
            font-size: 40px;
            margin-bottom: 10px;
            color: rgba(255,255,255,0.9);
        }

        .overlay-content span {
            display: block;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
    <div class="page-header">
            <a href="servicos.php" class="btn btn-voltar me-2">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        <div class="form-container">
            <h2 class="form-title">
                Editar Serviço - <?php echo htmlspecialchars($servico['nome']); ?>
            </h2>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="editarForm">
                <div class="circular-upload-container mb-4">
                    <div class="circular-avatar-wrapper" id="avatar-preview-container">
                        <input type="file" 
                               id="circular-avatar-input" 
                               name="imagem" 
                               accept="image/*" 
                               class="avatar-file-input">
                        
                        <div class="circular-avatar-preview" id="avatar-preview-image" style="background-image: url('<?php echo htmlspecialchars($servico['imagem_url']); ?>');"></div>
                        
                        <div class="circular-avatar-overlay" id="avatar-preview-overlay">
                            <div class="overlay-content">
                                <i class="fas fa-upload"></i>
                                <span>Alterar Imagem</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Serviço</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($servico['nome']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" required><?php echo htmlspecialchars($servico['descricao']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço</label>
                    <input type="number" class="form-control" id="preco" name="preco" value="<?php echo htmlspecialchars($servico['preco']); ?>" step="0.01" required>
                </div>

                <div class="mb-3">
                    <label for="duracao_minutos" class="form-label">Duração (em minutos)</label>
                    <input type="number" class="form-control" id="duracao_minutos" name="duracao_minutos" value="<?php echo htmlspecialchars($servico['duracao_minutos']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="ativo" <?php echo $servico['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="inativo" <?php echo $servico['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const avatarInput = document.getElementById('circular-avatar-input');
            const avatarPreviewContainer = document.getElementById('avatar-preview-container');
            const avatarPreviewImage = document.getElementById('avatar-preview-image');
            const avatarPreviewOverlay = document.getElementById('avatar-preview-overlay');

            // Handle file selection
            avatarInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = new Image();
                        img.onload = function() {
                            // Create canvas for circular avatar
                            const canvas = document.createElement('canvas');
                            canvas.width = 300;
                            canvas.height = 300;
                            const ctx = canvas.getContext('2d');

                            // Create circular clip
                            ctx.beginPath();
                            ctx.arc(150, 150, 150, 0, Math.PI * 2);
                            ctx.closePath();
                            ctx.clip();

                            // Draw image
                            ctx.drawImage(img, 0, 0, 300, 300);

                            // Convert to data URL
                            const circularImageUrl = canvas.toDataURL('image/jpeg');
                            
                            // Update preview background
                            avatarPreviewImage.style.backgroundImage = `url(${circularImageUrl})`;
                            avatarPreviewContainer.classList.add('has-image');
                        };
                        img.src = e.target.result;
                    };
                    
                    reader.readAsDataURL(file);
                }
            });

            // Click on preview container to trigger file input
            avatarPreviewContainer.addEventListener('click', function() {
                avatarInput.click();
            });
        });
    </script>
</body>
</html>
