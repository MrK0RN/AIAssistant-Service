from flask import render_template, render_template_string, request, redirect, url_for, flash, jsonify, send_file
from flask_login import login_user, logout_user, login_required, current_user
from app import app, db
from models import User
import logging
import requests
import os

@app.route('/')
def index():
    """Landing page"""
    return send_file('landing.html')

@app.route('/dashboard')
@login_required
def dashboard():
    """Main dashboard - requires authentication"""
    return send_file('index.html')



@app.route('/register', methods=['GET', 'POST'])
def register():
    """User registration"""
    if request.method == 'GET':
        return send_file('register.html')
    
    try:
        # Get form data
        data = request.get_json() if request.is_json else request.form
        
        username = data.get('username', '').strip()
        email = data.get('email', '').strip().lower()
        password = data.get('password', '')
        confirm_password = data.get('confirmPassword', '')
        first_name = data.get('firstName', '').strip()
        last_name = data.get('lastName', '').strip()
        phone = data.get('phone', '').strip()
        
        # Validation
        errors = []
        
        # Check required fields
        if not username:
            errors.append('Имя пользователя обязательно')
        if not email:
            errors.append('Email обязателен')
        if not password:
            errors.append('Пароль обязателен')
        
        # Validate password confirmation
        if password != confirm_password:
            errors.append('Пароли не совпадают')
        
        # Validate email format
        if email and not User.validate_email(email):
            errors.append('Некорректный формат email')
        
        # Validate username
        if username:
            is_valid, message = User.validate_username(username)
            if not is_valid:
                errors.append(message)
        
        # Validate password strength
        if password:
            is_valid, message = User.validate_password(password)
            if not is_valid:
                errors.append(message)
        
        # Check if user already exists
        if username and User.query.filter_by(username=username).first():
            errors.append('Пользователь с таким именем уже существует')
        
        if email and User.query.filter_by(email=email).first():
            errors.append('Пользователь с таким email уже существует')
        
        if errors:
            if request.is_json:
                return jsonify({'success': False, 'errors': errors}), 400
            else:
                for error in errors:
                    flash(error, 'error')
                return redirect(url_for('register'))
        
        # Create new user
        user = User(
            username=username,
            email=email,
            password=password,
            first_name=first_name if first_name else None,
            last_name=last_name if last_name else None,
            phone=phone if phone else None
        )
        
        db.session.add(user)
        db.session.commit()
        
        logging.info(f"New user registered: {username} ({email})")
        
        # Auto-login the user
        login_user(user, remember=True)
        
        if request.is_json:
            return jsonify({
                'success': True, 
                'message': 'Регистрация успешна!',
                'redirect': '/dashboard'
            })
        else:
            flash('Регистрация успешна! Добро пожаловать!', 'success')
            return redirect('/dashboard')
            
    except Exception as e:
        logging.error(f"Registration error: {str(e)}")
        db.session.rollback()
        
        error_message = 'Произошла ошибка при регистрации. Попробуйте еще раз.'
        
        if request.is_json:
            return jsonify({'success': False, 'errors': [error_message]}), 500
        else:
            flash(error_message, 'error')
            return redirect(url_for('register'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    """User login"""
    if request.method == 'GET':
        return send_file('login.html')
    
    try:
        # Get form data
        data = request.get_json() if request.is_json else request.form
        
        login_field = data.get('login', '').strip()  # Can be username or email
        password = data.get('password', '')
        remember = bool(data.get('remember', False))
        
        # Validation
        if not login_field:
            error = 'Введите имя пользователя или email'
            if request.is_json:
                return jsonify({'success': False, 'errors': [error]}), 400
            else:
                flash(error, 'error')
                return redirect(url_for('login'))
        
        if not password:
            error = 'Введите пароль'
            if request.is_json:
                return jsonify({'success': False, 'errors': [error]}), 400
            else:
                flash(error, 'error')
                return redirect(url_for('login'))
        
        # Find user by username or email
        user = None
        if '@' in login_field:
            user = User.query.filter_by(email=login_field.lower()).first()
        else:
            user = User.query.filter_by(username=login_field).first()
        
        # Check credentials
        if not user or not user.check_password(password):
            error = 'Неверное имя пользователя/email или пароль'
            if request.is_json:
                return jsonify({'success': False, 'errors': [error]}), 400
            else:
                flash(error, 'error')
                return redirect(url_for('login'))
        
        # Check if user is active
        if not user.active:
            error = 'Ваш аккаунт деактивирован. Обратитесь к администратору.'
            if request.is_json:
                return jsonify({'success': False, 'errors': [error]}), 400
            else:
                flash(error, 'error')
                return redirect(url_for('login'))
        
        # Update last login
        user.last_login = db.session.query(db.func.now()).scalar()
        db.session.commit()
        
        # Login user
        login_user(user, remember=remember)
        
        logging.info(f"User logged in: {user.username}")
        
        # Get next page from URL parameters or form data
        next_page = request.args.get('next') or request.form.get('next') or (request.get_json() or {}).get('next')
        if next_page and next_page.startswith('/'):
            redirect_url = next_page
        else:
            redirect_url = '/dashboard'  # Use absolute path instead of url_for
        
        if request.is_json:
            return jsonify({
                'success': True,
                'message': f'Добро пожаловать, {user.get_full_name()}!',
                'redirect': redirect_url
            })
        else:
            flash(f'Добро пожаловать, {user.get_full_name()}!', 'success')
            return redirect(redirect_url)
            
    except Exception as e:
        logging.error(f"Login error: {str(e)}")
        
        error_message = 'Произошла ошибка при входе. Попробуйте еще раз.'
        
        if request.is_json:
            return jsonify({'success': False, 'errors': [error_message]}), 500
        else:
            flash(error_message, 'error')
            return redirect(url_for('login'))

@app.route('/logout')
@login_required
def logout():
    """User logout"""
    username = current_user.username
    logout_user()
    flash(f'Вы успешно вышли из системы, {username}', 'info')
    return redirect(url_for('index'))

@app.route('/api/user/profile')
@login_required
def user_profile():
    """Get current user profile data"""
    return jsonify({
        'id': current_user.id,
        'username': current_user.username,
        'email': current_user.email,
        'fullName': current_user.get_full_name(),
        'firstName': current_user.first_name,
        'lastName': current_user.last_name,
        'phone': current_user.phone,
        'isVerified': current_user.is_verified,
        'createdAt': current_user.created_at.isoformat(),
        'lastLogin': current_user.last_login.isoformat() if current_user.last_login else None
    })

@app.route('/api/check-username')
def check_username():
    """Check if username is available"""
    username = request.args.get('username', '').strip()
    
    if not username:
        return jsonify({'available': False, 'message': 'Имя пользователя не может быть пустым'})
    
    # Validate username format
    is_valid, message = User.validate_username(username)
    if not is_valid:
        return jsonify({'available': False, 'message': message})
    
    # Check if username exists
    exists = User.query.filter_by(username=username).first() is not None
    
    return jsonify({
        'available': not exists,
        'message': 'Имя пользователя занято' if exists else 'Имя пользователя доступно'
    })

@app.route('/api/check-email')
def check_email():
    """Check if email is available"""
    email = request.args.get('email', '').strip().lower()
    
    if not email:
        return jsonify({'available': False, 'message': 'Email не может быть пустым'})
    
    # Validate email format
    if not User.validate_email(email):
        return jsonify({'available': False, 'message': 'Некорректный формат email'})
    
    # Check if email exists
    exists = User.query.filter_by(email=email).first() is not None
    
    return jsonify({
        'available': not exists,
        'message': 'Email занят' if exists else 'Email доступен'
    })

# Telegram Bot Integration API
@app.route('/api/bots/token', methods=['POST'])
@login_required
def get_bot_token():
    """Get API token for bot management"""
    try:
        bot_service_url = "http://localhost:5001/get_token"
        response = requests.post(bot_service_url, 
                               json={"description": f"Token for user {current_user.username}"},
                               headers={"Content-Type": "application/json"})
        
        if response.status_code == 200:
            return jsonify(response.json())
        else:
            return jsonify({"error": "Failed to get token from bot service"}), 500
            
    except requests.exceptions.ConnectionError:
        return jsonify({"error": "Bot service not available. Please contact administrator."}), 503
    except Exception as e:
        logging.error(f"Token generation error: {str(e)}")
        return jsonify({"error": "Failed to generate token"}), 500

@app.route('/add-assistant', methods=['POST'])
@login_required
def add_assistant():
    """Add new AI assistant with integration"""
    try:
        data = request.get_json()
        logging.info(f"Received assistant creation request: {data}")
        
        # Validate required fields
        required_fields = ['name', 'tone', 'language', 'prompt', 'integration_type', 'integration_data']
        missing_fields = [field for field in required_fields if field not in data]
        if missing_fields:
            logging.error(f"Missing required fields: {missing_fields}")
            return jsonify({"error": f"Missing required fields: {', '.join(missing_fields)}"}), 400
        
        integration_type = data['integration_type']
        integration_data = data['integration_data']
        
        logging.info(f"Integration type: {integration_type}, Data: {integration_data}")
        
        # Validate integration-specific data
        if integration_type == 'telegram_bot':
            if 'token' not in integration_data or not integration_data['token'].strip():
                return jsonify({"error": "Bot token is required"}), 400
        elif integration_type == 'telegram_client':
            required_client_fields = ['api_id', 'api_hash', 'phone']
            missing_integration_fields = [field for field in required_client_fields if field not in integration_data or not str(integration_data[field]).strip()]
            if missing_integration_fields:
                logging.error(f"Missing telegram_client fields: {missing_integration_fields}")
                return jsonify({"error": f"Missing fields: {', '.join(missing_integration_fields)}"}), 400
        elif integration_type in ['whatsapp', 'instagram']:
            if 'api_key' not in integration_data or not integration_data['api_key'].strip():
                return jsonify({"error": "API key is required"}), 400
        else:
            return jsonify({"error": f"Unknown integration type: {integration_type}"}), 400
        
        # Create new assistant in database
        from models import Assistant
        assistant = Assistant(
            user_id=current_user.id,
            name=data['name'],
            tone=data['tone'],
            language=data['language'],
            prompt=data['prompt'],
            integration_type=integration_type,
            integration_data=integration_data
        )
        
        db.session.add(assistant)
        db.session.commit()
        
        logging.info(f"Created assistant '{data['name']}' (ID: {assistant.id}) for user {current_user.username} with {integration_type}")
        
        return jsonify({
            "success": True,
            "message": "Assistant created successfully",
            "assistant_id": assistant.id,
            "assistant": assistant.to_dict()
        })
        
    except Exception as e:
        db.session.rollback()
        logging.error(f"Assistant creation error: {str(e)}")
        return jsonify({"error": "Failed to create assistant"}), 500

@app.route('/api/assistants')
@login_required
def get_user_assistants():
    """Get current user's assistants"""
    try:
        from models import Assistant
        assistants = Assistant.query.filter_by(user_id=current_user.id).order_by(Assistant.created_at.desc()).all()
        
        return jsonify({
            "success": True,
            "assistants": [assistant.to_dict() for assistant in assistants]
        })
        
    except Exception as e:
        logging.error(f"Error fetching assistants: {str(e)}")
        return jsonify({"error": "Failed to fetch assistants"}), 500

@app.route('/api/assistants/<int:assistant_id>', methods=['DELETE'])
@login_required
def delete_assistant(assistant_id):
    """Delete user's assistant"""
    try:
        from models import Assistant
        assistant = Assistant.query.filter_by(id=assistant_id, user_id=current_user.id).first()
        
        if not assistant:
            return jsonify({"error": "Assistant not found"}), 404
        
        db.session.delete(assistant)
        db.session.commit()
        
        logging.info(f"Deleted assistant '{assistant.name}' (ID: {assistant_id}) for user {current_user.username}")
        
        return jsonify({
            "success": True,
            "message": "Assistant deleted successfully"
        })
        
    except Exception as e:
        db.session.rollback()
        logging.error(f"Error deleting assistant: {str(e)}")
        return jsonify({"error": "Failed to delete assistant"}), 500

@app.route('/api/assistants/<int:assistant_id>/toggle', methods=['POST'])
@login_required
def toggle_assistant_status(assistant_id):
    """Toggle assistant status (active/inactive)"""
    try:
        from models import Assistant
        assistant = Assistant.query.filter_by(id=assistant_id, user_id=current_user.id).first()
        
        if not assistant:
            return jsonify({"error": "Assistant not found"}), 404
        
        # Toggle status
        assistant.status = 'inactive' if assistant.status == 'active' else 'active'
        db.session.commit()
        
        logging.info(f"Toggled assistant '{assistant.name}' (ID: {assistant_id}) status to {assistant.status} for user {current_user.username}")
        
        return jsonify({
            "success": True,
            "message": "Assistant status updated successfully",
            "new_status": assistant.status
        })
        
    except Exception as e:
        db.session.rollback()
        logging.error(f"Error toggling assistant status: {str(e)}")
        return jsonify({"error": "Failed to toggle assistant status"}), 500

@app.route('/api/bots/add', methods=['POST'])
@login_required
def add_telegram_bot():
    """Add new Telegram bot"""
    try:
        data = request.get_json()
        
        # Validate required fields
        required_fields = ['api_id', 'api_hash', 'phone_number']
        if not all(field in data for field in required_fields):
            return jsonify({"error": "Missing required fields: api_id, api_hash, phone_number"}), 400
        
        # Add user identifier to bot name
        bot_name = data.get('bot_name', f"bot_{current_user.username}_{data['api_id']}")
        
        bot_data = {
            "api_id": data['api_id'],
            "api_hash": data['api_hash'], 
            "phone_number": data['phone_number'],
            "bot_name": bot_name
        }
        
        # Get token first
        token_response = requests.post("http://localhost:5001/get_token",
                                     json={"description": f"Bot management for {current_user.username}"},
                                     headers={"Content-Type": "application/json"})
        
        if token_response.status_code != 200:
            return jsonify({"error": "Failed to authenticate with bot service"}), 500
        
        token = token_response.json().get('token')
        
        # Add bot
        bot_service_url = "http://localhost:5001/add_bot"
        response = requests.post(bot_service_url,
                               json=bot_data,
                               headers={
                                   "Content-Type": "application/json",
                                   "Authorization": f"Bearer {token}"
                               })
        
        if response.status_code == 200:
            logging.info(f"Bot added by user {current_user.username}: {bot_name}")
            return jsonify({
                "success": True,
                "message": f"Бот {bot_name} успешно добавлен",
                "bot_name": bot_name
            })
        else:
            return jsonify({"error": "Failed to add bot"}), 500
            
    except requests.exceptions.ConnectionError:
        return jsonify({"error": "Bot service not available. Please contact administrator."}), 503
    except Exception as e:
        logging.error(f"Add bot error: {str(e)}")
        return jsonify({"error": "Failed to add bot"}), 500

@app.route('/api/bots/list', methods=['GET'])
@login_required
def list_user_bots():
    """List user's bots"""
    try:
        # In a real implementation, you would query a database
        # For now, return a placeholder response
        return jsonify({
            "bots": [],
            "message": "Bot listing feature will be implemented with database integration"
        })
    except Exception as e:
        logging.error(f"List bots error: {str(e)}")
        return jsonify({"error": "Failed to list bots"}), 500

# Static file serving
@app.route('/<path:filename>')
def static_files(filename):
    """Serve static files"""
    try:
        return send_file(filename)
    except FileNotFoundError:
        return "", 404

# Error handlers
@app.errorhandler(404)
def not_found(error):
    return render_template_string("""
    <!DOCTYPE html>
    <html>
    <head>
        <title>Страница не найдена</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            h1 { color: #dc3545; }
            a { color: #007bff; text-decoration: none; }
        </style>
    </head>
    <body>
        <h1>404 - Страница не найдена</h1>
        <p>Запрашиваемая страница не существует.</p>
        <a href="/">Вернуться на главную</a>
    </body>
    </html>
    """), 404

@app.errorhandler(500)
def internal_error(error):
    db.session.rollback()
    return render_template_string("""
    <!DOCTYPE html>
    <html>
    <head>
        <title>Внутренняя ошибка сервера</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            h1 { color: #dc3545; }
            a { color: #007bff; text-decoration: none; }
        </style>
    </head>
    <body>
        <h1>500 - Внутренняя ошибка сервера</h1>
        <p>Произошла ошибка на сервере. Попробуйте еще раз позже.</p>
        <a href="/">Вернуться на главную</a>
    </body>
    </html>
    """), 500