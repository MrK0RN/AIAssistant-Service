// Authentication JavaScript111s

class AuthManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupPasswordStrength();
        this.setupFormValidation();
        this.setupFormSubmission();
        this.setupSocialAuth();
        this.setupLoginForm();
    }

    // Password strength indicator
    setupPasswordStrength() {
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        if (passwordInput && strengthFill && strengthText) {
            passwordInput.addEventListener('input', (e) => {
                const password = e.target.value;
                const strength = this.calculatePasswordStrength(password);
                this.updatePasswordStrength(strength, strengthFill, strengthText);
            });
        }
    }

    calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score += 25;
        if (password.length >= 12) score += 25;
        if (/[a-z]/.test(password)) score += 10;
        if (/[A-Z]/.test(password)) score += 10;
        if (/[0-9]/.test(password)) score += 10;
        if (/[^A-Za-z0-9]/.test(password)) score += 20;

        return Math.min(score, 100);
    }

    updatePasswordStrength(score, fillElement, textElement) {
        fillElement.style.width = `${score}%`;
        fillElement.className = 'strength-fill';

        if (score < 25) {
            fillElement.classList.add('weak');
            textElement.textContent = 'Слабый пароль';
            textElement.className = 'text-danger';
        } else if (score < 50) {
            fillElement.classList.add('fair');
            textElement.textContent = 'Удовлетворительный пароль';
            textElement.className = 'text-warning';
        } else if (score < 75) {
            fillElement.classList.add('good');
            textElement.textContent = 'Хороший пароль';
            textElement.className = 'text-info';
        } else {
            fillElement.classList.add('strong');
            textElement.textContent = 'Сильный пароль';
            textElement.className = 'text-success';
        }
    }

    // Form validation
    setupFormValidation() {
        const form = document.getElementById('registerForm');
        if (!form) return;

        const inputs = form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });

        // Password confirmation validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        
        if (password && confirmPassword) {
            confirmPassword.addEventListener('blur', () => {
                this.validatePasswordConfirmation(password, confirmPassword);
            });
        }

        // Email validation
        const email = document.getElementById('email');
        if (email) {
            email.addEventListener('blur', () => this.validateEmail(email));
        }
    }

    validateField(field) {
        const value = field.value.trim();
        
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'Это поле обязательно для заполнения');
            return false;
        }

        return true;
    }

    validateEmail(emailField) {
        const email = emailField.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.showFieldError(emailField, 'Введите корректный email адрес');
            return false;
        }
        
        this.clearFieldError(emailField);
        return true;
    }

    validatePasswordConfirmation(passwordField, confirmField) {
        const password = passwordField.value;
        const confirm = confirmField.value;
        
        if (password !== confirm) {
            this.showFieldError(confirmField, 'Пароли не совпадают');
            return false;
        }
        
        this.clearFieldError(confirmField);
        return true;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        
        const errorFeedback = field.parentNode.querySelector('.invalid-feedback');
        if (errorFeedback) {
            errorFeedback.remove();
        }
    }

    // Form submission
    setupFormSubmission() {
        const form = document.getElementById('registerForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegistration();
        });
    }

    async handleRegistration() {
        const form = document.getElementById('registerForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Validate all fields
        if (!this.validateForm()) {
            return;
        }

        // Show loading state
        this.setLoadingState(submitBtn, true);

        try {
            const formData = this.getFormData();
            // Call Flask registration API
            const response = await fetch('/api/registration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            const data = await response.json();
            if (data.success) {
                // Show success message
                this.showMessage(data.message || 'Регистрация успешна! Добро пожаловать!', 'success');
                
                // Redirect to dashboard immediately
                window.location.href = data.redirect || '/index.php';
            } else {
                // Show validation errors
                if (data.error.length > 0) {
                    this.showMessage(data.error, 'error');
                } else {
                    this.showMessage('Произошла ошибка при регистрации', 'error');
                    alert(data.error);
                }
            }
            
        } catch (error) {
            console.log('Registration error:', error);
            this.showMessage('Произошла ошибка соединения. Попробуйте еще раз.', 'error');
        } finally {
            this.setLoadingState(submitBtn, false);
        }
    }

    validateForm() {
        const form = document.getElementById('registerForm');
        const requiredFields = form.querySelectorAll('input[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Additional validations
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const termsCheck = document.getElementById('termsCheck');

        if (!this.validateEmail(email)) isValid = false;
        if (!this.validatePasswordConfirmation(password, confirmPassword)) isValid = false;
        
        if (!termsCheck.checked) {
            this.showMessage('Необходимо принять условия использования', 'error');
            isValid = false;
        }

        return isValid;
    }

    getFormData() {
        return {
            username: document.getElementById('username').value.trim(),
            firstName: document.getElementById('firstName').value.trim(),
            lastName: document.getElementById('lastName').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            password: document.getElementById('password').value,
            confirmPassword: document.getElementById('confirmPassword').value
        };
    }

    async simulateRegistration(data) {
        // Simulate API call delay
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Simulate random success/failure for demo
        if (Math.random() > 0.8) {
            throw new Error('Email уже используется');
        }
        
        // Store user data in localStorage for demo
        localStorage.setItem('registeredUser', JSON.stringify({
            ...data,
            password: undefined, // Don't store password
            registeredAt: new Date().toISOString()
        }));
        
        return { success: true };
    }

    setLoadingState(button, loading) {
        if (loading) {
            button.classList.add('btn-loading');
            button.disabled = true;
        } else {
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    }

    showMessage(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Try to find the most appropriate container
        const form = document.getElementById('registerForm') || document.getElementById('loginForm');
        if (form) {
            form.insertBefore(alertDiv, form.firstChild);
        } else {
            // Fallback to document body if no form found
            const container = document.querySelector('.container') || document.body;
            if (container.firstChild) {
                container.insertBefore(alertDiv, container.firstChild);
            } else {
                container.appendChild(alertDiv);
            }
        }

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Social authentication
    setupSocialAuth() {
        const googleBtn = document.querySelector('.social-auth .btn:first-child');
        const githubBtn = document.querySelector('.social-auth .btn:last-child');

        if (googleBtn) {
            googleBtn.addEventListener('click', () => this.handleSocialAuth('google'));
        }

        if (githubBtn) {
            githubBtn.addEventListener('click', () => this.handleSocialAuth('github'));
        }
    }

    handleSocialAuth(provider) {
        this.showMessage(`Перенаправление на ${provider === 'google' ? 'Google' : 'GitHub'}...`, 'info');
        
        // Simulate social auth
        setTimeout(() => {
            this.showMessage('Социальная авторизация временно недоступна', 'error');
        }, 1500);
    }

    // Login form setup
    setupLoginForm() {
        const loginForm = document.getElementById('loginForm');
        if (!loginForm) return;

        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });
    }

    async handleLogin() {
        const form = document.getElementById('loginForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Get form data
        const login = document.getElementById('login').value.trim();
        const password = document.getElementById('password').value;

        // Basic validation
        if (!login || !password) {
            this.showMessage('Пожалуйста, заполните все поля', 'error');
            return;
        }

        // Show loading state
        this.setLoadingState(submitBtn, true);

        try {
            const response = await fetch('/api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    login: login,
                    password: password
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message || 'Вход выполнен успешно!', 'success');
                
                // Redirect to dashboard immediately
                window.location.href = data.redirect || '/dashboard';
            } else {
                // Show validation errors
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(error => {
                        this.showMessage(error, 'error');
                    });
                } else {
                    this.showMessage('Неверный логин или пароль', 'error');
                }
            }
            
        } catch (error) {
            console.error('Login error:', error.message || error);
            this.showMessage('Произошла ошибка соединения. Попробуйте еще раз.', 'error');
        } finally {
            this.setLoadingState(submitBtn, false);
        }
    }
}

// Theme toggle functionality
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    // Initialize auth manager
    new AuthManager();
    
    // Apply saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
    
    // Add theme toggle button if needed
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
});

// Utility functions
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.startsWith('7') || value.startsWith('8')) {
        value = value.substring(1);
    }
    
    if (value.length >= 10) {
        value = `+7 (${value.substring(0, 3)}) ${value.substring(3, 6)}-${value.substring(6, 8)}-${value.substring(8, 10)}`;
    }
    
    input.value = value;
}

// Add phone formatting if phone field exists
document.addEventListener('DOMContentLoaded', () => {
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', () => formatPhoneNumber(phoneInput));
    }
});