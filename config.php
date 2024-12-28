<?php
// Configurações de banco de dados
$host = 'mysql';
$dbname = 'agendamento_db';
$username = 'root';
$password = '12345678';


// haspberry
// $host = 'localhost';
// $dbname = 'agendamento_db';
// $username = 'user';
// $password = '123456';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Função para criar tabelas iniciais se não existirem
function criarTabelasIniciais($pdo) {
    // Tabela de empresas
    $pdo->exec("CREATE TABLE IF NOT EXISTS empresas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        cnpj VARCHAR(20) UNIQUE NOT NULL,
        telefone VARCHAR(20),
        endereco TEXT,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('ativo', 'inativo') DEFAULT 'ativo'
    )");

    // Tabela de usuários (atualizada para incluir empresa_id)
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        tipo_usuario ENUM('admin_saas', 'admin', 'profissional', 'cliente') NOT NULL,
        empresa_id INT,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    )");

    // Tabela de serviços (atualizada para incluir empresa_id)
    $pdo->exec("CREATE TABLE IF NOT EXISTS servicos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao TEXT,
        duracao_minutos INT NOT NULL,
        preco DECIMAL(10,2) NOT NULL,
        categoria ENUM('barbearia', 'estetica', 'fisioterapia') NOT NULL,
        empresa_id INT NOT NULL,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    )");

    // Tabela de agendamentos (atualizada para incluir empresa_id)
    $pdo->exec("CREATE TABLE IF NOT EXISTS agendamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        profissional_id INT NOT NULL,
        servico_id INT NOT NULL,
        empresa_id INT NOT NULL,
        data_hora DATETIME NOT NULL,
        status ENUM('agendado', 'confirmado', 'cancelado', 'concluido') DEFAULT 'agendado',
        FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
        FOREIGN KEY (profissional_id) REFERENCES usuarios(id),
        FOREIGN KEY (servico_id) REFERENCES servicos(id),
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    )");
}

// Função para formatar número de telefone
function formatarTelefone($numero) {
    // Remove todos os caracteres não numéricos
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Verifica o comprimento do número
    $len = strlen($numero);
    
    // Formata para celular (11 dígitos)
    if ($len == 11) {
        return sprintf('(%s) %s-%s', 
            substr($numero, 0, 2), 
            substr($numero, 2, 5), 
            substr($numero, 7)
        );
    } 
    // Formata para telefone fixo (10 dígitos)
    elseif ($len == 10) {
        return sprintf('(%s) %s-%s', 
            substr($numero, 0, 2), 
            substr($numero, 2, 4), 
            substr($numero, 6)
        );
    }
    
    // Retorna o número original se não for possível formatar
    return $numero;
}

// Chama a função para criar tabelas
criarTabelasIniciais($pdo);
?>
