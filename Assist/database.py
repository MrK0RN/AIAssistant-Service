# database.py

import psycopg
from psycopg.rows import dict_row
from psycopg import sql
import os
from datetime import datetime
from typing import Optional, List, Dict, Any
from config import db, sql_queries
def get_db_connection():
    """Establishes a connection to the PostgreSQL database using psycopg."""
    conn = psycopg.connect(
        host=db.get("DB_HOST"),
        dbname=db.get("DB_NAME"),
        user=db.get("DB_USER"),
        password=db.get("DB_PASSWORD"),
        port=db.get("DB_PORT")
    )
    return conn

def check_db_connection():
    """
    Checks if the application can successfully connect to the PostgreSQL database.
    Raises an exception if the connection fails.
    """
    try:
        conn = get_db_connection()
        conn.close()
        print("Database connection successful!")
        conn = get_db_connection()
        cur = conn.cursor()
        for query in sql_queries:
            try:
                cur.execute(query)
            except Exception:
                pass
        conn.commit()
        return True
    except Exception as e:
        print(f"Error: Could not connect to the database. Please check your DB credentials and server status. Error: {e}")
        raise ConnectionError("Failed to connect to the database.") from e


# --- Assistant Operations ---

def db_create_assistant(openai_assistant_id: str, name: str, instructions: str, model: str, temperature: float) -> int:
    """Creates an assistant record in the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            INSERT INTO assistants (openai_assistant_id, name, instructions, model, temperature)
            VALUES (%s, %s, %s, %s, %s) RETURNING id;
            """,
            (openai_assistant_id, name, instructions, model, temperature)
        )
        assistant_db_id = cur.fetchone()[0]
        conn.commit()
        return assistant_db_id
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_assistant(assistant_db_id: int) -> Optional[Dict[str, Any]]:
    """Retrieves assistant information from the DB by internal ID."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute(
            """
            SELECT a.id, a.openai_assistant_id, a.name, a.instructions, a.model, a.temperature, a.created_at, a.updated_at,
                   ARRAY_AGG(vs.openai_vector_store_id) FILTER (WHERE vs.openai_vector_store_id IS NOT NULL) as vector_store_ids
            FROM assistants a
            LEFT JOIN assistant_vector_stores avs ON a.id = avs.assistant_id
            LEFT JOIN vector_stores vs ON avs.vector_store_id = vs.id
            WHERE a.id = %s
            GROUP BY a.id;
            """,
            (assistant_db_id,)
        )
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_assistant_by_openai_id(openai_assistant_id: str) -> Optional[Dict[str, Any]]:
    """Retrieves assistant information from the DB by OpenAI Assistant ID."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute(
            """
            SELECT a.id, a.openai_assistant_id, a.name, a.instructions, a.model, a.temperature, a.created_at, a.updated_at,
                   ARRAY_AGG(vs.openai_vector_store_id) FILTER (WHERE vs.openai_vector_store_id IS NOT NULL) as vector_store_ids
            FROM assistants a
            LEFT JOIN assistant_vector_stores avs ON a.id = avs.assistant_id
            LEFT JOIN vector_stores vs ON avs.vector_store_id = vs.id
            WHERE a.openai_assistant_id = %s
            GROUP BY a.id;
            """,
            (openai_assistant_id,)
        )
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_update_assistant(assistant_db_id: int, name: Optional[str] = None, instructions: Optional[str] = None, temperature: Optional[float] = None):
    """Updates an assistant record in the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        query_parts = []
        params = []
        if name is not None:
            query_parts.append(sql.SQL("name = %s"))
            params.append(name)
        if instructions is not None:
            query_parts.append(sql.SQL("instructions = %s"))
            params.append(instructions)
        if temperature is not None:
            query_parts.append(sql.SQL("temperature = %s"))
            params.append(temperature)

        if not query_parts:
            return # Nothing to update

        query = sql.SQL("UPDATE assistants SET {} WHERE id = %s").format(
            sql.SQL(', ').join(query_parts)
        )
        params.append(assistant_db_id)

        cur.execute(query, params)
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_delete_assistant(assistant_db_id: int):
    """Deletes an assistant record from the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute("DELETE FROM assistants WHERE id = %s;", (assistant_db_id,))
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

