<?php
// Verificar se o usuário está logado como admin do SaaS
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin_saas') {
    header("Location: ../index.php");
    exit();
}
?>
<style>
    .sidebar {
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        background-color: #2c3e50;
        color: #ecf0f1;
        transition: all 0.3s;
        z-index: 1000;
        padding-top: 20px;
    }

    .sidebar-logo {
        text-align: center;
        margin-bottom: 30px;
        padding: 0 15px;
    }

    .sidebar-logo img {
        max-width: 150px;
        margin-bottom: 10px;
    }

    .sidebar-logo h3 {
        color: #fff;
        margin: 0;
        font-size: 1.2rem;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li {
        margin-bottom: 5px;
    }

    .sidebar-menu li a {
        display: block;
        color: #bdc3c7;
        padding: 10px 15px;
        text-decoration: none;
        transition: all 0.3s;
    }

    .sidebar-menu li a:hover,
    .sidebar-menu li a.active {
        background-color: #34495e;
        color: #fff;
    }

    .sidebar-menu li a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .content-wrapper {
        margin-left: 250px;
        transition: all 0.3s;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 0;
            overflow-x: hidden;
        }
        .content-wrapper {
            margin-left: 0;
        }
    }
</style>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/img/logo.svg" alt="Logo SaaS Admin">
        <h3>SaaS Admin</h3>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="empresas.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'empresas.php') ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> Empresas
            </a>
        </li>
        <li>
            <a href="nova_empresa.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'nova_empresa.php') ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Cadastrar Empresa
            </a>
        </li>
        <li>
            <a href="usuarios.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Usuários
            </a>
        </li>
        <li>
            <a href="relatorios.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'relatorios.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Relatórios
            </a>
        </li>
        <li>
            <a href="configuracoes.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'configuracoes.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Configurações
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const contentWrapper = document.querySelector('.content-wrapper');

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            contentWrapper.classList.toggle('shifted');
        });
    }
});
</script>
