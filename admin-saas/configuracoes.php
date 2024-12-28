<?php
session_start();
require_once '../config.php';

// Verificar se o usuário é admin do SaaS
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin_saas') {
    header("Location: ../index.php");
    exit();
}

// Processar configurações
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Exemplo de configuração (você pode expandir conforme necessário)
        $config_keys = [
            'sistema_nome',
            'sistema_email_suporte',
            'sistema_limite_empresas',
            'sistema_limite_usuarios_por_empresa'
        ];

        foreach ($config_keys as $key) {
            $valor = filter_input(INPUT_POST, $key);
            
            // Verificar se a configuração já existe
            $stmt = $pdo->prepare("SELECT * FROM configuracoes WHERE chave = ?");
            $stmt->execute([$key]);
            $config_existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($config_existente) {
                // Atualizar configuração existente
                $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
                $stmt->execute([$valor, $key]);
            } else {
                // Inserir nova configuração
                $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?)");
                $stmt->execute([$key, $valor]);
            }
        }

        $mensagem = "Configurações atualizadas com sucesso!";
    } catch (PDOException $e) {
        $erro = "Erro ao atualizar configurações: " . $e->getMessage();
    }
}

// Buscar configurações atuais
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes");
    $configuracoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $configuracoes = [];
    error_log("Erro ao buscar configurações: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - SaaS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        .card-config {
            transition: transform 0.3s;
        }
        .card-config:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-4">Configurações do Sistema</h1>
                    
                    <?php if ($mensagem): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-config mb-4">
                                    <div class="card-header">
                                        Configurações Gerais
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="sistema_nome" class="form-label">Nome do Sistema</label>
                                            <input type="text" class="form-control" id="sistema_nome" name="sistema_nome" 
                                                   value="<?php echo htmlspecialchars($configuracoes['sistema_nome'] ?? 'Agendamento SaaS'); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="sistema_email_suporte" class="form-label">E-mail de Suporte</label>
                                            <input type="email" class="form-control" id="sistema_email_suporte" name="sistema_email_suporte" 
                                                   value="<?php echo htmlspecialchars($configuracoes['sistema_email_suporte'] ?? 'suporte@agendamento.com'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-config mb-4">
                                    <div class="card-header">
                                        Limites do Sistema
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="sistema_limite_empresas" class="form-label">Limite de Empresas</label>
                                            <input type="number" class="form-control" id="sistema_limite_empresas" name="sistema_limite_empresas" 
                                                   value="<?php echo htmlspecialchars($configuracoes['sistema_limite_empresas'] ?? '100'); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="sistema_limite_usuarios_por_empresa" class="form-label">Limite de Usuários por Empresa</label>
                                            <input type="number" class="form-control" id="sistema_limite_usuarios_por_empresa" name="sistema_limite_usuarios_por_empresa" 
                                                   value="<?php echo htmlspecialchars($configuracoes['sistema_limite_usuarios_por_empresa'] ?? '50'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salvar Configurações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
