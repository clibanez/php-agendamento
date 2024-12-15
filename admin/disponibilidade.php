<?php
session_start();
require_once '../config.php';

// Verificar permissão de acesso
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'], ['admin', 'gerente'])) {
    header("Location: ../index.php");
    exit();
}

// Buscar profissionais e administradores de empresa para preencher o dropdown
if ($_SESSION['usuario_tipo'] === 'admin') {
    $stmt_profissionais = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario IN ('admin', 'profissional') AND empresa_id = ?");
} else {
    $stmt_profissionais = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'profissional' AND empresa_id = ?");
}
$stmt_profissionais->execute([$_SESSION['empresa_id']]);
$profissionais = $stmt_profissionais->fetchAll(PDO::FETCH_ASSOC);

// Buscar disponibilidades já cadastradas
$profissional_id_filtro = isset($_GET['profissional_id']) ? $_GET['profissional_id'] : '';
$stmt = $pdo->prepare("SELECT 
    dp.*, 
    u.nome AS nome_profissional 
FROM disponibilidade_profissionais dp 
JOIN usuarios u ON dp.profissional_id = u.id 
WHERE (:profissional_id IS NULL OR dp.profissional_id = :profissional_id)
ORDER BY dp.data, dp.hora_inicio");
$stmt->execute(['profissional_id' => $profissional_id_filtro]);
$disponibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar a edição de disponibilidade
if (isset($_GET['editar'])) {
    $id_disponibilidade = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM disponibilidade_profissionais WHERE id = ?");
    $stmt->execute([$id_disponibilidade]);
    $disponibilidade_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Processar a remoção de disponibilidade
if (isset($_GET['remover'])) {
    $id_disponibilidade = $_GET['remover'];
    
    // Primeiro, obtenha o ID do profissional associado à disponibilidade que está sendo removida
    $stmt_profissional = $pdo->prepare("SELECT profissional_id FROM disponibilidade_profissionais WHERE id = ?");
    $stmt_profissional->execute([$id_disponibilidade]);
    $profissional = $stmt_profissional->fetch(PDO::FETCH_ASSOC);

    if ($profissional) {
        $stmt = $pdo->prepare("DELETE FROM disponibilidade_profissionais WHERE id = ?");
        $stmt->execute([$id_disponibilidade]);
        $_SESSION['sucesso'] = "Disponibilidade removida com sucesso!";
        
        // Redirecionar para a lista de disponibilidades do profissional removido
        header("Location: disponibilidade.php?profissional_id=" . $profissional['profissional_id']);
        exit();
    } else {
        $_SESSION['erro'] = "Erro ao remover a disponibilidade.";
    }
}

// Processar o cadastro de disponibilidade
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profissional_id = $_POST['profissional_id'];
    $data = $_POST['data'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];

    // Validação básica
    if (empty($profissional_id) || empty($data) || empty($hora_inicio) || empty($hora_fim)) {
        $_SESSION['erro'] = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        // Verificar se já existe disponibilidade cadastrada para o mesmo profissional e horário
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM disponibilidade_profissionais WHERE profissional_id = ? AND data = ? AND ((hora_inicio <= ? AND hora_fim >= ?) OR (hora_inicio <= ? AND hora_fim >= ?))");
        $stmt_check->execute([$profissional_id, $data, $hora_inicio, $hora_inicio, $hora_fim, $hora_fim]);
        $conflito = $stmt_check->fetchColumn();

        if ($conflito > 0) {
            $_SESSION['erro'] = "Este horário já está cadastrado para o profissional selecionado. Por favor, escolha um horário diferente.";
        } else {
            // Inserir disponibilidade
            $stmt_insert = $pdo->prepare("INSERT INTO disponibilidade_profissionais (profissional_id, data, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$profissional_id, $data, $hora_inicio, $hora_fim]);
            $_SESSION['sucesso'] = "Disponibilidade cadastrada com sucesso!";
            
            // Redirecionar para a lista de disponibilidades do profissional cadastrado
            header("Location: disponibilidade.php?profissional_id=" . $profissional_id);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Disponibilidade - <?php echo htmlspecialchars($empresa['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF69B4;
            --secondary-color: #FFC0CB;
            --accent-color: #8A4FFF;
            --background-gradient-start: #FFE5EC;
            --background-gradient-end: #FFF0F5;
            --text-color: #4A4A4A;
        }

        body {
            background: linear-gradient(135deg, var(--background-gradient-start), var(--background-gradient-end));
            min-height: 100vh;
            font-family: 'Poppins', 'Arial', sans-serif;
            color: var(--text-color);
        }

        .container {
            padding: 2rem 0;
        }

        .disponibilidade-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid var(--secondary-color);
        }

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border: 2px solid var(--secondary-color);
        }

        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .btn-back {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 15px;
            padding: 1rem 1.5rem;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border-color: var(--secondary-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 25px;
            padding: 12px 24px;
            color: white;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255,105,180,0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
            </a>
        </div>

        <div class="disponibilidade-container">
            <h2 class="page-title">
                <i class="fas fa-clock me-2"></i>
                Gerenciar Disponibilidade
            </h2>

            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
                    <?php echo htmlspecialchars($_SESSION['sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger" id="error-alert">
                    <?php echo htmlspecialchars($_SESSION['erro']); ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="mb-3">
                <div class="mb-3">
                    <label for="profissional_id" class="form-label">Filtrar por Profissional</label>
                    <select name="profissional_id" id="profissional_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($profissionais as $profissional): ?>
                            <option value="<?php echo $profissional['id']; ?>" <?php echo ($profissional['id'] == $profissional_id_filtro) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($profissional['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>

            <button type="button" class="btn btn-primary btn-lg mb-3" data-bs-toggle="modal" data-bs-target="#cadastroModal">
                <i class="fas fa-calendar-plus me-2"></i> Cadastrar Disponibilidade
            </button>

            <!-- Modal -->
            <div class="modal fade" id="cadastroModal" tabindex="-1" aria-labelledby="cadastroModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cadastroModalLabel">Cadastrar Disponibilidade</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="modalForm" action="disponibilidade.php" method="POST">
                                <input type="hidden" name="id" value="" id="modal_id">
                                <div class="mb-3">
                                    <label for="modal_profissional_id" class="form-label">Profissional</label>
                                    <select class="form-select" name="profissional_id" id="modal_profissional_id" required>
                                        <option value="">Selecione um profissional</option>
                                        <?php foreach ($profissionais as $profissional): ?>
                                            <option value="<?php echo $profissional['id']; ?>"><?php echo $profissional['nome']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="modal_data" class="form-label">Data</label>
                                    <input type="date" class="form-control" id="modal_data" name="data" required>
                                </div>
                                <div class="mb-3">
                                    <label for="modal_hora_inicio" class="form-label">Hora de Início</label>
                                    <input type="time" class="form-control" id="modal_hora_inicio" name="hora_inicio" required>
                                </div>
                                <div class="mb-3">
                                    <label for="modal_hora_fim" class="form-label">Hora de Fim</label>
                                    <input type="time" class="form-control" id="modal_hora_fim" name="hora_fim" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Salvar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-4 mt-4">
                <div class="card-header">
                    <h5 class="card-title">Disponibilidades Cadastradas</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Profissional</th>
                                <th>Data</th>
                                <th>Hora de Início</th>
                                <th>Hora de Fim</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disponibilidades as $disponibilidade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($disponibilidade['nome_profissional']); ?></td>
                                <td><?php echo htmlspecialchars($disponibilidade['data']); ?></td>
                                <td><?php echo htmlspecialchars($disponibilidade['hora_inicio']); ?></td>
                                <td><?php echo htmlspecialchars($disponibilidade['hora_fim']); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-warning" title="Editar" data-bs-toggle="modal" data-bs-target="#cadastroModal" onclick="carregarDadosModal(<?php echo json_encode($disponibilidade); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="disponibilidade.php?remover=<?php echo $disponibilidade['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function carregarDadosModal(dados) {
                document.getElementById('modal_id').value = dados.id;
                document.getElementById('modal_profissional_id').value = dados.profissional_id;
                document.getElementById('modal_data').value = dados.data;
                document.getElementById('modal_hora_inicio').value = dados.hora_inicio;
                document.getElementById('modal_hora_fim').value = dados.hora_fim;
                document.getElementById('cadastroModalLabel').innerText = 'Editar Disponibilidade';
            }

            // Remover alertas após 5 segundos
            setTimeout(function() {
                const successAlert = document.getElementById('success-alert');
                const errorAlert = document.getElementById('error-alert');
                if (successAlert) {
                    successAlert.classList.remove('show');
                }
                if (errorAlert) {
                    errorAlert.style.display = 'none';
                }
            }, 5000);
        </script>
    </div>
</body>
</html>
