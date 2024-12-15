<?php
require_once 'config.php';

// Verificar se já existe um admin SaaS
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin_saas'");
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        die("Já existe um administrador SaaS cadastrado. Não é possível criar outro.");
    }

    // Dados do primeiro admin SaaS
    $nome = "Administrador Master SaaS";
    $email = "master.saas@agendamento.com";
    
    // Gerar uma senha forte
    $senha_raw = bin2hex(random_bytes(8)); // Gera uma senha aleatória de 16 caracteres
    $senha_hash = password_hash($senha_raw, PASSWORD_DEFAULT);

    // Inserir admin SaaS
    $stmt = $pdo->prepare("
        INSERT INTO usuarios 
        (nome, email, senha, tipo_usuario, data_criacao) 
        VALUES (?, ?, ?, 'admin_saas', NOW())
    ");
    
    $stmt->execute([$nome, $email, $senha_hash]);

    echo "Administrador SaaS criado com sucesso!<br>";
    echo "E-mail: $email<br>";
    echo "Senha (ANOTE E GUARDE COM SEGURANÇA): $senha_raw<br>";
    echo "<strong>IMPORTANTE: Altere esta senha após o primeiro login!</strong>";

} catch (PDOException $e) {
    echo "Erro ao criar administrador SaaS: " . $e->getMessage();
}
?>
