db = {
    "DB_HOST": "db",
    "DB_NAME": "openai_assistants_db_2",
    "DB_USER": "openai_assistants_db_2",
    "DB_PASSWORD": "cu3ndh3behc",
    "DB_PORT": 5432
}

sql_queries = [
    # 1. Создание таблицы assistants
    """CREATE TABLE IF NOT EXISTS assistants (
        id SERIAL PRIMARY KEY,
        openai_assistant_id TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        instructions TEXT,
        model TEXT NOT NULL,
        temperature REAL,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );""",

    # 2. Создание таблицы openai_files
    """CREATE TABLE IF NOT EXISTS openai_files (
        id SERIAL PRIMARY KEY,
        openai_file_id TEXT UNIQUE NOT NULL,
        filename TEXT NOT NULL,
        purpose TEXT NOT NULL,
        size_bytes INTEGER,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );""",

    # 3. Создание таблицы vector_stores
    """CREATE TABLE IF NOT EXISTS vector_stores (
        id SERIAL PRIMARY KEY,
        openai_vector_store_id TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );""",

    # 4. Создание таблицы vector_store_files
    """CREATE TABLE IF NOT EXISTS vector_store_files (
        id SERIAL PRIMARY KEY,
        vector_store_id INTEGER REFERENCES vector_stores(id) ON DELETE CASCADE,
        openai_file_id TEXT REFERENCES openai_files(openai_file_id) ON DELETE CASCADE,
        openai_vector_store_file_id TEXT UNIQUE NOT NULL,
        status TEXT NOT NULL,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (vector_store_id, openai_file_id)
    );""",

    # 5. Создание таблицы assistant_vector_stores
    """CREATE TABLE IF NOT EXISTS assistant_vector_stores (
        id SERIAL PRIMARY KEY,
        assistant_id INTEGER REFERENCES assistants(id) ON DELETE CASCADE,
        vector_store_id INTEGER REFERENCES vector_stores(id) ON DELETE CASCADE,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (assistant_id, vector_store_id)
    );""",

    # 6. Создание таблицы chats
    """CREATE TABLE IF NOT EXISTS chats (
        id SERIAL PRIMARY KEY,
        chat_id VARCHAR(32),
        assistant_id INTEGER NOT NULL REFERENCES assistants(id) ON DELETE CASCADE,
        openai_thread_id VARCHAR(255) UNIQUE NOT NULL,
        total_tokens_used INTEGER DEFAULT 0,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );""",

    # 7. Создание функции update_timestamp
    """CREATE OR REPLACE FUNCTION update_timestamp()
    RETURNS TRIGGER AS $$
    BEGIN
        NEW.updated_at = NOW();
        RETURN NEW;
    END;
    $$ LANGUAGE plpgsql;""",

    # 8. Создание триггера для assistants
    """CREATE TRIGGER update_assistant_timestamp
    BEFORE UPDATE ON assistants
    FOR EACH ROW
    EXECUTE FUNCTION update_timestamp();"""
]
