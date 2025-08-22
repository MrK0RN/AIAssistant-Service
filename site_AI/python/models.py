from datetime import datetime
from flask_sqlalchemy import SQLAlchemy
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from app import db, login_manager
import re

class User(UserMixin, db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False, index=True)
    email = db.Column(db.String(120), unique=True, nullable=False, index=True)
    password_hash = db.Column(db.String(256), nullable=False)
    first_name = db.Column(db.String(50), nullable=True)
    last_name = db.Column(db.String(50), nullable=True)
    phone = db.Column(db.String(20), nullable=True)
    active = db.Column(db.Boolean, default=True, nullable=False)
    is_verified = db.Column(db.Boolean, default=False, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    last_login = db.Column(db.DateTime, nullable=True)
    
    def __init__(self, username, email, password, first_name=None, last_name=None, phone=None):
        self.username = username
        self.email = email
        self.set_password(password)
        self.first_name = first_name
        self.last_name = last_name
        self.phone = phone
    
    def set_password(self, password):
        """Hash and set password"""
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        """Check if provided password matches hash"""
        return check_password_hash(self.password_hash, password)
    
    def get_full_name(self):
        """Get user's full name"""
        if self.first_name and self.last_name:
            return f"{self.first_name} {self.last_name}"
        elif self.first_name:
            return self.first_name
        elif self.last_name:
            return self.last_name
        else:
            return self.username
    
    @staticmethod
    def validate_email(email):
        """Validate email format"""
        pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        return re.match(pattern, email) is not None
    
    @staticmethod
    def validate_password(password):
        """Validate password strength"""
        if len(password) < 8:
            return False, "Пароль должен содержать минимум 8 символов"
        
        if not re.search(r'[A-Za-z]', password):
            return False, "Пароль должен содержать буквы"
        
        if not re.search(r'\d', password):
            return False, "Пароль должен содержать цифры"
        
        return True, "Пароль соответствует требованиям"
    
    @staticmethod
    def validate_username(username):
        """Validate username format"""
        if len(username) < 3:
            return False, "Имя пользователя должно содержать минимум 3 символа"
        
        if len(username) > 80:
            return False, "Имя пользователя не может быть длиннее 80 символов"
        
        if not re.match(r'^[a-zA-Z0-9_.-]+$', username):
            return False, "Имя пользователя может содержать только буквы, цифры, точки, дефисы и подчеркивания"
        
        return True, "Имя пользователя корректно"
    
    @property
    def is_active(self):
        """Required by Flask-Login"""
        return self.active
    
    def __repr__(self):
        return f'<User {self.username}>'

class Assistant(db.Model):
    __tablename__ = 'assistants'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False, index=True)
    name = db.Column(db.String(100), nullable=False)
    tone = db.Column(db.String(50), nullable=False)
    language = db.Column(db.String(10), nullable=False)
    prompt = db.Column(db.Text, nullable=False)
    integration_type = db.Column(db.String(50), nullable=False)
    integration_data = db.Column(db.JSON, nullable=False)
    status = db.Column(db.String(20), default='active', nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    
    # Relationship
    user = db.relationship('User', backref=db.backref('assistants', lazy=True))
    
    def __init__(self, user_id, name, tone, language, prompt, integration_type, integration_data):
        self.user_id = user_id
        self.name = name
        self.tone = tone
        self.language = language
        self.prompt = prompt
        self.integration_type = integration_type
        self.integration_data = integration_data
    
    def to_dict(self):
        """Convert assistant to dictionary"""
        return {
            'id': self.id,
            'name': self.name,
            'tone': self.tone,
            'language': self.language,
            'prompt': self.prompt,
            'integration_type': self.integration_type,
            'status': self.status,
            'created_at': self.created_at.isoformat(),
            'updated_at': self.updated_at.isoformat()
        }
    
    def __repr__(self):
        return f'<Assistant {self.name} ({self.integration_type})>'

@login_manager.user_loader
def load_user(user_id):
    """Load user for Flask-Login"""
    return User.query.get(int(user_id))