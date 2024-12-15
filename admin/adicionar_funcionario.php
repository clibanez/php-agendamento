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
    header("Location: index.php");
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
        header("Location: index.php");
        exit();
    }

    debug_log("Empresa encontrada: " . print_r($empresa, true));
} catch (PDOException $e) {
    debug_log("Database error: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao buscar empresa.";
    header("Location: index.php");
    exit();
}

// Processar formulário de cadastro de funcionário
$erro = '';
$sucesso = '';
$imagem_url = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar e sanitizar inputs
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        $senha = $_POST['senha'];
        $confirma_senha = $_POST['confirma_senha'];
        
        // Definindo o tipo de usuário como 'profissional'
        $tipo_usuario = 'profissional'; 
        $tipo_acesso = 'cliente'; // Ajuste conforme necessário

        // Validações básicas
        if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        if ($senha !== $confirma_senha) {
            throw new Exception("As senhas não coincidem.");
        }

        // Upload de imagem
        if (isset($_FILES['imagem_perfil']) && $_FILES['imagem_perfil']['error'] == 0) {
            // Preparar diretório de upload
            $empresa_nome_sanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '', $empresa['nome']);
            
            // Criar diretório de upload
            $upload_dir = "../uploads/empresas/{$empresa_nome_sanitizado}/funcionario-foto/";
            
            // Criar diretório se não existir
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Gerar nome de arquivo único
            $extensao = pathinfo($_FILES['imagem_perfil']['name'], PATHINFO_EXTENSION);
            $nome_arquivo = uniqid() . '_funcionario.' . $extensao;
            $caminho_completo = $upload_dir . $nome_arquivo;

            // Validar e mover arquivo
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tamanho_maximo = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['imagem_perfil']['type'], $tipos_permitidos)) {
                throw new Exception("Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.");
            }

            if ($_FILES['imagem_perfil']['size'] > $tamanho_maximo) {
                throw new Exception("Arquivo muito grande. Limite de 5MB.");
            }

            // Mover arquivo
            if (move_uploaded_file($_FILES['imagem_perfil']['tmp_name'], $caminho_completo)) {
                // Caminho relativo para salvar no banco
                $imagem_url = "uploads/empresas/{$empresa_nome_sanitizado}/funcionario-foto/{$nome_arquivo}"; // Ajuste aqui
            } else {
                throw new Exception("Erro ao fazer upload da imagem.");
            }
        }

        // Verificar se o email já existe
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            throw new Exception("Este email já está cadastrado.");
        }

        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

        // Inserir funcionário
        $stmt = $pdo->prepare("
            INSERT INTO usuarios 
            (nome, email, telefone, senha, tipo_usuario, tipo_acesso, empresa_id, data_criacao, imagem_perfil_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $nome, 
            $email, 
            $telefone,
            $senha_hash, 
            $tipo_usuario, // Usando 'profissional' como padrão
            $tipo_acesso,
            $empresa_id,
            $imagem_url
        ]);

        $sucesso = "Cadastro realizado com sucesso!";
        
        // Redirecionar para página de login com mensagem de sucesso
        $_SESSION['sucesso'] = $sucesso;
        header("Location: ../index.php");
        exit();

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Verificar se é uma edição de usuário
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_usuario = $_GET['id'];
    
    // Buscar dados do usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id AND empresa_id = :empresa_id");
    $stmt->execute([
        ':id' => $id_usuario,
        ':empresa_id' => $empresa['id']
    ]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        $telefone_existente = $usuario['telefone'] ?? '';
        // Resto do código de preenchimento de formulário
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Funcionário - <?php echo htmlspecialchars($empresa['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-masker/1.2.0/vanilla-masker.min.js"></script>
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
../            background: linear-gradient(135deg, var(--background-gradient-start), var(--background-gradient-end));
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Poppins', 'Arial', sans-serif;
            color: var(--text-color);
            overflow: hidden; /* Prevent scrollbars */
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            width: 100%;
            padding: 0;
        }
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px; /* Slightly reduced for better mobile view */
            margin: 0 auto;
            animation: fadeIn 0.5s ease-out;
            border: 2px solid var(--secondary-color);
        }
        .form-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
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

        @media (max-width: 576px) {
            .circular-avatar-wrapper {
                width: 120px;
                height: 120px;
            }
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

        /* Smooth touch interactions */
        @media (pointer: coarse) {
            .circular-avatar-wrapper:active {
                transform: scale(0.98);
                transition: transform 0.1s ease;
            }
        }

        /* Telephone input styling */
        .tel-input-container {
            position: relative;
        }

        .tel-input-container .input-group-text {
            background-color: transparent;
            border-right: none;
            color: var(--primary-color);
        }

        .tel-input-container .form-control {
            border-left: none;
            box-shadow: none;
        }

        .tel-input-container .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
        }

        .tel-input-container .input-group-text i {
            transition: color 0.3s ease;
        }

        .tel-input-container .form-control:focus + .input-group-text i {
            color: var(--primary-color);
        }

        .tel-input-container .validation-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #28a745;
            display: none;
        }

        .tel-input-container.is-valid .validation-icon {
            display: block;
        }

        .tel-input-container .invalid-feedback {
            display: none;
        }

        .tel-input-container.is-invalid .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">
                <?php echo htmlspecialchars($empresa['nome']); ?>
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
                               name="imagem_perfil" 
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
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" 
                               required placeholder="Nome Completo">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" 
                               required placeholder="E-mail">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="tel" 
                               class="form-control" 
                               id="telefone" 
                               name="telefone" 
                               value="<?php 
                                   // Check if telefone_existente is defined and not empty
                                   $telefone_existente = isset($telefone_existente) ? $telefone_existente : '';
                                   echo $telefone_existente 
                                       ? htmlspecialchars(formatarTelefone($telefone_existente)) 
                                       : ''; 
                               ?>"
                               pattern="\(\d{2}\) \d{4,5}-\d{4}"
                               title="Formato: (99) 99999-9999"
                               placeholder="Telefone (Opcional)">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="senha" name="senha" 
                               required placeholder="Senha">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" 
                               required placeholder="Confirmar Senha">
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Cadastrar Funcionário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const telefoneInput = document.getElementById('telefone');

            telefoneInput.addEventListener('input', function(e) {
                // Remove non-numeric characters
                let phoneNumber = this.value.replace(/\D/g, '');
                
                // Format phone number
                if (phoneNumber.length > 0) {
                    // Limit to 11 digits (Brazilian phone number)
                    phoneNumber = phoneNumber.slice(0, 11);
                    
                    // Format with parentheses and hyphen
                    let formattedNumber = '(' + phoneNumber.slice(0, 2);
                    if (phoneNumber.length > 2) {
                        // Check if it's a 9-digit mobile number
                        if (phoneNumber.length === 11 || phoneNumber.length === 10) {
                            formattedNumber += ') ' + phoneNumber.slice(2, 7);
                            if (phoneNumber.length > 7) {
                                formattedNumber += '-' + phoneNumber.slice(7);
                            }
                        } else {
                            // For shorter numbers
                            formattedNumber += ') ' + phoneNumber.slice(2, 6);
                            if (phoneNumber.length > 6) {
                                formattedNumber += '-' + phoneNumber.slice(6);
                            }
                        }
                    }
                    
                    this.value = formattedNumber;
                }
            });

            // Prevent non-numeric input
            telefoneInput.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which);
                if (!/^\d+$/.test(char)) {
                    e.preventDefault();
                }
            });

            // Handle paste event
            telefoneInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = e.clipboardData.getData('text/plain').replace(/\D/g, '');
                const currentValue = this.value.replace(/\D/g, '');
                const newValue = (currentValue + pastedText).slice(0, 11);
                
                // Reformat the number
                let formattedNumber = '(' + newValue.slice(0, 2);
                if (newValue.length > 2) {
                    // Check if it's a 9-digit mobile number
                    if (newValue.length === 11 || newValue.length === 10) {
                        formattedNumber += ') ' + newValue.slice(2, 7);
                        if (newValue.length > 7) {
                            formattedNumber += '-' + newValue.slice(7);
                        }
                    } else {
                        // For shorter numbers
                        formattedNumber += ') ' + newValue.slice(2, 6);
                        if (newValue.length > 6) {
                            formattedNumber += '-' + newValue.slice(6);
                        }
                    }
                }
                
                this.value = formattedNumber;
            });

            // Avatar upload handling
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

                            // Calculate scaling and positioning
                            const aspectRatio = img.width / img.height;
                            let drawWidth, drawHeight, offsetX = 0, offsetY = 0;

                            if (aspectRatio > 1) {
                                // Wide image
                                drawHeight = 300;
                                drawWidth = drawHeight * aspectRatio;
                                offsetX = -(drawWidth - 300) / 2;
                            } else {
                                // Tall image
                                drawWidth = 300;
                                drawHeight = drawWidth / aspectRatio;
                                offsetY = -(drawHeight - 300) / 2;
                            }

                            // Fill background with white to prevent transparency
                            ctx.fillStyle = 'white';
                            ctx.fillRect(0, 0, 300, 300);

                            // Draw image
                            ctx.drawImage(
                                img, 
                                offsetX, offsetY, 
                                drawWidth, drawHeight
                            );

                            // Convert to data URL
                            const circularImageUrl = canvas.toDataURL('image/jpeg');
                            
                            // Update preview background
                            avatarPreviewImage.style.backgroundImage = `url(${circularImageUrl})`;
                            avatarPreviewContainer.classList.add('has-image');

                            // Convert data URL back to file
                            fetch(circularImageUrl)
                                .then(res => res.blob())
                                .then(blob => {
                                    const newFile = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(newFile);
                                    avatarInput.files = dataTransfer.files;
                                });
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

    <!-- Include Font Awesome for upload icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</body>
</html> 