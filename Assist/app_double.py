# msnger.py

from flask import Flask, request, jsonify, send_from_directory
from dotenv import load_dotenv
import os
import time
import uuid
from werkzeug.utils import secure_filename
from datetime import datetime # Ensure datetime is imported

from proxy import GlobalSocksProxy

# Load environment variables from .env file
load_dotenv()

# Import our custom modules
import database as db
import openai_service as oai_service

app = Flask(__name__)

# Configure upload folder
UPLOAD_FOLDER = 'files'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024 # 16 MB max upload size

ALLOWED_EXTENSIONS = {'txt', 'pdf', 'doc', 'docx', 'csv', 'json'}
def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

# Helper to convert DB rows (dicts) to JSON-serializable format
def row_to_dict(row):
    if row is None:
        return None
    # Convert datetime objects to string
    for key, value in row.items():
        if isinstance(value, datetime):
            row[key] = value.isoformat()
    return dict(row)

# --- API Endpoints ---


@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({"status": "healthy"}), 200

@app.route('/assistants', methods=['POST'])
def create_assistant():
    data = request.json
    if not data:
        return jsonify({"error": "Invalid JSON"}), 400

    name = data.get('name')
    instructions = data.get('instructions')
    model = data.get('model', 'gpt-4o-mini')
    temperature = data.get('temperature', 0.7)
    file_paths = data.get('file_paths', [])

    if not name or not instructions:
        return jsonify({"error": "Missing required fields: name, instructions"}), 400

    try:
        vector_store_name = f"VS for {name} ({uuid.uuid4().hex[:6]})"
        openai_vector_store = oai_service.create_openai_vector_store(vector_store_name)
        vector_store_openai_id = openai_vector_store['id']
        vector_store_db_id = db.db_create_vector_store(vector_store_openai_id, vector_store_name)

        uploaded_file_ids = []
        for local_file_path in file_paths:
            if not os.path.exists(local_file_path):
                return jsonify({"error": f"File not found: {local_file_path}"}), 400

            file_name = os.path.basename(local_file_path)
            openai_file = oai_service.upload_openai_file(local_file_path)
            openai_file_id = openai_file['id']
            db.db_add_openai_file(openai_file_id, file_name, openai_file['purpose'], openai_file['bytes'])

            vector_store_file = oai_service.add_file_to_vector_store(vector_store_openai_id, openai_file_id)
            db.db_add_vector_store_file_link(vector_store_db_id, openai_file_id, vector_store_file['id'], vector_store_file['status'])

            while True:
                status = oai_service.get_vector_store_file_status(vector_store_openai_id, vector_store_file['id'])
                db.db_update_vector_store_file_status(vector_store_file['id'], status)
                if status in ['completed', 'failed', 'cancelled']:
                    break
                time.sleep(1)

            if status != 'completed':
                app.logger.warning(f"File {file_name} processing failed/cancelled: {status}")

            uploaded_file_ids.append(openai_file_id)


        openai_assistant = oai_service.create_openai_assistant(
            name=name,
            instructions=instructions,
            model=model,
            temperature=temperature,
            vector_store_id=vector_store_openai_id
        )
        assistant_openai_id = openai_assistant['id']

        assistant_db_id = db.db_create_assistant(
            assistant_openai_id, name, instructions, model, temperature
        )
        db.db_link_assistant_to_vector_store(assistant_db_id, vector_store_db_id)

        response_data = db.db_get_assistant(assistant_db_id)
        return jsonify(row_to_dict(response_data)), 201

    except Exception as e:
        app.logger.error(f"Error creating assistant: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

@app.route('/assistants/<int:assistant_db_id>', methods=['GET'])
def get_assistant(assistant_db_id):
    try:
        assistant_data = db.db_get_assistant(assistant_db_id)
        if not assistant_data:
            return jsonify({"error": "Assistant not found"}), 404
        return jsonify(row_to_dict(assistant_data)), 200
    except Exception as e:
        app.logger.error(f"Error getting assistant: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

@app.route('/assistants/<int:assistant_db_id>', methods=['PUT'])
def update_assistant(assistant_db_id):
    data = request.json
    if not data:
        return jsonify({"error": "Invalid JSON"}), 400

    assistant_data = db.db_get_assistant(assistant_db_id)
    if not assistant_data:
        return jsonify({"error": "Assistant not found"}), 404

    openai_assistant_id = assistant_data['openai_assistant_id']
    name = data.get('name')
    instructions = data.get('instructions')
    temperature = data.get('temperature')

    try:
        oai_service.update_openai_assistant(
            openai_assistant_id=openai_assistant_id,
            name=name,
            instructions=instructions,
            temperature=temperature
        )
        db.db_update_assistant(
            assistant_db_id=assistant_db_id,
            name=name,
            instructions=instructions,
            temperature=temperature
        )
        response_data = db.db_get_assistant(assistant_db_id)
        return jsonify(row_to_dict(response_data)), 200
    except Exception as e:
        app.logger.error(f"Error updating assistant: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

@app.route('/assistants/<int:assistant_db_id>', methods=['DELETE'])
def delete_assistant(assistant_db_id):
    try:
        assistant_data = db.db_get_assistant(assistant_db_id)
        if not assistant_data:
            return jsonify({"error": "Assistant not found"}), 404

        openai_assistant_id = assistant_data['openai_assistant_id']

        linked_vector_stores = db.db_get_assistant_vector_stores(assistant_db_id)

        oai_service.delete_openai_assistant(openai_assistant_id)

        for vs_info in linked_vector_stores:
            try:
                oai_service.delete_openai_vector_store(vs_info['openai_vector_store_id'])
                db.db_delete_vector_store(vs_info['openai_vector_store_id'])
            except Exception as e:
                app.logger.warning(f"Could not delete vector store {vs_info['openai_vector_store_id']} from OpenAI: {e}")

        # Also delete all chats associated with this assistant
        # This will be handled by ON DELETE CASCADE from assistants.id in chats table.
        # So we just need to delete the assistant.

        db.db_delete_assistant(assistant_db_id)

        return jsonify({"message": f"Assistant {assistant_db_id} and associated resources deleted."}), 200
    except Exception as e:
        app.logger.error(f"Error deleting assistant: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

@app.route('/files/upload', methods=['POST'])
def upload_file():
    if 'file' not in request.files:
        return jsonify({"error": "No file part in the request"}), 400
    file = request.files['file']
    if file.filename == '':
        return jsonify({"error": "No selected file"}), 400
    if file and allowed_file(file.filename):
        filename = secure_filename(file.filename)
        local_file_path = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        try:
            file.save(local_file_path)

            openai_file = oai_service.upload_openai_file(local_file_path)
            openai_file_id = openai_file['id']
            file_db_id = db.db_add_openai_file(openai_file_id, filename, openai_file['purpose'], openai_file['bytes'])

            os.remove(local_file_path)

            response_data = db.db_get_openai_file(file_db_id)
            return jsonify(row_to_dict(response_data)), 201
        except Exception as e:
            app.logger.error(f"Error uploading file: {e}", exc_info=True)
            if os.path.exists(local_file_path):
                os.remove(local_file_path)
            return jsonify({"error": str(e)}), 500
    else:
        return jsonify({"error": "File type not allowed"}), 400

@app.route('/files/<string:openai_file_id>', methods=['DELETE'])
def delete_file(openai_file_id):
    try:
        file_data = db.db_get_openai_file_by_openai_id(openai_file_id)
        if not file_data:
            return jsonify({"error": "File not found in database"}), 404

        oai_service.delete_openai_file(openai_file_id)

        db.db_delete_openai_file(openai_file_id)

        return jsonify({"message": f"File {openai_file_id} deleted successfully."}), 200
    except Exception as e:
        app.logger.error(f"Error deleting file: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

@app.route('/assistants/<int:assistant_db_id>/add_files', methods=['POST'])
def add_files_to_assistant(assistant_db_id):
    data = request.json
    if not data or 'openai_file_ids' not in data:
        return jsonify({"error": "Missing required field: openai_file_ids (list of OpenAI file IDs)"}), 400

    openai_file_ids_to_add = data.get('openai_file_ids', [])

    assistant_data = db.db_get_assistant(assistant_db_id)
    if not assistant_data:
        return jsonify({"error": "Assistant not found"}), 404

    openai_assistant_id = assistant_data['openai_assistant_id']

    try:
        current_linked_vs = db.db_get_assistant_vector_stores(assistant_db_id)

        vector_store_openai_id = None
        vector_store_db_id = None

        if current_linked_vs:
            vector_store_openai_id = current_linked_vs[0]['openai_vector_store_id']
            vector_store_db_id = current_linked_vs[0]['vector_store_db_id']
            app.logger.info(f"Using existing Vector Store {vector_store_openai_id} for assistant {assistant_db_id}")
        else:
            vector_store_name = f"VS for {assistant_data['name']} ({uuid.uuid4().hex[:6]})"
            openai_vector_store = oai_service.create_openai_vector_store(vector_store_name)
            vector_store_openai_id = openai_vector_store['id']
            vector_store_db_id = db.db_create_vector_store(vector_store_openai_id, vector_store_name)
            db.db_link_assistant_to_vector_store(assistant_db_id, vector_store_db_id)
            app.logger.info(f"Created new Vector Store {vector_store_openai_id} for assistant {assistant_db_id}")

        added_files_info = []
        for openai_file_id in openai_file_ids_to_add:
            file_in_db = db.db_get_openai_file_by_openai_id(openai_file_id)
            if not file_in_db:
                app.logger.warning(f"File with OpenAI ID {openai_file_id} not found in DB. Skipping.")
                added_files_info.append({"openai_file_id": openai_file_id, "status": "skipped", "reason": "Not found in DB"})
                continue

            try:
                vector_store_file = oai_service.add_file_to_vector_store(vector_store_openai_id, openai_file_id)
                db.db_add_vector_store_file_link(vector_store_db_id, openai_file_id, vector_store_file['id'], vector_store_file['status'])

                while True:
                    status = oai_service.get_vector_store_file_status(vector_store_openai_id, vector_store_file['id'])
                    db.db_update_vector_store_file_status(vector_store_file['id'], status)
                    if status in ['completed', 'failed', 'cancelled']:
                        break
                    time.sleep(1)

                added_files_info.append({"openai_file_id": openai_file_id, "status": status})

            except Exception as e:
                app.logger.error(f"Error adding file {openai_file_id} to vector store: {e}", exc_info=True)
                added_files_info.append({"openai_file_id": openai_file_id, "status": "failed", "error": str(e)})

        assistant_info_after_add = oai_service.get_openai_assistant(openai_assistant_id)
        current_vs_ids_on_openai = []
        if assistant_info_after_add.get('tool_resources') and assistant_info_after_add['tool_resources'].get('file_search'):
            current_vs_ids_on_openai = assistant_info_after_add['tool_resources']['file_search'].get('vector_store_ids', [])

        if vector_store_openai_id not in current_vs_ids_on_openai:
            app.logger.info(f"Linking vector store {vector_store_openai_id} to assistant {openai_assistant_id} on OpenAI side.")
            oai_service.update_openai_assistant(
                openai_assistant_id=openai_assistant_id,
                add_vector_store_ids=[vector_store_openai_id]
            )

        return jsonify({
            "message": f"Attempted to add files to assistant {assistant_db_id}",
            "details": added_files_info
        }), 200

    except Exception as e:
        app.logger.error(f"Error adding files to assistant: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

@app.route('/vector_stores/<string:vector_store_openai_id>/files/<string:openai_file_id>', methods=['DELETE'])
def remove_file_from_specific_vector_store(vector_store_openai_id: str, openai_file_id: str):
    try:
        oai_service.remove_file_from_vector_store(vector_store_openai_id, openai_file_id)

        # Find the corresponding link in your DB and delete it
        link_data = db.db_get_vector_store_file_link_by_ids(vector_store_openai_id, openai_file_id)
        if link_data:
            db.db_delete_vector_store_file_link(link_data['openai_vector_store_file_id'])
            return jsonify({"message": f"File {openai_file_id} successfully removed from Vector Store {vector_store_openai_id} and database link deleted."}), 200
        else:
            return jsonify({"message": f"File {openai_file_id} removed from Vector Store {vector_store_openai_id}, but no corresponding link found in database."}), 200
    except Exception as e:
        app.logger.error(f"Error removing file from vector store: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

# --- New Chat Endpoint ---

@app.route('/chat', methods=['POST'])
def chat_with_assistant():
    data = request.json
    if not data:
        print("Invalid JSON")
        return jsonify({"error": "Invalid JSON"}), 400

    assistant_db_id = data.get('assistant_id')
    message_content = data.get('text')
    chat_db_id = data.get('thread') # Our internal DB chat ID

    if not assistant_db_id or not message_content:
        print("Missing required fields: assistant_id, message")
        return jsonify({"error": "Missing required fields: assistant_id, message"}), 400

    try:
        # 1. Get assistant details from DB
        assistant_data = db.db_get_assistant(assistant_db_id)
        if not assistant_data:
            return jsonify({"error": f"Assistant with ID {assistant_db_id} not found in database."}), 404
        openai_assistant_id = assistant_data['openai_assistant_id']

        # 2. Determine OpenAI Thread ID
        openai_thread_id = None
        current_chat_data = None

        if chat_db_id:
            current_chat_data = db.db_get_chat_by_db_id(chat_db_id)
            if current_chat_data:
                openai_thread_id = current_chat_data['openai_thread_id']
                app.logger.info(f"Using existing chat (DB ID: {chat_db_id}, OpenAI Thread ID: {openai_thread_id})")
            else:
                app.logger.warning(f"Chat with DB ID {chat_db_id} not found. Creating a new thread.")

        if not openai_thread_id:
            # Create a new thread in OpenAI
            new_thread = oai_service.create_openai_thread()
            openai_thread_id = new_thread['id']
            # Save new thread to our database
            chat_db_id = db.db_create_chat(assistant_db_id, openai_thread_id, chat_db_id)
            app.logger.info(f"Created new chat (DB ID: {chat_db_id}, OpenAI Thread ID: {openai_thread_id})")
            # Refresh chat data to get full record including total_tokens_used
            current_chat_data = db.db_get_chat_by_db_id(chat_db_id)


        # 3. Add user message to the thread
        oai_service.add_message_to_thread(openai_thread_id, message_content, role="user")

        # 4. Run the assistant
        run = oai_service.run_assistant_on_thread(openai_thread_id, openai_assistant_id)
        completed_run = oai_service.wait_for_run_completion(openai_thread_id, run['id'])

        # 5. Get assistant's response
        assistant_response = oai_service.get_latest_assistant_message(openai_thread_id)

        # 6. Get token usage and update DB
        total_tokens_for_interaction = 0
        if completed_run.get('usage'):
            total_tokens_for_interaction = completed_run['usage']['total_tokens']
            if current_chat_data:
                db.db_update_chat_tokens(current_chat_data['id'], total_tokens_for_interaction)
            else:
                # This case should ideally not happen if current_chat_data is set after creation
                # but as a fallback, update the newly created chat
                db.db_update_chat_tokens(chat_db_id, total_tokens_for_interaction)


        # Refresh chat data to include updated token count
        updated_chat_data = db.db_get_chat_by_db_id(chat_db_id)

        print({
            "chat_id": chat_db_id,
            "openai_thread_id": openai_thread_id,
            "message": assistant_response,
            "tokens_used_this_interaction": total_tokens_for_interaction,
            "total_tokens_used_in_chat": updated_chat_data['total_tokens_used'] if updated_chat_data else total_tokens_for_interaction
        })

        return jsonify({
            "chat_id": chat_db_id,
            "openai_thread_id": openai_thread_id,
            "message": assistant_response,
            "tokens_used_this_interaction": total_tokens_for_interaction,
            "total_tokens_used_in_chat": updated_chat_data['total_tokens_used'] if updated_chat_data else total_tokens_for_interaction
        }), 200

    except Exception as e:
        app.logger.error(f"Error in chat with assistant: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

    # --- Проверка работы базы данных при старте приложения ---
    try:
        db.check_db_connection()
    except ConnectionError:
        print("Application startup aborted due to database connection error.")
        exit(1)  # Завершаем приложение, если нет подключения к БД
    # --------------------------------------------------------

    proxy_url = "socks5://user304567:1ufzcv@181.215.231.14:17224"

    if proxy_url:
        proxy = GlobalSocksProxy(proxy_url)
        if proxy.activate_proxy():
            print("Proxy activated successfully")

            # Проверка работы прокси
            success, ip_or_error = proxy.test_proxy()
            if success:
                print(f"Proxy works! Your IP: {ip_or_error}")
                print(f"Original IP: {proxy.original_ip}")
            else:
                print(f"Proxy test failed: {ip_or_error}")
                exit()
    else:
        print("No proxy provided")

    app.run(debug=True, host='0.0.0.0', port=5000)
