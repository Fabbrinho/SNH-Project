-- Create the database
CREATE DATABASE IF NOT EXISTS novelists_db;

USE novelists_db;

-- Create the Users table
CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_premium BOOLEAN DEFAULT FALSE,
    role ENUM('user', 'admin') DEFAULT 'user',
    token VARCHAR(32),
    is_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(64) NULL,
    reset_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the Novels table
CREATE TABLE IF NOT EXISTS Novels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('short', 'full') NOT NULL,
    content TEXT,
    file_path VARCHAR(255),
    is_premium BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Insert seed data
INSERT INTO Users (username, password_hash, email, is_premium, role, is_verified)
VALUES ('testuser', '$2y$10$wJ2j9LnL4ryu9SzUFLf5O.lZzgyU2vwiN/HDuRXzMH93UqAbbe6py', 'testuser@example.com', TRUE, 'user', TRUE)
ON DUPLICATE KEY UPDATE username=username;

INSERT INTO Novels (author_id, title, type, content, is_premium)
VALUES (1, 'Sample Short Story', 'short', 'Once upon a time...', TRUE)
ON DUPLICATE KEY UPDATE title=title;
