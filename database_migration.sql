-- Agendamento SaaS - Script de Migração de Banco de Dados
-- Versão: 1.0.0
-- Data: 2024-12-09

-- Atualização da estrutura de usuários para suportar multitenancy
ALTER TABLE usuarios 
ADD COLUMN empresa_id INT,
ADD COLUMN tipo_acesso ENUM('admin_saas', 'admin_sistema', 'admin_empresa', 'gerente', 'funcionario', 'cliente') DEFAULT 'cliente',
ADD COLUMN imagem_perfil_url VARCHAR(255),
ADD COLUMN status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
ADD FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL;

-- Atualizar tipo de usuário existentes
UPDATE usuarios SET 
tipo_usuario = 'admin_empresa' 
WHERE tipo_usuario = 'admin';

-- Adicionar índices para melhorar performance
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);

-- Adicionar tabela de logs de atividades
CREATE TABLE IF NOT EXISTS logs_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT,
    ip_address VARCHAR(45),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar tabela de planos SaaS
CREATE TABLE IF NOT EXISTS planos (
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

-- Adicionar tabela de assinaturas
CREATE TABLE IF NOT EXISTS assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    plano_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    status ENUM('ativo', 'suspenso', 'cancelado') DEFAULT 'ativo',
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir planos iniciais
INSERT IGNORE INTO planos (
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

-- Comentário final
-- Script de migração do banco de dados Agendamento SaaS
-- Criado em: 2024-12-09
-- Versão: 1.0.0
-- Autor: Equipe Agendamento
