-- DDL (Data Definition Language) para o Encurtador de Link

-- 1. Tentar Criar o Banco de Dados (Se não existir)
--    Garante que o banco de dados exista.
CREATE DATABASE IF NOT EXISTS simples_assim_url_db
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 2. Selecionar o Banco de Dados
USE simples_assim_url_db;

-- 3. Limpeza: Remover a tabela 'links' se já existir.
--    Isto é seguro e garante um estado inicial limpo para as tabelas.
DROP TABLE IF EXISTS links;

-- 4. Criação da Tabela 'links' (Seu Schema)
CREATE TABLE links (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    long_url VARCHAR(2048) NOT NULL,
    short_code VARCHAR(5) UNIQUE NOT NULL,
    clicks INT(11) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    valid_until DATETIME NULL,
    user_id INT(11) NULL,
    custom_code VARCHAR(50) NULL,
    INDEX user_id_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;