# --- File Operations ---
# (Existing functions remain unchanged)

def db_add_openai_file(openai_file_id: str, filename: str, purpose: str, size_bytes: Optional[int]) -> int:
    """Adds an OpenAI file record to the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            INSERT INTO openai_files (openai_file_id, filename, purpose, size_bytes)
            VALUES (%s, %s, %s, %s) RETURNING id;
            """,
            (openai_file_id, filename, purpose, size_bytes)
        )
        file_db_id = cur.fetchone()[0]
        conn.commit()
        return file_db_id
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_openai_file(file_db_id: int) -> Optional[Dict[str, Any]]:
    """Retrieves OpenAI file information from the DB."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute("SELECT * FROM openai_files WHERE id = %s;", (file_db_id,))
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_openai_file_by_openai_id(openai_file_id: str) -> Optional[Dict[str, Any]]:
    """Retrieves OpenAI file information from the DB by OpenAI File ID."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute("SELECT * FROM openai_files WHERE openai_file_id = %s;", (openai_file_id,))
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_delete_openai_file(openai_file_id: str):
    """Deletes an OpenAI file record from the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute("DELETE FROM openai_files WHERE openai_file_id = %s;", (openai_file_id,))
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

# --- Vector Store Operations ---
# (Existing functions remain unchanged)

def db_create_vector_store(openai_vector_store_id: str, name: str) -> int:
    """Creates a Vector Store record in the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            INSERT INTO vector_stores (openai_vector_store_id, name)
            VALUES (%s, %s) RETURNING id;
            """,
            (openai_vector_store_id, name)
        )
        vector_store_db_id = cur.fetchone()[0]
        conn.commit()
        return vector_store_db_id
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_vector_store(vector_store_db_id: int) -> Optional[Dict[str, Any]]:
    """Retrieves Vector Store information from the DB."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute("SELECT * FROM vector_stores WHERE id = %s;", (vector_store_db_id,))
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_vector_store_by_openai_id(openai_vector_store_id: str) -> Optional[Dict[str, Any]]:
    """Retrieves Vector Store information from the DB by OpenAI Vector Store ID."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute("SELECT * FROM vector_stores WHERE openai_vector_store_id = %s;", (openai_vector_store_id,))
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_delete_vector_store(openai_vector_store_id: str):
    """Deletes a Vector Store record from the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute("DELETE FROM vector_stores WHERE openai_vector_store_id = %s;", (openai_vector_store_id,))
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

# --- Vector Store File Link Operations ---
# (Existing functions remain unchanged)

def db_add_vector_store_file_link(vector_store_db_id: int, openai_file_id: str, openai_vector_store_file_id: str, status: str):
    """Adds a file-to-Vector Store link record to the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            INSERT INTO vector_store_files (vector_store_id, openai_file_id, openai_vector_store_file_id, status)
            VALUES (%s, %s, %s, %s)
            ON CONFLICT (vector_store_id, openai_file_id) DO UPDATE SET
                openai_vector_store_file_id = EXCLUDED.openai_vector_store_file_id,
                status = EXCLUDED.status;
            """,
            (vector_store_db_id, openai_file_id, openai_vector_store_file_id, status)
        )
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_update_vector_store_file_status(openai_vector_store_file_id: str, status: str):
    """Updates the status of a file in a Vector Store record in the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            UPDATE vector_store_files SET status = %s WHERE openai_vector_store_file_id = %s;
            """,
            (status, openai_vector_store_file_id)
        )
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()


