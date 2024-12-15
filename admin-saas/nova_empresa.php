<?php
session_start();
require_once '../config.php';

// Verificar se o usuário é admin do SaaS
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin_saas') {
    header("Location: ../index.php");
    exit();
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filtrar e validar inputs da empresa
    $nome_empresa = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email_empresa = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $cnpj = filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $endereco = filter_input(INPUT_POST, 'endereco', FILTER_SANITIZE_STRING);

    // Filtrar inputs do admin da empresa
    $nome_admin = filter_input(INPUT_POST, 'nome_admin', FILTER_SANITIZE_STRING);
    $email_admin = filter_input(INPUT_POST, 'email_admin', FILTER_VALIDATE_EMAIL);
    $senha_admin = $_POST['senha_admin'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validações
    $erros = [];
    if (empty($nome_empresa) || empty($email_empresa) || empty($cnpj)) {
        $erros[] = "Preencha todos os campos obrigatórios da empresa.";
    }
    if (empty($nome_admin) || empty($email_admin) || empty($senha_admin)) {
        $erros[] = "Preencha todos os campos obrigatórios do administrador.";
    }
    if ($senha_admin !== $confirmar_senha) {
        $erros[] = "As senhas não coincidem.";
    }
    if (strlen($senha_admin) < 6) {
        $erros[] = "A senha deve ter no mínimo 6 caracteres.";
    }

    // Processamento do upload de logo
    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Criar diretório para logos de empresas se não existir
        $nome_empresa_slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $nome_empresa));
        $upload_dir = "../uploads/empresas/{$nome_empresa_slug}/";
        
        // Criar diretório específico para a empresa
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Gerar nome único para o arquivo
        $extensao = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = 'logo-' . uniqid() . '.' . $extensao;
        $caminho_completo = $upload_dir . $nome_arquivo;

        // Validar tipo de arquivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $tipo_arquivo = mime_content_type($_FILES['logo']['tmp_name']);
        
        if (in_array($tipo_arquivo, $tipos_permitidos)) {
            // Redimensionar imagem
            $imagem = null;
            switch ($tipo_arquivo) {
                case 'image/jpeg':
                    $imagem = imagecreatefromjpeg($_FILES['logo']['tmp_name']);
                    break;
                case 'image/png':
                    $imagem = imagecreatefrompng($_FILES['logo']['tmp_name']);
                    break;
                case 'image/gif':
                    $imagem = imagecreatefromgif($_FILES['logo']['tmp_name']);
                    break;
                case 'image/webp':
                    $imagem = imagecreatefromwebp($_FILES['logo']['tmp_name']);
                    break;
            }

            if ($imagem) {
                // Obter dimensões originais
                $largura_original = imagesx($imagem);
                $altura_original = imagesy($imagem);

                // Definir nova largura (máximo 500px)
                $nova_largura = 500;
                $nova_altura = ($altura_original / $largura_original) * $nova_largura;

                // Criar nova imagem
                $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

                // Configurar transparência para PNG e GIF
                if ($tipo_arquivo == 'image/png' || $tipo_arquivo == 'image/gif') {
                    imagecolortransparent($nova_imagem, imagecolorallocatealpha($nova_imagem, 0, 0, 0, 127));
                    imagealphablending($nova_imagem, false);
                    imagesavealpha($nova_imagem, true);
                }

                // Redimensionar
                imagecopyresampled($nova_imagem, $imagem, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_original, $altura_original);

                // Salvar imagem
                switch ($tipo_arquivo) {
                    case 'image/jpeg':
                        imagejpeg($nova_imagem, $caminho_completo, 85);
                        break;
                    case 'image/png':
                        imagepng($nova_imagem, $caminho_completo, 8);
                        break;
                    case 'image/gif':
                        imagegif($nova_imagem, $caminho_completo);
                        break;
                    case 'image/webp':
                        imagewebp($nova_imagem, $caminho_completo, 85);
                        break;
                }

                // Verificar se o arquivo foi salvo corretamente
                if (file_exists($caminho_completo)) {
                    // Caminho relativo para salvar no banco
                    $logo_url = str_replace('../', '', $caminho_completo);
                    
                    // Debug: Log detalhado
                    error_log("Logo da Empresa - Caminho completo: $caminho_completo");
                    error_log("Logo da Empresa - Caminho relativo para banco: $logo_url");
                    error_log("Logo da Empresa - Nome da Empresa: $nome_empresa");
                    error_log("Logo da Empresa - Diretório de upload: $upload_dir");
                } else {
                    error_log("ERRO: Falha ao salvar o logo da empresa");
                    $logo_url = null;
                }

                // Liberar memória
                imagedestroy($imagem);
                imagedestroy($nova_imagem);
            }
        } else {
            $erros[] = "Tipo de arquivo de imagem não permitido. Use JPEG, PNG, GIF ou WebP.";
        }
    }

    // Processar upload da foto do funcionário admin, se existir
    $imagem_funcionario_url = null;
    if (isset($_FILES['foto_funcionario']) && $_FILES['foto_funcionario']['error'] === UPLOAD_ERR_OK) {
        // Preparar diretório de upload
        $nome_empresa_slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $nome_empresa));
        $upload_dir = "../uploads/empresas/{$nome_empresa_slug}/funcionario-foto/";
        
        // Criar diretório se não existir
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Gerar nome de arquivo único
        $extensao = pathinfo($_FILES['foto_funcionario']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid() . '_funcionario.' . $extensao;
        $caminho_completo = $upload_dir . $nome_arquivo;

        // Validar tipo de arquivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $tipo_arquivo = mime_content_type($_FILES['foto_funcionario']['tmp_name']);
        
        if (in_array($tipo_arquivo, $tipos_permitidos)) {
            // Redimensionar imagem
            $imagem = null;
            switch ($tipo_arquivo) {
                case 'image/jpeg':
                    $imagem = imagecreatefromjpeg($_FILES['foto_funcionario']['tmp_name']);
                    break;
                case 'image/png':
                    $imagem = imagecreatefrompng($_FILES['foto_funcionario']['tmp_name']);
                    break;
                case 'image/gif':
                    $imagem = imagecreatefromgif($_FILES['foto_funcionario']['tmp_name']);
                    break;
                case 'image/webp':
                    $imagem = imagecreatefromwebp($_FILES['foto_funcionario']['tmp_name']);
                    break;
            }

            if ($imagem) {
                // Obter dimensões originais
                $largura_original = imagesx($imagem);
                $altura_original = imagesy($imagem);

                // Definir nova largura (máximo 500px)
                $nova_largura = 500;
                $nova_altura = ($altura_original / $largura_original) * $nova_largura;

                // Criar nova imagem
                $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

                // Configurar transparência para PNG e GIF
                if ($tipo_arquivo == 'image/png' || $tipo_arquivo == 'image/gif') {
                    imagecolortransparent($nova_imagem, imagecolorallocatealpha($nova_imagem, 0, 0, 0, 127));
                    imagealphablending($nova_imagem, false);
                    imagesavealpha($nova_imagem, true);
                }

                // Redimensionar
                imagecopyresampled($nova_imagem, $imagem, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_original, $altura_original);

                // Salvar imagem
                switch ($tipo_arquivo) {
                    case 'image/jpeg':
                        imagejpeg($nova_imagem, $caminho_completo, 85);
                        break;
                    case 'image/png':
                        imagepng($nova_imagem, $caminho_completo, 8);
                        break;
                    case 'image/gif':
                        imagegif($nova_imagem, $caminho_completo);
                        break;
                    case 'image/webp':
                        imagewebp($nova_imagem, $caminho_completo, 85);
                        break;
                }

                // Verificar se o arquivo foi salvo corretamente
                if (file_exists($caminho_completo)) {
                    // Caminho relativo para salvar no banco
                    $imagem_funcionario_url = str_replace('../', '', $caminho_completo);
                    
                    // Debug: Log detalhado
                    error_log("Foto Funcionário Admin - Caminho completo: $caminho_completo");
                    error_log("Foto Funcionário Admin - Caminho relativo para banco: $imagem_funcionario_url");
                } else {
                    error_log("ERRO: Falha ao salvar a foto do funcionário admin");
                    $imagem_funcionario_url = null;
                }

                // Liberar memória
                imagedestroy($imagem);
                imagedestroy($nova_imagem);
            }
        } else {
            $erros[] = "Tipo de arquivo de imagem do funcionário não permitido. Use JPEG, PNG, GIF ou WebP.";
        }
    }

    if (empty($erros)) {
        try {
            // Iniciar transação
            $pdo->beginTransaction();

            // Verificar se a empresa já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE email_contato = ? OR cnpj = ?");
            $stmt->execute([$email_empresa, $cnpj]);
            
            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Já existe uma empresa cadastrada com este e-mail ou CNPJ.";
            } else {
                // Inserir empresa
                $stmt = $pdo->prepare("
                    INSERT INTO empresas 
                    (nome, email_contato, cnpj, telefone, endereco, logo_url, data_cadastro, status) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'ativo')
                ");
                
                $stmt->execute([
                    $nome_empresa, 
                    $email_empresa, 
                    $cnpj, 
                    $telefone, 
                    $endereco,
                    $logo_url
                ]);

                // Recuperar ID da empresa recem-criada
                $empresa_id = $pdo->lastInsertId();

                // Hash da senha
                $senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);

                // Inserir usuário admin da empresa com foto, se disponível
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios 
                    (nome, email, senha, tipo_usuario, tipo_acesso, empresa_id, data_criacao, status, imagem_perfil_url) 
                    VALUES (?, ?, ?, 'admin', 'admin_empresa', ?, NOW(), 'ativo', ?)
                ");
                
                $stmt->execute([
                    $nome_admin,
                    $email_admin,
                    $senha_hash,
                    $empresa_id,
                    $imagem_funcionario_url
                ]);

                // Commit da transação
                $pdo->commit();

                $sucesso = "Empresa e administrador cadastrados com sucesso!";
                
                // Limpar dados sensíveis da sessão
                unset($_POST);
            }
        } catch (PDOException $e) {
            // Rollback em caso de erro
            $pdo->rollBack();
            $erros[] = "Erro ao cadastrar empresa: " . $e->getMessage();
        }
    }

    // Converter erros para mensagem única
    if (!empty($erros)) {
        $erro = implode("<br>", $erros);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Nova Empresa - SaaS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .logo-preview {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 15px;
            display: none;
        }
        .password-strength {
            font-size: 0.8em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Cadastrar Nova Empresa</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($erro): ?>
                            <div class="alert alert-danger"><?php echo $erro; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($sucesso): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Dados da Empresa</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Logo da Empresa</label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <img id="logo-preview" class="logo-preview" src="#" alt="Pré-visualização do logo">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="nome" class="form-label">Nome da Empresa *</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo htmlspecialchars($nome_empresa ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-mail da Empresa *</label>
                                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email_empresa ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="cnpj" class="form-label">CNPJ *</label>
                                        <input type="text" class="form-control" id="cnpj" name="cnpj" required value="<?php echo htmlspecialchars($cnpj ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="endereco" class="form-label">Endereço</label>
                                        <textarea class="form-control" id="endereco" name="endereco" rows="3"><?php echo htmlspecialchars($endereco ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Administrador da Empresa</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="nome_admin" class="form-label">Nome do Administrador *</label>
                                        <input type="text" class="form-control" id="nome_admin" name="nome_admin" required value="<?php echo htmlspecialchars($nome_admin ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email_admin" class="form-label">E-mail do Administrador *</label>
                                        <input type="email" class="form-control" id="email_admin" name="email_admin" required value="<?php echo htmlspecialchars($email_admin ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="senha_admin" class="form-label">Senha *</label>
                                        <input type="password" class="form-control" id="senha_admin" name="senha_admin" required>
                                        <div id="password-strength" class="password-strength text-muted"></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="foto_funcionario" class="form-label">Foto do Funcionário</label>
                                        <input type="file" class="form-control" id="foto_funcionario" name="foto_funcionario" accept="image/jpeg,image/png,image/gif,image/webp">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Cadastrar Empresa e Administrador
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="empresas.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar para Lista de Empresas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pré-visualização do logo
        document.getElementById('logo').addEventListener('change', function(e) {
            const preview = document.getElementById('logo-preview');
            const file = e.target.files[0];
            const reader = new FileReader();

            reader.onloadstart = function() {
                preview.style.display = 'none';
            }

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }

            if (file) {
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Força de senha
        document.getElementById('senha_admin').addEventListener('input', function(e) {
            const senha = e.target.value;
            const strengthDiv = document.getElementById('password-strength');
            
            let strength = 0;
            if (senha.length >= 6) strength++;
            if (senha.match(/[a-z]+/)) strength++;
            if (senha.match(/[A-Z]+/)) strength++;
            if (senha.match(/[0-9]+/)) strength++;
            if (senha.match(/[$@#&!]+/)) strength++;

            let strengthText = '';
            switch(strength) {
                case 0:
                case 1:
                    strengthText = '<span class="text-danger">Muito Fraca</span>';
                    break;
                case 2:
                case 3:
                    strengthText = '<span class="text-warning">Fraca</span>';
                    break;
                case 4:
                case 5:
                    strengthText = '<span class="text-success">Forte</span>';
                    break;
            }

            strengthDiv.innerHTML = 'Força da Senha: ' + strengthText;
        });

        // Máscara para CNPJ
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length <= 10) {
                value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
            } else {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>
