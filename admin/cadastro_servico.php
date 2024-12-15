<?php
session_start();
require_once '../config.php';

// Extensive error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file for debugging
$log_file = '/tmp/cadastro_debug.log';

// Function to log debug information
function debug_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Log all relevant information
debug_log("Script started");
debug_log("SESSION: " . print_r($_SESSION, true));
debug_log("GET: " . print_r($_GET, true));

// Obter o ID da empresa de múltiplas fontes
$empresa_id = null;

// Prioridade 1: GET parameter
if (isset($_GET['empresa_id'])) {
    $empresa_id = intval($_GET['empresa_id']);
}

// Prioridade 2: Session variable
if (!$empresa_id && isset($_SESSION['empresa_id'])) {
    $empresa_id = intval($_SESSION['empresa_id']);
}

debug_log("Empresa ID (Final): $empresa_id");

// Validar empresa_id
if (!$empresa_id) {
    debug_log("Empresa ID não encontrado");
    $_SESSION['erro'] = "Empresa inválida ou não autorizada.";
    header("Location: ../index.php");
    exit();
}

// Buscar detalhes da empresa
try {
    $stmt_empresa = $pdo->prepare("SELECT nome FROM empresas WHERE id = ?");
    $stmt_empresa->execute([$empresa_id]);
    $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        debug_log("Empresa not found - ID: $empresa_id");
        $_SESSION['erro'] = "Empresa não encontrada.";
        header("Location: ../index.php");
        exit();
    }

    debug_log("Empresa encontrada: " . print_r($empresa, true));
} catch (PDOException $e) {
    debug_log("Database error: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao buscar empresa.";
    header("Location: ../index.php");
    exit();
}

// Processar formulário de cadastro de serviço
$erro = '';
$sucesso = '';
$imagem_url = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar e sanitizar inputs
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
        $preco = filter_input(INPUT_POST, 'preco', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $duracao_minutos = filter_input(INPUT_POST, 'duracao_minutos', FILTER_SANITIZE_NUMBER_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

        // Validações básicas
        if (empty($nome) || empty($descricao) || empty($preco) || empty($duracao_minutos)) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        // Upload de imagem
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
            // Preparar diretório de upload
            $empresa_nome_sanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '', $empresa['nome']);
            
            // Criar diretório de upload
            $upload_dir = "../uploads/empresas/{$empresa_nome_sanitizado}/servicos/";
            
            // Criar diretório se não existir
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Gerar nome de arquivo único
            $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $nome_arquivo = uniqid() . '_servico.' . $extensao;
            $caminho_completo = $upload_dir . $nome_arquivo;

            // Validar e mover arquivo
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tamanho_maximo = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['imagem']['type'], $tipos_permitidos)) {
                throw new Exception("Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.");
            }

            if ($_FILES['imagem']['size'] > $tamanho_maximo) {
                throw new Exception("Arquivo muito grande. Limite de 5MB.");
            }

            // Mover arquivo
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_completo)) {
                // Caminho relativo para salvar no banco
                $imagem_url = $caminho_completo;
                debug_log("Imagem salva em: $imagem_url");
            } else {
                throw new Exception("Erro ao fazer upload da imagem.");
            }
        }

        // Inserir serviço
        $stmt = $pdo->prepare("
            INSERT INTO servicos 
            (nome, descricao, preco, duracao_minutos, status, empresa_id, imagem_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nome, 
            $descricao, 
            $preco, 
            $duracao_minutos, 
            $status, 
            $empresa_id,
            $imagem_url
        ]);

        debug_log("Serviço adicionado com sucesso!");

        $sucesso = "Cadastro realizado com sucesso!";
        
        // Redirecionar para página de serviços com mensagem de sucesso
        $_SESSION['sucesso'] = $sucesso;
        header("Location: servicos.php");
        exit();

    } catch (Exception $e) {
        debug_log("Erro ao adicionar serviço: " . $e->getMessage());
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Serviço - <?php echo htmlspecialchars($empresa['nome']); ?></title>
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
            width: 150px;
            height: 150px;
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
                Cadastrar Serviço - <?php echo htmlspecialchars($empresa['nome']); ?>
            </h2>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($sucesso); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="cadastroForm">
                <div class="circular-upload-container mb-4">
                    <div class="circular-avatar-wrapper" id="avatar-preview-container">
                        <input type="file" 
                               id="circular-avatar-input" 
                               name="imagem" 
                               accept="image/*" 
                               class="avatar-file-input">
                        
                        <div class="circular-avatar-preview" id="avatar-preview-image"></div>
                        
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
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço</label>
                    <input type="number" class="form-control" id="preco" name="preco" step="0.01" required>
                </div>

                <div class="mb-3">
                    <label for="duracao_minutos" class="form-label">Duração (em minutos)</label>
                    <input type="number" class="form-control" id="duracao_minutos" name="duracao_minutos" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Cadastrar Serviço
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
