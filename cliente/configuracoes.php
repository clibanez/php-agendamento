<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$erro = '';
$sucesso = '';

// Buscar dados do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar dados do usuário: " . $e->getMessage();
}

// Processar formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verifica se é uma atualização de foto
        if (isset($_FILES['nova_foto']) && $_FILES['nova_foto']['error'] == 0) {
            // Buscar nome da empresa do usuário logado
            $stmt = $pdo->prepare("
                SELECT e.nome 
                FROM empresas e 
                INNER JOIN usuarios u ON u.empresa_id = e.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Sanitizar nome da empresa
            $empresa_nome_sanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '', $empresa['nome']);
            
            // Definir diretório de upload
            $upload_dir = "../uploads/empresas/{$empresa_nome_sanitizado}/cliente-foto/";
            
            // Criar diretório se não existir
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Processar upload
            $extensao = strtolower(pathinfo($_FILES['nova_foto']['name'], PATHINFO_EXTENSION));
            $nome_arquivo = uniqid() . '_cliente.' . $extensao;
            $caminho_completo = $upload_dir . $nome_arquivo;
            $caminho_banco = "uploads/empresas/{$empresa_nome_sanitizado}/cliente-foto/" . $nome_arquivo;

            if (move_uploaded_file($_FILES['nova_foto']['tmp_name'], $caminho_completo)) {
                // Atualizar apenas a foto no banco
                $stmt = $pdo->prepare("UPDATE usuarios SET imagem_perfil_url = ? WHERE id = ?");
                $stmt->execute([$caminho_banco, $usuario_id]);
                $sucesso = "Foto atualizada com sucesso!";
                
                // Atualizar dados do usuário na sessão
                $usuario['imagem_perfil_url'] = $caminho_banco;
            }
        } else {
            // Atualização normal dos dados (sem foto)
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
            
            // Atualizar dados no banco
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $telefone, $usuario_id]);
            $sucesso = "Dados atualizados com sucesso!";
        }

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações da Conta</title>
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
            padding: 20px;
        }

        .config-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            padding: 30px;
            border: 2px solid var(--secondary-color);
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin: 0 auto 20px;
            display: block;
        }

        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border-color: var(--secondary-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: bold;
        }

        .btn-voltar {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .btn-voltar:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(-5px);
        }

        .profile-image-container {
            text-align: center;
            margin: 20px 0;
        }

        .profile-image-label {
            cursor: pointer;
            display: inline-block;
        }

        .profile-image-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .camera-icon {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            padding: 8px 0;
            color: white;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .profile-image-wrapper:hover .camera-icon {
            opacity: 1;
        }

        .profile-image-wrapper:hover .profile-image {
            filter: brightness(0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="config-container">
            <!-- Botão Voltar -->
            <a href="dashboard.php" class="btn btn-voltar mb-4">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
            </a>

            <h2 class="text-center mb-4">Configurações da Conta</h2>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $sucesso; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Seção: Foto do Perfil -->
                <div class="mb-4">
                    <h4 class="section-title">
                        <i class="fas fa-camera me-2"></i>Foto do Perfil
                    </h4>
                    <div class="profile-image-container">
                        <label for="nova_foto" class="profile-image-label">
                            <div class="profile-image-wrapper">
                                <img src="<?php 
                                    if (!empty($usuario['imagem_perfil_url'])) {
                                        echo '../' . $usuario['imagem_perfil_url'];
                                    } else {
                                        echo '../assets/img/default-avatar.png';
                                    }
                                ?>" 
                                alt="Foto de Perfil" 
                                id="preview-image"
                                class="profile-image">
                                <div class="camera-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </div>
                        </label>
                        <input type="file" class="d-none" id="nova_foto" name="nova_foto" accept="image/*">
                    </div>
                </div>

                <!-- Seção: Informações Básicas -->
                <div class="mb-4">
                    <h4 class="section-title">
                        <i class="fas fa-user me-2"></i>Informações Básicas
                    </h4>
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="tel" class="form-control" id="telefone" name="telefone" 
                               value="<?php echo htmlspecialchars($usuario['telefone']); ?>">
                    </div>
                </div>

                <!-- Seção: Alterar Senha -->
                <div class="mb-4">
                    <h4 class="section-title">
                        <i class="fas fa-lock me-2"></i>Alterar Senha
                    </h4>
                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                    </div>
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                    </div>
                    <div class="mb-3">
                        <label for="confirma_senha" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirma_senha" name="confirma_senha">
                    </div>
                </div>

                <!-- Botão Salvar -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = '(' + value;
                if (value.length > 3) {
                    value = value.substring(0,3) + ') ' + value.substring(3);
                }
                if (value.length > 10) {
                    value = value.substring(0,10) + '-' + value.substring(10,14);
                }
            }
            e.target.value = value;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const inputFoto = document.getElementById('nova_foto');
            const formFoto = document.getElementById('formFoto');

            inputFoto.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Criar um FormData apenas para a foto
                    const formData = new FormData();
                    formData.append('nova_foto', file);

                    // Enviar via AJAX
                    fetch('configuracoes.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Atualizar a imagem na página
                        const previewImage = document.getElementById('preview-image');
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                        }
                        
                        reader.readAsDataURL(file);
                        
                        // Mostrar mensagem de sucesso
                        // Você pode adicionar um elemento para mostrar mensagens
                        alert('Foto atualizada com sucesso!');
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao atualizar a foto');
                    });
                }
            });
        });
    </script>
</body>
</html>
