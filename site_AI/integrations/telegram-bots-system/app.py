from flask import Flask, request, jsonify
from scripts.add_bot import add_bot
from scripts.auth_manager import validate_token, generate_token
import os
import logging

app = Flask(__name__)
logging.basicConfig(level=logging.DEBUG)  # Включение логов


@app.before_request
def check_auth():
    if request.endpoint == 'get_token':
        return
    token = request.headers.get("Authorization", "")
    if not token.startswith("Bearer "):
        return jsonify({"error": "Invalid token format"}), 403
    token = token.replace("Bearer ", "")
    if not validate_token(token):
        return jsonify({"error": "Invalid token"}), 403


@app.route('/get_token', methods=['POST'])
def get_token():
    # Проверка наличия ANY данных
    if not request.data:
        app.logger.error("Empty body received")
        return jsonify({"error": "Request body is empty"}), 400

    # Проверка Content-Type
    if not request.is_json:
        app.logger.error(f"Wrong Content-Type: {request.content_type}")
        return jsonify({"error": "Content-Type must be application/json"}), 400

    # Парсинг JSON с обработкой всех ошибок
    try:
        data = request.get_json(force=True)  # force=True для строгой проверки
        if data is None:
            raise ValueError("Empty JSON received")

        description = data.get("description", "API Token")
        token = str(generate_token(description))
        print(token)

        return jsonify({
            "status": "success",
            "token": token,
            "description": description
        })
    except Exception as e:
        app.logger.error(f"JSON parse failed: {str(e)}")
        return jsonify({
            "error": "Invalid JSON data",
            "details": str(e),
            "received_data": request.data.decode('utf-8', errors='replace')
        }), 400

@app.route("/add_bot", methods=["POST"])
def handle_add_bot():
    try:
        data = request.json
        if not data or "api_id" not in data or "api_hash" not in data or "phone_number" not in data:
            return jsonify({"error": "Missing required fields"}), 400

        add_bot(
            api_id=data["api_id"],
            api_hash=data["api_hash"],
            phone_number=data["phone_number"],
            bot_name=data.get("bot_name")
        )
        return jsonify({"status": "success"})
    except Exception as e:
        app.logger.error(f"Error in add_bot: {e}")
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=True)  # Debug mode