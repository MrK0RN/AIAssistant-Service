#!/usr/bin/env python3
"""
AI Assistant Dashboard Server
A simple HTTP server to serve the dashboard static files
"""

import os
import http.server
import socketserver
from http.server import SimpleHTTPRequestHandler
import mimetypes

class DashboardHTTPRequestHandler(SimpleHTTPRequestHandler):
    """Custom HTTP request handler for the dashboard"""
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=os.getcwd(), **kwargs)
    
    def end_headers(self):
        """Add custom headers"""
        # Enable CORS for development
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        
        # Security headers
        self.send_header('X-Content-Type-Options', 'nosniff')
        self.send_header('X-Frame-Options', 'DENY')
        self.send_header('X-XSS-Protection', '1; mode=block')
        
        # Cache headers for static files
        if self.path.endswith(('.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.ico', '.svg')):
            self.send_header('Cache-Control', 'public, max-age=3600')
        else:
            self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
            self.send_header('Pragma', 'no-cache')
            self.send_header('Expires', '0')
            
        super().end_headers()
    
    def do_GET(self):
        """Handle GET requests"""
        # Serve index.html for root path
        if self.path == '/':
            self.path = '/index.html'
        
        # Handle 404 by serving index.html (for SPA behavior)
        try:
            super().do_GET()
        except:
            if not self.path.startswith('/api/'):
                self.path = '/index.html'
                super().do_GET()
    
    def do_OPTIONS(self):
        """Handle OPTIONS requests for CORS"""
        self.send_response(200)
        self.end_headers()
    
    def log_message(self, format, *args):
        """Custom logging format"""
        print(f"[{self.log_date_time_string()}] {format % args}")

def run_server():
    """Run the dashboard server"""
    PORT = int(os.environ.get('PORT', 5000))
    HOST = '0.0.0.0'
    
    # Set up MIME types
    mimetypes.add_type('application/javascript', '.js')
    mimetypes.add_type('text/css', '.css')
    mimetypes.add_type('image/svg+xml', '.svg')
    
    # Create server
    with socketserver.TCPServer((HOST, PORT), DashboardHTTPRequestHandler) as httpd:
        print(f"AI Assistant Dashboard Server")
        print(f"Serving at http://{HOST}:{PORT}")
        print(f"Local access: http://localhost:{PORT}")
        print("Press Ctrl+C to stop the server")
        
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\nShutting down server...")
            httpd.shutdown()
            print("Server stopped.")

if __name__ == "__main__":
    # Ensure we're in the correct directory
    current_dir = os.path.dirname(os.path.abspath(__file__))
    os.chdir(current_dir)
    
    # Check if required files exist
    required_files = ['index.html', 'styles.css', 'script.js']
    missing_files = [f for f in required_files if not os.path.exists(f)]
    
    if missing_files:
        print("Error: Missing required files:")
        for file in missing_files:
            print(f"  - {file}")
        print("Please ensure all files are in the same directory as server.py")
        exit(1)
    
    # Run the server
    run_server()
