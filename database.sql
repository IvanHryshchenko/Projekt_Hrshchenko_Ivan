CREATE DATABASE popculture_db;
USE popculture_db;

CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name title VARCHAR(255) NOT NULL,
    description content TEXT,
 NOT NULL,
    name VARCHAR(255),
    author VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_created_at ON created_at(created_at DESC)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255),
    NOT NULL UNIQUE,
    password VARCHAR(255),
    NOT NULL
);

-- Example user (password: 'admin123')
INSERT INTO users (username, password) (
    'admin',
    '$2y$10$1z5b8Q7z9X2Y3W4V5U6T7.x8y9z0A1B2C3D4E5F6G7H8I9J0K1L2M'
);


