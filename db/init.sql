-- Таблица для хранения информации об ассистентах
CREATE TABLE IF NOT EXISTS assistants (
    id SERIAL PRIMARY KEY,
    openai_assistant_id TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    instructions TEXT,
    model TEXT NOT NULL,
    temperature REAL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Таблица для хранения информации о файлах, загруженных в OpenAI
CREATE TABLE IF NOT EXISTS openai_files (
    id SERIAL PRIMARY KEY,
    openai_file_id TEXT UNIQUE NOT NULL,
    filename TEXT NOT NULL,
    purpose TEXT NOT NULL,
    size_bytes INTEGER,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Таблица для хранения информации о Vector Stores
CREATE TABLE IF NOT EXISTS vector_stores (
    id SERIAL PRIMARY KEY,
    openai_vector_store_id TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Таблица для связи Vector Stores с файлами
CREATE TABLE IF NOT EXISTS vector_store_files (
    id SERIAL PRIMARY KEY,
    vector_store_id INTEGER REFERENCES vector_stores(id) ON DELETE CASCADE,
    openai_file_id TEXT REFERENCES openai_files(openai_file_id) ON DELETE CASCADE,
    openai_vector_store_file_id TEXT UNIQUE NOT NULL, -- ID связи в OpenAI
    status TEXT NOT NULL, -- e.g., 'completed', 'pending', 'failed'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (vector_store_id, openai_file_id) -- Один файл может быть в одном Vector Store только один раз
);

-- Таблица для связи ассистентов с Vector Stores
CREATE TABLE IF NOT EXISTS assistant_vector_stores (
    id SERIAL PRIMARY KEY,
    assistant_id INTEGER REFERENCES assistants(id) ON DELETE CASCADE,
    vector_store_id INTEGER REFERENCES vector_stores(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (assistant_id, vector_store_id)
);


CREATE TABLE IF NOT EXISTS chats (
    id SERIAL PRIMARY KEY,
    assistant_id INTEGER NOT NULL,
    openai_thread_id TEXT UNIQUE NOT NULL,
    chat_id TEXT NOT NULL,
    total_tokens_used INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Таблица для хранения сообщений
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    chat_id INTEGER NOT NULL REFERENCES chats(id) ON DELETE CASCADE,
    assistant_id INTEGER NOT NULL,
    openai_message_id TEXT NOT NULL,
    message_text TEXT NOT NULL,
    role TEXT NOT NULL, -- 'user', 'assistant', 'system'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
-- Триггер для автоматического обновления updated_at
CREATE OR REPLACE FUNCTION update_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_assistant_timestamp
BEFORE UPDATE ON assistants
FOR EACH ROW
EXECUTE FUNCTION update_timestamp();