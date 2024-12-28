<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Aceita login por nome ou email
    $login_input = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'];

    // Verifica se o input parece ser um email
    $is_email = filter_var($login_input, FILTER_VALIDATE_EMAIL);

    try {
        // Preparar consulta dinâmica baseada no tipo de input
        if ($is_email) {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo_usuario, empresa_id FROM usuarios WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo_usuario, empresa_id FROM usuarios WHERE nome = ?");
        }

        $stmt->execute([$login_input]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
            $_SESSION['empresa_id'] = $usuario['empresa_id'];

            // Atualiza último login
            $update_login = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $update_login->execute([$usuario['id']]);

            // Redireciona baseado no tipo de usuário
            switch ($usuario['tipo_usuario']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'profissional':
                    header("Location: profissional/dashboard.php");
                    break;
                case 'cliente':
                    header("Location: cliente/dashboard.php");
                    break;
                case 'admin_saas':
                    header("Location: admin-saas/dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            // Credenciais inválidas
            $_SESSION['erro_login'] = "Credenciais inválidas. Verifique seu nome/email e senha.";
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        // Erro de banco de dados
        $_SESSION['erro_login'] = "Erro no sistema. Tente novamente mais tarde.";
        header("Location: index.php");
        exit();
    }
} else {
    // Acesso direto não permitido
    header("Location: index.php");
    exit();
}
?>
