-- Agendamento SaaS - Configuração Básica do Banco de Dados
-- Versão: 1.0.0
-- Data: 2024-12-09

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS agendamento_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agendamento_db;

-- Tabela de Empresas
CREATE TABLE IF NOT EXISTS empresas (
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

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('admin_saas', 'admin_empresa', 'profissional', 'cliente') NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    empresa_id INT,
    tipo_acesso ENUM('admin_saas', 'admin_empresa', 'gerente', 'funcionario', 'cliente') DEFAULT 'cliente',
    status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Funcionários
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cargo VARCHAR(100),
    disponibilidade VARCHAR(255),
    empresa_id INT,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentário final
-- Script de configuração básica do banco de dados Agendamento SaaS
-- Criado em: 2024-12-09
-- Versão: 1.0.0
-- Autor: Equipe Agendamento
