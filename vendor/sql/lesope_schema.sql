CREATE DATABASE IF NOT EXISTS lesopo;
USE lesopo;


-- users table
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(150) NOT NULL,
email VARCHAR(150) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
role ENUM('teacher','admin','viewer') DEFAULT 'teacher',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- lesson_plans
CREATE TABLE lesson_plans (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
subject VARCHAR(150) NOT NULL,
class_level VARCHAR(100) NOT NULL,
topic VARCHAR(255) NOT NULL,
objectives TEXT,
teaching_aids TEXT,
methods TEXT,
lesson_steps TEXT,
evaluation TEXT,
date_planned DATE DEFAULT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NULL,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- optional: approvals
CREATE TABLE approvals (
id INT AUTO_INCREMENT PRIMARY KEY,
lesson_id INT NOT NULL,
approver_id INT NOT NULL,
status ENUM('pending','approved','rejected') DEFAULT 'pending',
note TEXT,
acted_at TIMESTAMP NULL,
FOREIGN KEY (lesson_id) REFERENCES lesson_plans(id) ON DELETE CASCADE,
FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;