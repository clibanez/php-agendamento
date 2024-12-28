<?php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Função para validar e redimensionar imagem
function processarImagem($arquivo, $tipo, $id) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validações
    if (!in_array($arquivo['type'], $allowed_types)) {
        return ['error' => 'Tipo de arquivo inválido. Use JPEG, PNG ou WebP.'];
    }

    if ($arquivo['size'] > $max_size) {
        return ['error' => 'Arquivo muito grande. Máximo de 5MB.'];
    }

    // Criar diretório de uploads se não existir
    $upload_dir = 'uploads/' . ($tipo === 'empresa' ? 'logos/' : 'perfis/');
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Gerar nome único para o arquivo
    $file_extension = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $novo_nome = $tipo . '_' . $id . '.' . $file_extension;
    $caminho_destino = $upload_dir . $novo_nome;

    // Redimensionar imagem
    $imagem = match($arquivo['type']) {
        'image/jpeg' => imagecreatefromjpeg($arquivo['tmp_name']),
        'image/png' => imagecreatefrompng($arquivo['tmp_name']),
        'image/webp' => imagecreatefromwebp($arquivo['tmp_name'])
    };

    $largura_original = imagesx($imagem);
    $altura_original = imagesy($imagem);
    $nova_largura = 500;
    $nova_altura = ($altura_original / $largura_original) * $nova_largura;

    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);
    
    // Preservar transparência para PNG
    if ($arquivo['type'] === 'image/png') {
        imagecolortransparent($nova_imagem, imagecolorallocatealpha($nova_imagem, 0, 0, 0, 127));
        imagealphablending($nova_imagem, false);
        imagesavealpha($nova_imagem, true);
    }

    imagecopyresampled($nova_imagem, $imagem, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_original, $altura_original);

    // Salvar imagem
    match($arquivo['type']) {
        'image/jpeg' => imagejpeg($nova_imagem, $caminho_destino, 85),
        'image/png' => imagepng($nova_imagem, $caminho_destino, 8),
        'image/webp' => imagewebp($nova_imagem, $caminho_destino, 85)
    };

    // Liberar memória
    imagedestroy($imagem);
    imagedestroy($nova_imagem);

    return ['success' => true, 'url' => $caminho_destino];
}

// Processar upload de logo da empresa
if (isset($_FILES['logo_empresa']) && $_SESSION['usuario_tipo_acesso'] === 'admin_empresa') {
    $resultado = processarImagem($_FILES['logo_empresa'], 'empresa', $_SESSION['empresa_id']);
    
    if (isset($resultado['success'])) {
        try {
            $stmt = $pdo->prepare("UPDATE empresas SET logo_url = ? WHERE id = ?");
            $stmt->execute([$resultado['url'], $_SESSION['empresa_id']]);
            echo json_encode(['success' => true, 'url' => $resultado['url']]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao salvar logo: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode($resultado);
    }
    exit();
}

// Processar upload de imagem de perfil
if (isset($_FILES['imagem_perfil'])) {
    $resultado = processarImagem($_FILES['imagem_perfil'], 'perfil', $_SESSION['usuario_id']);
    
    if (isset($resultado['success'])) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET imagem_perfil_url = ? WHERE id = ?");
            $stmt->execute([$resultado['url'], $_SESSION['usuario_id']]);
            echo json_encode(['success' => true, 'url' => $resultado['url']]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao salvar imagem de perfil: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode($resultado);
    }
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Nenhum upload processado']);
