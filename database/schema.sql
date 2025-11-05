-- DDL (Data Definition Language) para o Encurtador de Link

CREATE DATABASE IF NOT EXISTS simples_assim_url_db
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE simples_assim_url_db;

DROP TABLE IF EXISTS links;

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