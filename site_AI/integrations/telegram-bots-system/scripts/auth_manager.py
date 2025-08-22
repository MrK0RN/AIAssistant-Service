import json
import secrets

AUTH_FILE = "auth/tokens.json"

def generate_token(description=""):
    """Создает новый токен."""
    token = secrets.token_hex(32)
    tokens = load_tokens()
    tokens[token] = {"description": description, "active": True}
    save_tokens(tokens)
    return token

def validate_token(token):
    """Проверяет токен."""
    tokens = load_tokens()
    return tokens.get(token, {}).get("active", False)

def load_tokens():
    """Загружает токены из файла."""
    try:
        with open(AUTH_FILE, "r") as f:
            return json.loads(f.read())
    except Exception:
        print("!!!!!!")

def save_tokens(tokens):
    """Сохраняет токены в файл."""
    with open(AUTH_FILE, "w") as f:
        json.dump(tokens, f, indent=2)