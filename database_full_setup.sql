-- Agendamento SaaS - Configuração Completa do Banco de Dados
-- Versão: 1.0.0
-- Data: 2024-12-09

-- Remover banco de dados existente (opcional, use com cuidado)
DROP DATABASE IF EXISTS agendamento_db;

-- Criar novo banco de dados
CREATE DATABASE agendamento_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agendamento_db;

-- Tabela de Empresas (Multitenancy)
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(20) UNIQUE,
    email_contato VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    logo_url VARCHAR(255),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativo', 'inativo', 'pendente') DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Usuários (Expandida com Suporte SaaS)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('admin_saas', 'admin', 'profissional', 'cliente') NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    empresa_id INT,
    tipo_acesso ENUM('admin_saas', 'admin_sistema', 'admin_empresa', 'gerente', 'funcionario', 'cliente') DEFAULT 'cliente',
    imagem_perfil_url VARCHAR(255),
    status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    celular VARCHAR(20) NULL,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Serviços
CREATE TABLE servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    duracao_minutos INT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    categoria ENUM('barbearia', 'estetica', 'fisioterapia') NOT NULL,
    empresa_id INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Agendamentos
CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    profissional_id INT NOT NULL,
    servico_id INT NOT NULL,
    empresa_id INT NOT NULL,
    data_hora DATETIME NOT NULL,
    data_hora_final DATETIME NOT NULL,
    status ENUM('agendado', 'confirmado', 'cancelado', 'concluido') DEFAULT 'agendado',
    observacoes TEXT,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id),
    FOREIGN KEY (servico_id) REFERENCES servicos(id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Disponibilidade dos Profissionais
CREATE TABLE disponibilidade_profissionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profissional_id INT NOT NULL,
    data DATE NOT NULL,  -- New field for specific date
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Planos SaaS
CREATE TABLE planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    preco_mensal DECIMAL(10,2) NOT NULL,
    max_usuarios INT NOT NULL,
    max_servicos INT NOT NULL,
    max_agendamentos_mes INT NOT NULL,
    recursos TEXT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Assinaturas
CREATE TABLE assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    plano_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    status ENUM('ativo', 'suspenso', 'cancelado') DEFAULT 'ativo',
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Logs de Atividades
CREATE TABLE logs_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT,
    ip_address VARCHAR(45),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para melhorar performance
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);
CREATE INDEX idx_agendamentos_cliente ON agendamentos(cliente_id);
CREATE INDEX idx_agendamentos_profissional ON agendamentos(profissional_id);
CREATE INDEX idx_agendamentos_servico ON agendamentos(servico_id);
CREATE INDEX idx_agendamentos_data ON agendamentos(data_hora);

-- Inserir empresa padrão
INSERT INTO empresas (nome, cnpj, email_contato, status) VALUES 
('Empresa Padrão', '00.000.000/0001-00', 'contato@empresapadrao.com', 'ativo');

-- Inserir usuário admin SaaS
INSERT INTO usuarios (
    nome, 
    email, 
    senha, 
    tipo_usuario, 
    tipo_acesso, 
    empresa_id
) SELECT 
    'admin', 
    'admin@gmai.com', 
    -- Senha: 123456 (hash gerada com password_hash)
    '$2y$10$8Qx7tgzyWaNV6NF7..P09eRCYN8pK6eBelYhusTmxEfHoaBVYJnkq', 
    'admin_saas', 
    'admin_saas', 
    id 
FROM empresas 
WHERE nome = 'Empresa Padrão';

-- Inserir planos iniciais
INSERT INTO planos (
    nome, 
    descricao, 
    preco_mensal, 
    max_usuarios, 
    max_servicos, 
    max_agendamentos_mes, 
    recursos
) VALUES 
('Básico', 'Plano para pequenos negócios', 49.90, 5, 10, 100, 'Suporte por e-mail,Até 5 usuários,10 serviços'),
('Intermediário', 'Plano para negócios em crescimento', 99.90, 15, 30, 500, 'Suporte prioritário,Até 15 usuários,30 serviços,Relatórios básicos'),
('Avançado', 'Plano completo para empresas', 199.90, 50, 100, 2000, 'Suporte dedicado,Até 50 usuários,100 serviços,Relatórios avançados,Integração API');

-- Inserir serviços de exemplo
INSERT INTO servicos (
    nome, 
    descricao, 
    duracao_minutos, 
    preco, 
    categoria, 
    empresa_id
) SELECT 
    'Corte Masculino', 
    'Corte de cabelo tradicional', 
    30, 
    35.00, 
    'barbearia', 
    id 
FROM empresas 
WHERE nome = 'Empresa Padrão';

-- Comentário final
-- Script de configuração completa do banco de dados Agendamento SaaS
-- Criado em: 2024-12-09
-- Versão: 1.0.0
-- Autor: Equipe Agendamento
