<?php
// Buscar informações do usuário e da empresa
try {
    $stmt = $pdo->prepare("SELECT \
        u.nome AS usuario_nome, \
        u.imagem_perfil_url, \
        e.nome AS empresa_nome, \
        e.logo_url \
    FROM usuarios u\n    JOIN empresas e ON u.empresa_id = e.id\n    WHERE u.id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Definir imagens padrão se não existirem
    $logo_url = $usuario_info['logo_url'] ?? 'uploads/logos/logo_padrao.svg';
    $perfil_url = $usuario_info['imagem_perfil_url'] ?? 'uploads/perfis/admin_padrao.svg';
    if (!empty($usuario_info['imagem_perfil_url']) && file_exists('../' . $usuario_info['imagem_perfil_url'])) {
        $perfil_url = $usuario_info['imagem_perfil_url'];
    }
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro ao buscar informações do usuário: " . $e->getMessage());
    $logo_url = 'uploads/logos/logo_padrao.svg';
    $perfil_url = 'uploads/perfis/admin_padrao.svg';
}
?>

<nav class="modern-sidebar">
    <div class="sidebar-header">
        <img src="../<?php echo htmlspecialchars($logo_url); ?>" alt="Logo" class="sidebar-logo">
        <div>
            <h4 class="sidebar-title"><?php echo htmlspecialchars(substr($usuario_info['empresa_nome'], 0, 15) . (strlen($usuario_info['empresa_nome']) > 15 ? '...' : '')); ?></h4>
            <p class="sidebar-subtitle">Sistema de Agendamento</p>
        </div>
    </div>

    <div class="user-profile">
        <img src="../<?php echo htmlspecialchars($perfil_url); ?>" alt="Perfil" class="user-avatar">
        <div class="user-info">
            <h5 class="user-name"><?php echo htmlspecialchars(substr($usuario_info['usuario_nome'], 0, 15) . (strlen($usuario_info['usuario_nome']) > 15 ? '...' : '')); ?></h5>
            <p class="user-role"><?php echo $_SESSION['usuario_tipo_acesso']; ?></p>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="meus_agendamentos.php">
                <i class="fas fa-calendar-check"></i> Meus Agendamentos
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="servicos.php">
                <i class="fas fa-concierge-bell"></i> Serviços
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="configuracoes.php">
                <i class="fas fa-cog"></i> Configurações
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</nav>
