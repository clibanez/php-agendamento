<?php
session_start();
require_once '../config.php';

// Verifica se o usuário está logado e é profissional
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'profissional') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profissional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1>Dashboard Profissional</h1>
                <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</p>
                
                <div class="card">
                    <div class="card-header">
                        Meus Agendamentos
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Agenda do Dia</h5>
                                        <a href="agenda_dia.php" class="btn btn-primary">Ver Agenda</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Próximos Agendamentos</h5>
                                        <a href="proximos_agendamentos.php" class="btn btn-primary">Visualizar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="../logout.php" class="btn btn-danger">Sair</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
