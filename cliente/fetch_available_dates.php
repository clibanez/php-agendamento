<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit;
}

$profissional_id = $_POST['profissional_id'] ?? null;

if (!$profissional_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Profissional não especificado']);
    exit;
}

try {
    // Buscar datas disponíveis para o profissional
    $sql = "SELECT DISTINCT data 
            FROM disponibilidade_profissionais 
            WHERE profissional_id = :profissional_id 
            AND data >= CURDATE() 
            ORDER BY data ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':profissional_id' => $profissional_id]);
    
    $datas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $datas_formatadas = [];
    
    foreach ($datas as $data) {
        // Formatar a data para exibição
        $data_obj = new DateTime($data['data']);
        $datas_formatadas[] = [
            'data' => $data['data'],
            'data_formatada' => $data_obj->format('d/m/Y'),
            'dia_semana' => strftime('%A', $data_obj->getTimestamp())
        ];
    }
    
    echo json_encode($datas_formatadas);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar datas disponíveis: ' . $e->getMessage()]);
}
