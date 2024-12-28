<?php
require_once '../config.php';

// Verificar se já existe um admin SaaS
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin_saas'");
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        die("Já existe um administrador SaaS cadastrado. Não é possível criar outro.");
    }

    // Dados do primeiro admin SaaS
    $nome = "Administrador SaaS";
    $email = "admin.saas@agendamento.com";
    $senha = password_hash("AdminSaaS2024!", PASSWORD_DEFAULT);

    // Inserir admin SaaS
    $stmt = $pdo->prepare("
        INSERT INTO usuarios 
        (nome, email, senha, tipo_usuario, data_criacao) 
        VALUES (?, ?, ?, 'admin_saas', NOW())
    ");
    
    $stmt->execute([$nome, $email, $senha]);

    echo "Administrador SaaS criado com sucesso!<br>";
    echo "E-mail: $email<br>";
    echo "Senha: AdminSaaS2024!<br>";
    echo "<strong>IMPORTANTE: Altere esta senha após o primeiro login!</strong>";

} catch (PDOException $e) {
    echo "Erro ao criar administrador SaaS: " . $e->getMessage();
}
?>
