<?php
// Inicia a sessão para manipulação de variáveis de sessão
session_start();

// Inclui o arquivo de configuração
require_once '../../config.php';

// Define a URL base caso não esteja definida na configuração
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost:8081/agendamento-php'); // Ajuste conforme necessário
}

// Verifica se o usuário está logado e se é do tipo 'admin'
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../index.php"); // Redireciona para a página inicial caso não esteja logado ou não seja cliente
    exit();
}

// Buscar clientes associados à empresa
$stmt_clientes = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'cliente' AND empresa_id = ?");
$stmt_clientes->execute([$_SESSION['empresa_id']]);
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Processar o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $servico = $_POST['servico'] ?? '';
    $profissional = $_POST['profissional_id'] ?? '';
    $cliente_id = $_POST['cliente_id'] ?? ''; // Captura o ID do cliente
    $observacao = $_POST['observacao'] ?? '';
    $user_id = $_SESSION['usuario_id']; // ID do usuário logado
    $empresa_id = $_SESSION['empresa_id']; // ID da empresa do usuário

    // Validações básicas para verificar se os campos obrigatórios foram preenchidos
    if (empty($data) || empty($hora) || empty($servico) || empty($profissional) || empty($cliente_id)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Obtém a duração do serviço selecionado
            $sql_duracao = "SELECT duracao_minutos FROM servicos WHERE id = :servico_id";
            $stmt_duracao = $pdo->prepare($sql_duracao);
            $stmt_duracao->execute([':servico_id' => $servico]);
            $servico_duracao = $stmt_duracao->fetch(PDO::FETCH_ASSOC);

            // Verifica se o serviço foi encontrado
            if ($servico_duracao) {
                $duracao_minutos = $servico_duracao['duracao_minutos'];

                // Determina a consulta de verificação de conflito de agendamento com base na duração do serviço
                if ($duracao_minutos < 1) {
                    // Se a duração for menor que 1 minuto, não verifica o conflito de agendamento
                    $sql_cliente_check = "SELECT COUNT(*) as total FROM agendamentos 
                                          WHERE cliente_id = :cliente_id 
                                          AND ((data_hora_final BETWEEN :data_hora_final_inicio AND :data_hora_final_fim)) 
                                          AND status != 'cancelado'";
                } else {
                    // Caso contrário, verifica o conflito de agendamento normalmente
                    $sql_cliente_check = "SELECT COUNT(*) as total FROM agendamentos 
                                          WHERE cliente_id = :cliente_id 
                                          AND (
                                              (data_hora_final BETWEEN :data_hora_final_inicio AND :data_hora_final_fim) 
                                              OR 
                                              (DATE_ADD(data_hora_final, INTERVAL (SELECT duracao_minutos FROM servicos WHERE id = servico_id) MINUTE) 
                                              BETWEEN :data_hora_final_inicio AND :data_hora_final_fim)
                                          ) 
                                          AND status != 'cancelado'";
                }

                // Executa a verificação de conflitos de agendamento
                $stmt_cliente_check = $pdo->prepare($sql_cliente_check);
                $stmt_cliente_check->execute([
                    ':cliente_id' => $user_id,
                    ':data_hora_final_inicio' => $data_hora_final_inicio,
                    ':data_hora_final_fim' => $data_hora_final_fim
                ]);
                $cliente_resultado = $stmt_cliente_check->fetch(PDO::FETCH_ASSOC);

                // Se já houver um agendamento para o cliente, exibe um erro
                if ($cliente_resultado['total'] > 0) {
                    $erro = "Você já possui um agendamento neste horário. Por favor, escolha outro.";
                } else {
                    // Verifica a disponibilidade do profissional para o horário solicitado
                    $sql_disponibilidade = "SELECT * FROM disponibilidade_profissionais 
                                            WHERE profissional_id = :profissional_id 
                                            AND data = :data 
                                            AND hora_inicio <= :hora 
                                            AND hora_fim >= :hora";
                    $stmt_disponibilidade = $pdo->prepare($sql_disponibilidade);
                    $stmt_disponibilidade->execute([
                        ':profissional_id' => $profissional,
                        ':data' => $data,
                        ':hora' => $hora
                    ]);
                    $disponivel = $stmt_disponibilidade->fetch(PDO::FETCH_ASSOC);

                    // Caso o profissional não esteja disponível, exibe uma mensagem de erro
                    if (!$disponivel) {
                        $erro = "O profissional não está disponível neste horário.";
                    } else {
                        // Inserir o agendamento, somando a duração do serviço
                        $sql_insert = "INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, empresa_id, data_hora_final, status, observacoes, data_hora) 
                        VALUES (:cliente_id, :profissional_id, :servico_id, :empresa_id, 
                        DATE_ADD(:data_hora_final, INTERVAL (SELECT duracao_minutos FROM servicos WHERE id = :servico_id) MINUTE), 'agendado', :observacoes, :data_hora)";
                        $stmt_insert = $pdo->prepare($sql_insert);
                        $stmt_insert->execute([
                            ':cliente_id' => $cliente_id, // Salva o ID do cliente selecionado
                            ':profissional_id' => $profissional,
                            ':servico_id' => $servico,
                            ':empresa_id' => $empresa_id,
                            ':data_hora' => "$data $hora",
                            ':data_hora_final' => "$data $hora",
                            ':observacoes' => $observacao
                        ]);

                        // Mensagem de sucesso após o agendamento ser realizado
                        $sucesso = "Agendamento realizado com sucesso!";
                    }
                }
            } else {
                $erro = "Serviço não encontrado.";
            }
        } catch (PDOException $e) {
            // Exibe mensagem de erro caso ocorra uma exceção ao realizar o agendamento
            $erro = "Erro ao realizar agendamento: " . $e->getMessage();
        }
    }
}

