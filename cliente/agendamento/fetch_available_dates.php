<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit();
}

if (!isset($_POST['profissional_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do profissional não fornecido']);
    exit();
}

$profissional_id = $_POST['profissional_id'];

try {
    // Buscar datas disponíveis para os próximos 30 dias
    $sql = "SELECT DISTINCT data 
            FROM disponibilidade_profissionais 
            WHERE profissional_id = :profissional_id 
            AND data >= CURDATE() 
            AND data <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY data ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':profissional_id' => $profissional_id]);
    $datas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $datas_formatadas = [];
    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
    
    foreach ($datas as $data) {
        $timestamp = strtotime($data['data']);
        $datas_formatadas[] = [
            'data' => $data['data'],
            'data_formatada' => date('d/m/Y', $timestamp),
            'dia_semana' => strftime('%A', $timestamp)
        ];
    }

    echo json_encode($datas_formatadas);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar datas disponíveis: ' . $e->getMessage()]);
}
