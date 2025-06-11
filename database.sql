CREATE DATABASE popculture_db;
USE popculture_db;

CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    author VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_created_at (created_at)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Example user (password: 'admin123', stored in plain text)
INSERT INTO users (username, password) VALUES (
    'admin',
    'admin123'
);