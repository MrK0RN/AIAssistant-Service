
-- Create tables for the application
CREATE TABLE IF NOT EXISTS accounts (
    id SERIAL PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(32) NOT NULL,
    email VARCHAR(64) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    hash VARCHAR(32),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS integrations (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    config JSON NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assistant (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    tone VARCHAR(32) NOT NULL,
    language VARCHAR(10) NOT NULL,
    prompt TEXT NOT NULL,
    platform VARCHAR(10) NOT NULL,
    credentials JSON NOT NULL, 
    status VARCHAR(10) NOT NULL,
    integration_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS knowledge_base (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    assistant_id INT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(20) NOT NULL,
    size INT DEFAULT 0,
    file_path VARCHAR(500),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add foreign key constraints
ALTER TABLE assistant ADD CONSTRAINT fk_assistant_user FOREIGN KEY (user_id) REFERENCES accounts(id);
ALTER TABLE assistant ADD CONSTRAINT fk_assistant_integration FOREIGN KEY (integration_id) REFERENCES integrations(id);
ALTER TABLE integrations ADD CONSTRAINT fk_integrations_user FOREIGN KEY (user_id) REFERENCES accounts(id);
ALTER TABLE knowledge_base ADD CONSTRAINT fk_knowledge_user FOREIGN KEY (user_id) REFERENCES accounts(id);
ALTER TABLE knowledge_base ADD CONSTRAINT fk_knowledge_assistant FOREIGN KEY (assistant_id) REFERENCES assistant(id);