def db_delete_vector_store_file_link(openai_vector_store_file_id: str):
    """Deletes a file-to-Vector Store link record from the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            "DELETE FROM vector_store_files WHERE openai_vector_store_file_id = %s;",
            (openai_vector_store_file_id,)
        )
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_vector_store_file_link_by_ids(openai_vector_store_id: str, openai_file_id: str) -> Optional[Dict[str, Any]]:
    """
    Retrieves the vector_store_file link information by OpenAI Vector Store ID and OpenAI File ID.
    """
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute(
            """
            SELECT vsf.*
            FROM vector_store_files vsf
            JOIN vector_stores vs ON vsf.vector_store_id = vs.id
            WHERE vs.openai_vector_store_id = %s AND vsf.openai_file_id = %s;
            """,
            (openai_vector_store_id, openai_file_id)
        )
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()


# --- Assistant Vector Store Link Operations ---
# (Existing functions remain unchanged)

def db_link_assistant_to_vector_store(assistant_db_id: int, vector_store_db_id: int):
    """Creates an assistant-to-Vector Store link in the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            INSERT INTO assistant_vector_stores (assistant_id, vector_store_id)
            VALUES (%s, %s) ON CONFLICT (assistant_id, vector_store_id) DO NOTHING;
            """,
            (assistant_db_id, vector_store_db_id)
        )
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_unlink_assistant_from_vector_store(assistant_db_id: int, vector_store_db_id: int):
    """Deletes an assistant-to-Vector Store link from the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            "DELETE FROM assistant_vector_stores WHERE assistant_id = %s AND vector_store_id = %s;",
            (assistant_db_id, vector_store_db_id)
        )
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_assistant_vector_stores(assistant_db_id: int) -> List[Dict[str, Any]]:
    """Retrieves all vector stores linked to a given assistant."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute(
            """
            SELECT vs.id as vector_store_db_id, vs.openai_vector_store_id, vs.name
            FROM assistant_vector_stores avs
            JOIN vector_stores vs ON avs.vector_store_id = vs.id
            WHERE avs.assistant_id = %s;
            """,
            (assistant_db_id,)
        )
        return cur.fetchall()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

# --- Chat (Thread) Operations ---

def db_create_chat(assistant_db_id: int, openai_thread_id: str, chat_id) -> int:
    """Creates a chat (thread) record in the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            INSERT INTO chats (assistant_id, openai_thread_id, chat_id)
            VALUES (%s, %s, %s) RETURNING id;
            """,
            (assistant_db_id, openai_thread_id, str(chat_id))
        )
        chat_db_id = cur.fetchone()[0]
        conn.commit()
        return chat_db_id
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_chat_by_db_id(chat_db_id: int) -> Optional[Dict[str, Any]]:
    """Retrieves chat information from the DB by internal ID."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute("SELECT * FROM chats WHERE chat_id = %s;", (str(chat_db_id),))
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_get_chat_by_openai_thread_id(openai_thread_id: str) -> Optional[Dict[str, Any]]:
    """Retrieves chat information from the DB by OpenAI Thread ID."""
    conn = get_db_connection()
    cur = conn.cursor(row_factory=dict_row)
    try:
        cur.execute("SELECT * FROM chats WHERE openai_thread_id = %s;", (openai_thread_id,))
        return cur.fetchone()
    except Exception as e:
        raise e
    finally:
        cur.close()
        conn.close()

def db_update_chat_tokens(chat_db_id: int, tokens_added: int):
    """Updates the total tokens used for a chat in the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute(
            """
            UPDATE chats SET total_tokens_used = total_tokens_used + %s
            WHERE id = %s;
            """,
            (tokens_added, chat_db_id)
        )
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()

def db_delete_chat(chat_db_id: int):
    """Deletes a chat record from the DB."""
    conn = get_db_connection()
    cur = conn.cursor()
    try:
        cur.execute("DELETE FROM chats WHERE id = %s;", (chat_db_id,))
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        cur.close()
        conn.close()