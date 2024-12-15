<?php
session_start(); // Inicializa a sessão

require_once '../config.php'; // Inclua seu arquivo de conexão com o banco de dados

header('Content-Type: application/json');

// Verifica se o usuário está logado e se é do tipo 'cliente'
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$profissional_id = $_POST['profissional_id'] ?? null;
$data = $_POST['data'] ?? null;

if (!$profissional_id || !$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Profissional ou data não especificados']);
    exit;
}

try {
    // Buscar horários disponíveis para o profissional na data especificada
    $sql = "SELECT hora_inicio, hora_fim 
            FROM disponibilidade_profissionais 
            WHERE profissional_id = :profissional_id 
            AND data = :data 
            ORDER BY hora_inicio ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':profissional_id' => $profissional_id, ':data' => $data]);
    
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna os horários como um array associativo
    
    echo json_encode($horarios); // Retorna os horários em formato JSON

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar horários disponíveis: ' . $e->getMessage()]);
}
?>