// Buscar serviços disponíveis
try {
    $sql_servicos = "SELECT * FROM servicos WHERE status = 'ativo'";
    $stmt_servicos = $pdo->prepare($sql_servicos);
    $stmt_servicos->execute();
    $servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Exibe mensagem de erro caso ocorra uma exceção ao carregar os serviços
    $erro_servicos = "Erro ao carregar serviços: " . $e->getMessage();
}

// Inicializa a variável profissionais como um array vazio
$profissionais = [];

// Verifica se a sessão da empresa está definida
if (!isset($_SESSION['empresa_id'])) {
    echo "<p>Erro: Empresa não definida na sessão.</p>";
} else {
    // Buscar profissionais e administradores associados à empresa do usuário logado
    try {
        $sql_profissionais = "SELECT id, nome, tipo_usuario 
                             FROM usuarios 
                             WHERE (tipo_usuario = 'profissional' OR tipo_usuario = 'admin') 
                             AND empresa_id = :empresa_id 
                             AND status = 'ativo'";
        $stmt_profissionais = $pdo->prepare($sql_profissionais);
        $stmt_profissionais->execute([':empresa_id' => $_SESSION['empresa_id']]);
        $profissionais = $stmt_profissionais->fetchAll(PDO::FETCH_ASSOC);

        // Se não encontrar profissionais nem administradores, tenta buscar funcionários
        if (empty($profissionais)) {
            $sql_profissionais = "SELECT id, nome, tipo_usuario 
                                 FROM usuarios 
                                 WHERE tipo_usuario = 'funcionario' 
                                 AND empresa_id = :empresa_id 
                                 AND status = 'ativo'";
            $stmt_profissionais = $pdo->prepare($sql_profissionais);
            $stmt_profissionais->execute([':empresa_id' => $_SESSION['empresa_id']]);
            $profissionais = $stmt_profissionais->fetchAll(PDO::FETCH_ASSOC);
        }

        // Caso não tenha encontrado nem profissionais, administradores ou funcionários
        if (empty($profissionais)) {
            $erro_profissionais = "Nenhum profissional ou administrador encontrado.";
        }
    } catch (PDOException $e) {
        // Exibe mensagem de erro caso ocorra uma exceção ao carregar os profissionais
        $erro_profissionais = "Erro ao carregar profissionais: " . $e->getMessage();
    }
}
?>
