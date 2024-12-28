<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit();
}

if (!isset($_POST['profissional_id']) || !isset($_POST['data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros incompletos']);
    exit();
}

$profissional_id = $_POST['profissional_id'];
$data = $_POST['data'];

if (!isset($_POST['servico_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro servico_id é necessário']);
    exit();
}

$servico_id = $_POST['servico_id'];

try {
    // Consultar a duração do serviço
    $sql_servico = "SELECT duracao_minutos FROM servicos WHERE id = :servico_id";
    $stmt_servico = $pdo->prepare($sql_servico);
    $stmt_servico->execute([':servico_id' => $servico_id]);
    $servico = $stmt_servico->fetch(PDO::FETCH_ASSOC);

    if (!$servico) {
        http_response_code(404);
        echo json_encode(['error' => 'Serviço não encontrado']);
        exit();
    }

    $duracao_minutos = $servico['duracao_minutos']; // Duração do serviço em minutos

    // Buscar horários disponíveis para a data selecionada
    $sql = "SELECT hora_inicio, hora_fim 
            FROM disponibilidade_profissionais 
            WHERE profissional_id = :profissional_id 
            AND data = :data";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':profissional_id' => $profissional_id,
        ':data' => $data
    ]);
    
    $disponibilidade = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $horarios_disponiveis = [];

    foreach ($disponibilidade as $periodo) {
        $hora_inicio = strtotime($periodo['hora_inicio']);
        $hora_fim = strtotime($periodo['hora_fim']);
        
        // Gerar horários baseados na duração do serviço
        for ($hora = $hora_inicio; $hora <= $hora_fim; $hora += $duracao_minutos * 60) { // Multiplicar por 60 para converter minutos em segundos
            $horario = date('H:i', $hora);
            
            // Verificar se o horário já está agendado
            $sql_check = "SELECT COUNT(*) as total 
                         FROM agendamentos 
                         WHERE profissional_id = :profissional_id 
                         AND data_hora = :data_hora 
                         AND status != 'cancelado'";
            
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([
                ':profissional_id' => $profissional_id,
                ':data_hora' => "$data $horario:00"
            ]);
            
            $resultado = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] == 0) {
                $horarios_disponiveis[] = $horario;
            }
        }
    }

    sort($horarios_disponiveis);
    echo json_encode($horarios_disponiveis);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar horários disponíveis: ' . $e->getMessage()]);
}
