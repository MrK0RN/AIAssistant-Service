#!/usr/bin/env python3
"""
Script to create demo user for testing
"""

from app import app, db
from models import User
import logging

def create_demo_user():
    """Create demo user if it doesn't exist"""
    with app.app_context():
        # Check if demo user already exists
        demo_user = User.query.filter_by(email='demo@example.com').first()
        
        if demo_user:
            logging.info("Demo user already exists")
            return demo_user
        
        # Create demo user
        demo_user = User(
            username='demo',
            email='demo@example.com',
            password='demo123',
            first_name='Демо',
            last_name='Пользователь'
        )
        
        # Mark as verified for demo purposes
        demo_user.is_verified = True
        
        db.session.add(demo_user)
        db.session.commit()
        
        logging.info(f"Demo user created: {demo_user.username} ({demo_user.email})")
        return demo_user

if __name__ == '__main__':
    logging.basicConfig(level=logging.INFO)
    create_demo_user()
    print("Demo user setup complete!")