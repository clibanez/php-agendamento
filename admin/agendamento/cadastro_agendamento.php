<?php
session_start();
require_once 'function_agendamento.php';


?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Agendamento</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text-color);
            padding: 20px 0;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            border: 2px solid var(--secondary-color);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            padding: 20px;
            border-bottom: none;
        }
        
        .card-header h4 {
            margin: 0;
            font-weight: 600;
            text-align: center;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn {
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,105,180,0.4);
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
        }
        
        .btn-secondary {
            background: #f8f9fa;
            border: 2px solid var(--secondary-color);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background: var(--secondary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .form-control {
            border-radius: 15px;
            padding: 12px 20px;
            border: 2px solid var(--secondary-color);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,105,180,0.25);
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
        }
        
        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .selected-item {
            background-color: var(--background-gradient-start);
            border-radius: 15px;
            padding: 10px;
            margin-top: 10px;
            display: inline-block;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            margin-top: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4>Cadastro de Agendamento</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <!-- Botão para selecionar cliente -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#clientesModal">
                            <i class="fas fa-user me-2"></i>Selecionar Cliente
                        </button>
                        <input type="hidden" name="cliente_id" id="cliente_id" required>
                        <div id="cliente_nome" class="selected-item mt-2"></div>
                    </div>

                    <!-- Botão para selecionar serviço -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#servicosModal">
                            <i class="fas fa-concierge-bell me-2"></i>Selecione um Serviço
                        </button>
                        <input type="hidden" name="servico" id="servico" required>
                        <div id="servico_nome" class="selected-item mt-2"></div>
                    </div>

                    <!-- Botão para selecionar profissional -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#profissionaisModal">
                            <i class="fas fa-user-md me-2"></i>Selecionar Profissional
                        </button>
                        <input type="hidden" name="profissional_id" id="profissional_id" required>
                        <div id="profissional_nome" class="selected-item mt-2"></div>
                    </div>

                    <!-- Data e hora -->
                    <div class="mb-4" id="date-selection-container" style="display: none;">
                        <button type="button" class="btn btn-primary w-100" id="select-date-btn">
                            <i class="fas fa-calendar-alt me-2"></i>Selecionar Data
                        </button>
                        <input type="hidden" name="data" id="data" required>
                        <input type="hidden" name="hora" id="hora" required>
                        <div id="data_hora_selecionada" class="selected-item mt-2"></div>
                    </div>

                    <div class="mb-4">
                        <label for="observacao" class="form-label">
                            <i class="fas fa-comment-alt me-2"></i>Observações
                        </label>
                        <textarea name="observacao" class="form-control" rows="3" 
                                  placeholder="Alguma observação especial para o seu agendamento?"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>Confirmar Agendamento
                        </button>
                        <button type="button" onclick="window.location.href='../dashboard.php'" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </button>
                    </div>
                </form>
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger mt-3"><?php echo $erro; ?></div>
                <?php endif; ?>
                <?php if (isset($sucesso)): ?>
                    <div class="alert alert-success mt-3"><?php echo $sucesso; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para seleção de clientes -->
    <div class="modal fade" id="clientesModal" tabindex="-1" aria-labelledby="clientesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientesModalLabel">Selecione um Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($clientes as $cliente): ?>
                            <div class="col">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><?php echo htmlspecialchars($cliente['nome']); ?></h5>
                                        <button type="button" class="btn btn-primary select-cliente w-100" 
                                                data-id="<?php echo $cliente['id']; ?>" 
                                                data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>">
                                            Selecionar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Services -->
    <div class="modal fade" id="servicosModal" tabindex="-1" role="dialog" aria-labelledby="servicosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="servicosModalLabel">Selecione um Serviço</h5>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($servicos as $servico): ?>
                            <div class="col">
                                <div class="card h-100">
                                    <img src="<?php echo '../' . htmlspecialchars($servico['imagem_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($servico['nome']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($servico['nome']); ?></h5>
                                        <p class="card-text">R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></p>
                                        <a href="#" class="btn btn-primary select-service" data-id="<?php echo $servico['id']; ?>" data-nome="<?php echo htmlspecialchars($servico['nome']); ?>" data-preco="<?php echo $servico['preco']; ?>">Selecionar</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para seleção de profissionais -->
    <div class="modal fade" id="profissionaisModal" tabindex="-1" aria-labelledby="profissionaisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profissionaisModalLabel">Selecione um Profissional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php
                        try {
                            // Buscar profissionais e administradores
                            $stmt_profissionais = $pdo->prepare("SELECT id, nome, tipo_usuario, imagem_perfil_url 
                                                                   FROM usuarios 
                                                                   WHERE (tipo_usuario = 'profissional' OR tipo_usuario = 'admin')
                                                                   AND empresa_id = :empresa_id 
                                                                   AND status = 'ativo'");
                            $stmt_profissionais->execute([':empresa_id' => $_SESSION['empresa_id']]);
                            $profissionais = $stmt_profissionais->fetchAll(PDO::FETCH_ASSOC);

                            // Se não encontrar profissionais, busca funcionários
                            if (empty($profissionais)) {
                                $stmt_profissionais = $pdo->prepare("SELECT id, nome, tipo_usuario, imagem_perfil_url 
                                                                       FROM usuarios 
                                                                       WHERE tipo_usuario = 'funcionario' 
                                                                       AND empresa_id = :empresa_id 
                                                                       AND status = 'ativo'");
                                $stmt_profissionais->execute([':empresa_id' => $_SESSION['empresa_id']]);
                                $profissionais = $stmt_profissionais->fetchAll(PDO::FETCH_ASSOC);
                            }

                            if (!empty($profissionais)): 
                                foreach ($profissionais as $profissional): 
                                    $imagem_url = !empty($profissional['imagem_perfil_url']) ? 
                                        '../../' . htmlspecialchars($profissional['imagem_perfil_url']) :
                                        '../assets/img/default-profile.jpg'; ?>
                                    <div class="col">
                                        <div class="card h-100">
                                            <img src="<?php echo $imagem_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($profissional['nome']); ?>">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo htmlspecialchars($profissional['nome']); ?></h5>
                                                <p class="card-text"><?php echo ucfirst(htmlspecialchars($profissional['tipo_usuario'])); ?></p>
                                                <button type="button" class="btn btn-primary select-profissional w-100" 
                                                        data-id="<?php echo $profissional['id']; ?>" 
                                                        data-nome="<?php echo htmlspecialchars($profissional['nome']); ?>">
                                                    Selecionar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        Nenhum profissional encontrado para esta empresa.
                                    </div>
                                </div>
                            <?php endif;
                        } catch (PDOException $e) { ?>
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    Erro ao carregar profissionais: <?php echo htmlspecialchars($e->getMessage()); ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for date selection -->
    <div class="modal fade" id="dateSelectionModal" tabindex="-1" aria-labelledby="dateSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dateSelectionModalLabel">Selecione uma Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="available-dates-list" class="list-group">
                        <!-- As datas disponíveis serão inseridas aqui via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for time selection -->
    <div class="modal fade" id="timeSelectionModal" tabindex="-1" aria-labelledby="timeSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="timeSelectionModalLabel">Selecione um Horário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="available-times-list" class="list-group">
                        <!-- Os horários disponíveis serão inseridos aqui via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="agendamento.js"></script> 
    <script>
        // Lida com a seleção do cliente
        $(document).on('click', '.select-cliente', function() {
            const id = $(this).data('id');
            const nome = $(this).data('nome');
            $('#cliente_id').val(id);
            $('#cliente_nome').text(nome);
            $('#clientesModal').modal('hide'); // Fecha o modal
        });
    </script>
</body>
</html>

