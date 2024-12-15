<?php
// Função para obter usuário por ID
function getUserById($usuario_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, e.nome AS empresa_nome 
            FROM usuarios u
            JOIN empresas e ON u.empresa_id = e.id
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar usuário: " . $e->getMessage());
        return false;
    }
}

// Função para sanitizar entradas
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Função para validar e-mail
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para gerar hash de senha seguro
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

// Função para verificar senha
function verifyPassword($inputPassword, $storedHash) {
    return password_verify($inputPassword, $storedHash);
}

// Função para gerar token de redefinição de senha
function generatePasswordResetToken() {
    return bin2hex(random_bytes(32));
}

// Função para formatar telefone (se ainda não estiver definida)
if (!function_exists('formatarTelefone')) {
    function formatarTelefone($telefone) {
        $telefone = preg_replace('/\D/', '', $telefone);
        
        if (strlen($telefone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($telefone, 0, 2), 
                substr($telefone, 2, 4), 
                substr($telefone, 6)
            );
        } elseif (strlen($telefone) === 11) {
            return sprintf('(%s) %s-%s', 
                substr($telefone, 0, 2), 
                substr($telefone, 2, 5), 
                substr($telefone, 7)
            );
        }
        
        return $telefone;
    }
}

// Função para gerar slug amigável
function generateSlug($string) {
    $string = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Função para registrar log de atividades
function logActivity($usuario_id, $acao, $detalhes = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_atividades 
            (usuario_id, acao, detalhes, data_hora) 
            VALUES (:usuario_id, :acao, :detalhes, NOW())
        ");
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'acao' => $acao,
            'detalhes' => $detalhes
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

// Função para enviar e-mail
function enviarEmail($para, $assunto, $corpo, $de = null) {
    // Implementação básica, pode ser substituída por biblioteca mais robusta
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    
    if ($de) {
        $headers .= "From: " . $de . "\r\n";
    }
    
    return mail($para, $assunto, $corpo, $headers);
}

// Função para gerar código de verificação
function gerarCodigoVerificacao($length = 6) {
    return str_pad(random_int(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

// Função para calcular idade
function calcularIdade($dataNascimento) {
    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($nascimento);
    return $idade->y;
}

// Função para validar CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11 || preg_match('/^(.)\1*$/', $cpf)) {
        return false;
    }
    
    // Calcula os dígitos verificadores
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

// Função para limitar texto
function limitarTexto($texto, $limite = 100, $sufixo = '...') {
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    return substr($texto, 0, $limite) . $sufixo;
}

// Função para converter data para formato brasileiro
function dataParaBrasil($data) {
    return date('d/m/Y', strtotime($data));
}

// Função para converter data do formato brasileiro para MySQL
function dataDoBrasil($data) {
    $partes = explode('/', $data);
    if (count($partes) === 3) {
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }
    return null;
}
