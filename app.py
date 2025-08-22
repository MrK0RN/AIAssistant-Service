from flask import Flask, request, jsonify
import os
import yaml
import subprocess

app = Flask(__name__)

def load_docker_compose():
    """Загружает текущий docker-compose.yml"""
    try:
        docker_compose_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "docker-compose.yml")
        with open(docker_compose_path, "r") as file:
            return yaml.safe_load(file)
    except FileNotFoundError:
        return {"version": "3.8", "services": {}, "networks": {"my_network": {"driver": "bridge"}}, "volumes": {"postgres_data": None}}

def validate_and_fix_networks(config):
    """Проверяет и исправляет сети в конфигурации"""
    # Убеждаемся, что сеть my_network существует
    if "networks" not in config:
        config["networks"] = {}

    if "my_network" not in config["networks"]:
        config["networks"]["my_network"] = {"driver": "bridge"}

    # Исправляем ссылки на сети в сервисах
    for service_name, service_config in config.get("services", {}).items():
        if "networks" in service_config:
            # Заменяем app_network на my_network если есть
            if isinstance(service_config["networks"], list):
                service_config["networks"] = ["my_network" if net == "app_network" else net for net in service_config["networks"]]
                # Убеждаемся что my_network есть в списке
                if "my_network" not in service_config["networks"]:
                    service_config["networks"].append("my_network")

    return config

def save_docker_compose(config):
    """Сохраняет конфигурацию в docker-compose.yml"""
    try:
        # Проверяем и исправляем сети перед сохранением
        config = validate_and_fix_networks(config)

        docker_compose_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "docker-compose.yml")
        with open(docker_compose_path, "w") as file:
            yaml.dump(config, file, default_flow_style=False, indent=2)
        print("Docker compose file saved successfully")
    except Exception as e:
        print(f"Error saving docker-compose.yml: {e}")
        raise e

def restart_docker_compose():
    """Перезапускает весь docker-compose"""
    try:
        # Устанавливаем рабочую директорию
        working_dir = os.path.dirname(os.path.abspath(__file__))

        print("Stopping all Docker Compose services...")
        stop_result = subprocess.run(
            ["docker-compose", "down"],
            capture_output=True,
            text=True,
            cwd=working_dir
        )
        if stop_result.stderr:
            print(f"Stop warnings: {stop_result.stderr}")

        print("Validating docker-compose.yml...")
        validate_result = subprocess.run(
            ["docker-compose", "config"],
            capture_output=True,
            text=True,
            cwd=working_dir
        )
        if validate_result.returncode != 0:
            print(f"Docker Compose validation error: {validate_result.stderr}")
            return False

        print("Starting all Docker Compose services...")
        start_result = subprocess.run(
            ["docker-compose", "up", "-d"],
            capture_output=True,
            text=True,
            cwd=working_dir
        )
        if start_result.returncode != 0:
            print(f"Docker Compose start error: {start_result.stderr}")
            return False

        print("All services started successfully")
        return True
    except subprocess.CalledProcessError as e:
        print(f"Docker Compose restart error: {e}")
        if hasattr(e, 'stderr') and e.stderr:
            print(f"Error output: {e.stderr}")
        return False
    except Exception as e:
        print(f"Unexpected error during restart: {e}")
        return False

