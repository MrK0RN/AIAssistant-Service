<?php
//setup data base
$commands = [
    "CREATE TABLE IF NOT EXISTS accounts (
        id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        login VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(32) NOT NULL,
        email VARCHAR(64) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20) NULL);",
    "CREATE TABLE IF NOT EXISTS assistant (
        id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        tone VARCHAR(32) NOT NULL,
        language VARCHAR(10) NOT NULL,
        prompt TEXT NOT NULL,
        platform VARCHAR(10) NOT NULL,
        credentials JSON NOT NULL, 
        status VARCHAR(10) NOT NULL);",
    "CREATE TABLE IF NOT EXISTS integrations (
        id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        platform VARCHAR(50) NOT NULL,
        config JSON NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        code VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);",
    "CREATE TABLE IF NOT EXISTS knowledge_base (
        id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id INT NOT NULL,
        assistant_id INT,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(20) NOT NULL,
        size INT DEFAULT 0,
        file_path VARCHAR(500),
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);",
    "ALTER TABLE knowledge_base ADD COLUMN IF NOT EXISTS assistant_id INT;",
    "ALTER TABLE assistant ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;",
    "ALTER TABLE assistant ADD COLUMN IF NOT EXISTS integration_id INT;"
];

include "pg.php";
foreach ($commands as $command) {
    pgQuery($command);
}
?>