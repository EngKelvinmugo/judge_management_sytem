-- Create database
CREATE DATABASE IF NOT EXISTS judge_management_system;
USE judge_management_system;

-- Create tables
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS judges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judge_id INT NOT NULL,
    user_id INT NOT NULL,
    points INT NOT NULL CHECK (points BETWEEN 1 AND 100),
    FOREIGN KEY (judge_id) REFERENCES judges(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_judge_user (judge_id, user_id)
);

-- Insert sample data
-- Admin: username: admin, password: admin123
INSERT INTO admins (username, password_hash) VALUES 
('admin', '$2y$10$8MNE.3xAYRXpvHc94HjM7uVHn5.QwTr/QBjBII6jFGqPq1DnUZFXC');

-- Judges: usernames: judge1, judge2, judge3, passwords: judge123
INSERT INTO judges (username, display_name, password_hash) VALUES 
('judge1', 'John Smith', '$2y$10$8MNE.3xAYRXpvHc94HjM7uVHn5.QwTr/QBjBII6jFGqPq1DnUZFXC'),
('judge2', 'Jane Doe', '$2y$10$8MNE.3xAYRXpvHc94HjM7uVHn5.QwTr/QBjBII6jFGqPq1DnUZFXC'),
('judge3', 'Robert Johnson', '$2y$10$8MNE.3xAYRXpvHc94HjM7uVHn5.QwTr/QBjBII6jFGqPq1DnUZFXC');

-- Sample users
INSERT INTO users (name) VALUES 
('Alice Williams'),
('Bob Brown'),
('Charlie Davis'),
('Diana Miller'),
('Edward Wilson'),
('Fiona Taylor');

-- Sample scores
INSERT INTO scores (judge_id, user_id, points) VALUES 
(1, 1, 85),
(1, 2, 92),
(1, 3, 78),
(2, 1, 90),
(2, 2, 88),
(2, 4, 95),
(3, 1, 82),
(3, 3, 89),
(3, 5, 94);