@app.route('/integration', methods=['POST'])
def create_integration():
    data = request.json

    # Валидация данных
    required_fields = ['id', 'platform', 'credentials', 'url', 'aid']
    if not all(field in data for field in required_fields):
        return jsonify({"error": "Missing required fields"}), 400

    # Загружаем текущую конфигурацию
    config = load_docker_compose()

    # Определяем имя сервиса
    service_name = f"ya_bot_{data['id']}"

    # Проверяем, не существует ли уже такой сервис
    if service_name in config['services']:
        return jsonify({"error": "Integration already exists"}), 409

    credentials = ""
    # Создаем конфигурацию нового сервиса
    if data["platform"] == "telegram_client":
        credentials = data["credentials"]["phone_number"] + "|" + data["credentials"]["api_id"] + "|" + data["credentials"]["api_hash"]
    elif data["platform"] == "telegram_bot":
        credentials = data["credentials"]["bot_token"]

    new_service = {
        "build": {
            "context": ".",
            "dockerfile": "dockerfile"
        },
        "environment": {
            "PLATFORM": data['platform'],
            "CREDS": credentials,
            "API_ENDPOINT": http://open_ai:5000/chat,
            "ASSISTANT_ID": str(data['aid'])
        },
        "depends_on": ["open_ai"],
        "networks": ["my_network"],
        "restart": "unless-stopped"
    }

    # Добавляем прокси если указан
    if 'proxy' in data and data['proxy']:
        new_service['environment']['PROXY'] = data['proxy']

    # Добавляем сервис в конфигурацию
    config['services'][service_name] = new_service

    try:
        # Сохраняем конфигурацию
        save_docker_compose(config)
        print(f"Configuration saved for service: {service_name}")

        # Перезапускаем весь docker-compose
        if restart_docker_compose():
            return jsonify({"message": f"Integration {service_name} created successfully"}), 201
        else:
            return jsonify({"error": "Failed to restart Docker services"}), 500

    except Exception as e:
        print(f"Error creating integration: {e}")
        return jsonify({"error": f"Failed to create integration: {str(e)}"}), 500

@app.route('/integration/stop', methods=['POST'])
def stop_integration():
    data = request.json

    if 'id' not in data:
        return jsonify({"error": "Missing integration ID"}), 400

    service_name = f"ya_bot_{data['id']}"

    # Загружаем текущую конфигурацию
    config = load_docker_compose()

    # Проверяем, существует ли сервис
    if service_name not in config['services']:
        return jsonify({"error": "Integration not found"}), 404

    # Удаляем сервис из конфигурации
    del config['services'][service_name]

    # Сохраняем конфигурацию
    save_docker_compose(config)

    # Перезапускаем весь docker-compose
    try:
        if restart_docker_compose():
            return jsonify({"message": f"Integration {service_name} stopped and removed"}), 200
        else:
            return jsonify({"error": "Failed to restart Docker services after removal"}), 500
    except Exception as e:
        print(f"Error restarting docker-compose: {e}")
        return jsonify({"error": f"Failed to restart services: {str(e)}"}), 500

@app.route('/integrations', methods=['GET'])
def list_integrations():
    """Возвращает список всех интеграций"""
    config = load_docker_compose()
    integrations = []

    for service_name, service_config in config['services'].items():
        if service_name.startswith('ya_bot_'):
            integration_id = service_name.replace('ya_bot_', '')
            env = service_config.get('environment', {})
            integrations.append({
                "id": integration_id,
                "platform": env.get('PLATFORM'),
                "assistant_id": env.get('ASSISTANT_ID'),
                "api_endpoint": env.get('API_ENDPOINT')
            })

    return jsonify(integrations)

def check_service_status(service_name):
    """Проверяет статус конкретного сервиса"""
    try:
        working_dir = os.path.dirname(os.path.abspath(__file__))
        result = subprocess.run(
            ["docker-compose", "ps", "-q", service_name],
            capture_output=True,
            text=True,
            check=False,
            cwd=working_dir
        )
        return len(result.stdout.strip()) > 0
    except Exception:
        return False

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({"status": "healthy"}), 200

@app.route('/integration/status/<integration_id>', methods=['GET'])
def get_integration_status(integration_id):
    """Получает статус конкретной интеграции"""
    service_name = f"ya_bot_{integration_id}"
    status = "running" if check_service_status(service_name) else "stopped"
    return jsonify({"status": status, "service_name": service_name}), 200

if __name__ == '__main__':
    app.run(host='0.0.0.0', debug=True, port=5001)
