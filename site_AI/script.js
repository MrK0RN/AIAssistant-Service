// This file incorporates changes from the code edit, focusing on form handling and UI improvements for the AI Assistant Dashboard.
// AI Assistant Dashboard - Main JavaScript File
// Author: AI Dashboard Team
// Description: Complete frontend functionality for the AI assistant management dashboard

// Global variables for assistant modal
let currentAssistantStep = 1;

class DashboardApp {
    constructor() {
        this.currentSection = 'welcome';
        this.theme = localStorage.getItem('theme') || 'light';
        this.mockData = this.initializeMockData();
        this.charts = {};

        this.init();
    }

    async init() {
        // Load user profile first to show name in header immediately
        await this.loadUserProfile();

        this.setupEventListeners();
        this.initializeTheme();
        this.loadMockData();
        this.setupNavigation();
        this.initializeCharts();
        this.setupFileUpload();
        this.setupModals();
        this.setCurrentDate();
        this.updateDashboardStatistics();

        // Handle initial navigation immediately after initialization
        if (window.handleInitialNavigation) {
            window.handleInitialNavigation();
        }
    }

    // Initialize mock data structure
    initializeMockData() {
        const existingData = localStorage.getItem('dashboardData');
        if (existingData) {
            return JSON.parse(existingData);
        }

        return {
            assistants: [
                {
                    id: 1,
                    name: 'Саппорт-бот',
                    status: true,
                    tone: 'friendly',
                    language: 'ru',
                    prompt: 'Вы дружелюбный помощник службы поддержки. Отвечайте вежливо и помогайте решать проблемы пользователей.',
                    avatar: '🎧',
                    created: new Date().toISOString()
                },
                {
                    id: 2,
                    name: 'Продажный ассистент',
                    status: true,
                    tone: 'expert',
                    language: 'ru',
                    prompt: 'Вы эксперт по продажам. Помогайте клиентам выбрать подходящий продукт и отвечайте на вопросы о ценах.',
                    avatar: '💼',
                    created: new Date().toISOString()
                },
                {
                    id: 3,
                    name: 'Консультант',
                    status: false,
                    tone: 'formal',
                    language: 'ru',
                    prompt: 'Вы профессиональный консультант. Предоставляйте точную информацию и экспертные советы.',
                    avatar: '👨‍💼',
                    created: new Date().toISOString()
                }
            ],
            messages: [
                {
                    id: 1,
                    client: 'Анна Петрова',
                    channel: 'telegram',
                    lastMessage: 'Как настроить интеграцию с CRM?',
                    time: new Date(Date.now() - 2 * 60 * 1000).toISOString(),
                    status: 'new',
                    avatar: 'https://ui-avatars.com/api/?name=Анна&background=28a745&color=fff&size=40'
                },
                {
                    id: 2,
                    client: 'Иван Сидоров',
                    channel: 'whatsapp',
                    lastMessage: 'Спасибо за помощь!',
                    time: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
                    status: 'completed',
                    avatar: 'https://ui-avatars.com/api/?name=Иван&background=dc3545&color=fff&size=40'
                },
                {
                    id: 3,
                    client: 'Мария Козлова',
                    channel: 'telegram',
                    lastMessage: 'У меня вопрос по тарифам',
                    time: new Date(Date.now() - 60 * 60 * 1000).toISOString(),
                    status: 'new',
                    avatar: 'https://ui-avatars.com/api/?name=Мария&background=ffc107&color=000&size=40'
                }
            ],
            documents: [
                {
                    id: 1,
                    name: 'Руководство пользователя.pdf',
                    type: 'pdf',
                    size: '2.4 MB',
                    uploaded: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString(),
                    assistant: 'support',
                    processing: false
                },
                {
                    id: 2,
                    name: 'FAQ по продуктам.docx',
                    type: 'docx',
                    size: '1.8 MB',
                    uploaded: new Date(Date.now() - 12 * 60 * 60 * 1000).toISOString(),
                    assistant: 'sales',
                    processing: false
                }
            ],
            integrations: {
                telegram: { connected: true, token: 'bot123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw' },
                whatsapp: { connected: false, token: '' },
                instagram: { connected: false, token: '' },
                amocrm: { connected: true, token: 'crm_token_123' },
                bitrix24: { connected: false, token: '' }
            },
            statistics: {
                newDialogs: 156,
                questionsForDb: 23,
                activeUsers: 89,
                channels: {
                    telegram: 45,
                    whatsapp: 32,
                    instagram: 28,
                    email: 21,
                    website: 30
                }
            }
        };
    }

    // Save data to localStorage
    saveData() {
        localStorage.setItem('dashboardData', JSON.stringify(this.mockData));
    }

    // Load data from localStorage
    loadMockData() {
        const data = localStorage.getItem('dashboardData');
        if (data) {
            this.mockData = JSON.parse(data);
        }
    }

    // Setup all event listeners
    setupEventListeners() {
        // Theme toggle
        document.querySelector('.theme-toggle').addEventListener('click', () => {
            this.toggleTheme();
        });

        // Sidebar toggle
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        if (sidebarToggle) {
            console.log('Sidebar toggle button found');
            sidebarToggle.addEventListener('click', (e) => {
                console.log('Sidebar toggle clicked');
                e.preventDefault();
                this.toggleSidebar();
            });
        } else {
            console.error('Sidebar toggle button not found!');
        }

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('show') && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        // Navigation links
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.getAttribute('data-section');
                this.navigateToSection(section);
                
                // Close sidebar on mobile after navigation
                if (window.innerWidth <= 768) {
                    const sidebar = document.querySelector('.sidebar');
                    sidebar.classList.remove('show');
                }
            });
        });

        // Assistant management
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-assistant-action]')) {
                const action = e.target.getAttribute('data-assistant-action');
                const assistantId = e.target.getAttribute('data-assistant-id');
                this.handleAssistantAction(action, assistantId);
            }
        });

        // Integration settings
        document.querySelectorAll('[data-integration]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const integration = e.target.getAttribute('data-integration');
                if (integration === 'telegram') {
                    window.location.href = '/bot-auth';
                } else {
                    this.openIntegrationSettings(integration);
                }
            });
        });

        // File upload
        document.getElementById('fileInput')?.addEventListener('change', (e) => {
            this.handleFileUpload(e.target.files);
        });

        // Forms
        this.setupForms();

        // Chat functionality
        this.setupChatInterface();

        // Search and filters
        this.setupSearchAndFilters();

        // Export functionality
        document.getElementById('exportBtn')?.addEventListener('click', () => {
            this.exportData();
        });
    }

    // Theme management
    initializeTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        this.updateThemeIcon();
    }

    toggleTheme() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', this.theme);
        localStorage.setItem('theme', this.theme);
        this.updateThemeIcon();
    }

    updateThemeIcon() {
        const icon = document.querySelector('.theme-toggle i');
        icon.className = this.theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }

    // Sidebar management
    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        console.log('toggleSidebar called, window width:', window.innerWidth);
        console.log('Sidebar current classes:', sidebar.className);

        // На мобильных устройствах используем show класс вместо collapsed
        if (window.innerWidth <= 768) {
            const isShown = sidebar.classList.contains('show');
            console.log('Mobile mode, sidebar shown:', isShown);
            
            if (isShown) {
                sidebar.classList.remove('show');
                console.log('Hiding sidebar');
            } else {
                sidebar.classList.add('show');
                console.log('Showing sidebar');
            }
        } else {
            // Десктопная версия
            const isCollapsed = sidebar.classList.contains('collapsed');
            console.log('Desktop mode, sidebar collapsed:', isCollapsed);
            
            if (isCollapsed) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            } else {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        }
    }

    // Navigation
    setupNavigation() {
        // Setup navigation click handlers
        const navLinks = document.querySelectorAll('.nav-link[data-section]');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                this.navigateToSection(section);
            });
        });

        // Show welcome section by default only if no initial navigation is set
        if (!window.initialNavigation) {
            this.navigateToSection('welcome');
        }
    }

    navigateToAssistants(integrationType = null) {
        // Navigate to assistants section
        this.navigateToSection('assistants');

        // If integration type is specified, open modal with pre-selected type
        if (integrationType) {
            setTimeout(() => {
                selectedIntegrationType = integrationType;

                // Open modal
                const modal = new bootstrap.Modal(document.getElementById('addAssistantModal'));
                modal.show();

                // Pre-select integration type and move to step 2
                setTimeout(() => {
                    document.querySelectorAll('.integration-option').forEach(option => {
                        option.classList.remove('selected');
                        if (option.dataset.type === integrationType) {
                            option.classList.add('selected');
                        }
                    });

                    // Auto-advance to step 2
                    showStep2();
                }, 100);
            }, 100);
        }
    }

    navigateToSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });

        // Show target section
        const targetSection = document.getElementById(sectionName);
        if (targetSection) {
            targetSection.classList.add('active');
        }

        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        document.querySelectorAll(`[data-section="${sectionName}"]`).forEach(link => {
            link.classList.add('active');
        });

        this.currentSection = sectionName;

        // Load section-specific data
        this.loadSectionData(sectionName);
    }

    loadSectionData(sectionName) {
        switch (sectionName) {
            case 'messages':
                this.renderMessages();
                break;
            case 'assistants':
                this.renderAssistants();
                break;
            case 'knowledge':
                this.renderDocuments();
                break;
            case 'statistics':
                this.renderStatistics();
                break;
            case 'account':
                this.loadUserProfile();
                break;
            case 'welcome':
                this.updateDashboardStatistics();
                break;
            case 'integrations':
                this.renderIntegrations();
                break;
        }
    }

    async updateDashboardStatistics() {
        try {
            // Fetch assistants data
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/assistants.php?hash=${hash}`);
            const data = await response.json();

            if (data.success) {
                const assistants = data.assistants;
                const activeAssistants = assistants.filter(a => a.status === 'active').length;
                const totalAssistants = assistants.length;

                // Update active bots count
                const activeBotElement = document.querySelector('.stats-number');
                if (activeBotElement) {
                    activeBotElement.textContent = activeAssistants;
                }

                // Update status detail
                const statusDetail = document.querySelector('.stats-detail');
                if (statusDetail && totalAssistants > 0) {
                    const integrationTypes = [...new Set(assistants.map(a => a.platform))];
                    const typeNames = integrationTypes.map(type => {
                        const names = {
                            telegram_bot: 'Telegram',
                            telegram_client: 'Telegram',
                            whatsapp: 'WhatsApp',
                            instagram: 'Instagram'
                        };
                        return names[type] || type;
                    });
                    statusDetail.textContent = typeNames.join(', ') || 'Нет активных интеграций';
                }

                console.log(`Dashboard updated: ${activeAssistants}/${totalAssistants} active assistants`);
            }
        } catch (error) {
            console.error('Error updating dashboard statistics:', error);
        }
    }

    async loadUserProfile() {
        try {
            const response = await fetch('/api/user/profile.php?hash=' + document.getElementById('hidden-data-hash').textContent, {
                method: 'GET'
            });
            const data1 = await response.json();
            const data = data1.user;
            if (response.ok) {
                // Update header with user information
                this.updateUserHeader(data);

                // Populate profile form with user data (if elements exist)
                const firstNameEl = document.getElementById('firstName');
                const lastNameEl = document.getElementById('lastName');
                const emailEl = document.getElementById('email');
                const phoneEl = document.getElementById('phone');
                const usernameEl = document.getElementById('username');

                if (firstNameEl) firstNameEl.value = data.first_name || '';
                if (lastNameEl) lastNameEl.value = data.last_name || '';
                if (emailEl) emailEl.value = data.email || '';
                if (phoneEl) phoneEl.value = data.phone || '';
                if (usernameEl) usernameEl.value = data.username || '';
            } else {
                console.error('Error loading user profile:', data);
            }
        } catch (error) {
            console.error('Error fetching user profile:', error);
        }
    }

    updateUserHeader(userData) {
        const userNameElement = document.getElementById('userName');
        const userAvatarElement = document.getElementById('userAvatar');

        if (userNameElement) {
            const displayName = userData.fullName || 
                              (userData.firstName && userData.lastName ? `${userData.firstName} ${userData.lastName}` : '') ||
                              userData.firstName || 
                              userData.username || 
                              'Пользователь';
            userNameElement.textContent = displayName;
        }

        if (userAvatarElement) {
            const avatarName = userData.fullName || userData.firstName || userData.username || 'User';
            const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(avatarName)}&background=007bff&color=fff&size=32`;
            userAvatarElement.src = avatarUrl;
        }

        console.log('Updated user header with:', userData.fullName || userData.firstName || userData.username);
    }

    // Messages section
    renderMessages() {
        const tbody = document.getElementById('messagesTable');
        if (!tbody) return;

        tbody.innerHTML = this.mockData.messages.map(message => `
            <tr data-message-id="${message.id}">
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${message.avatar}" class="rounded-circle me-2" width="32" height="32">
                        <span>${message.client}</span>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${this.getChannelColor(message.channel)}">
                        <i class="${this.getChannelIcon(message.channel)} me-1"></i>
                        ${this.getChannelName(message.channel)}
                    </span>
                </td>
                <td>${message.lastMessage}</td>
                <td>${this.formatTime(message.time)}</td>
                <td>
                    <span class="badge bg-${message.status === 'new' ? 'warning' : 'success'}">
                        ${message.status === 'new' ? 'Новое' : 'Завершено'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="app.openConversation(${message.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    getChannelColor(channel) {
        const colors = {
            telegram: 'info',
            whatsapp: 'success',
            instagram: 'danger',
            email: 'secondary'
        };
        return colors[channel] || 'secondary';
    }

    getChannelIcon(channel) {
        const icons = {
            telegram: 'fab fa-telegram',
            whatsapp: 'fab fa-whatsapp',
            instagram: 'fab fa-instagram',
            email: 'fas fa-envelope'
        };
        return icons[channel] || 'fas fa-comments';
    }

    getChannelName(channel) {
        const names = {
            telegram: 'Telegram',
            whatsapp: 'WhatsApp',
            instagram: 'Instagram',
            email: 'Email'
        };
        return names[channel] || channel;
    }

    openConversation(messageId) {
        const message = this.mockData.messages.find(m => m.id === messageId);
        if (!message) return;

        // Populate conversation modal
        const modal = new bootstrap.Modal(document.getElementById('conversationModal'));
        const messagesContainer = document.getElementById('conversationMessages');

        messagesContainer.innerHTML = `
            <div class="message assistant-message">
                <div class="message-content">
                    Добро пожаловать! Как дела? Чем могу помочь?
                </div>
                <small class="text-muted">Ассистент • ${this.formatTime(new Date(Date.now() - 60 * 60 * 1000).toISOString())}</small>
            </div>
            <div class="message user-message">
                <div class="message-content">
                    ${message.lastMessage}
                </div>
                <small class="text-muted">${message.client} • ${this.formatTime(message.time)}</small>
            </div>
        `;

        modal.show();
    }

    // Assistants section
    async renderAssistants() {
        const container = document.getElementById('assistantsList');
        if (!container) return;

        try {
            let hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/renderAssistant.php?hash=${hash}`);
            const data1 = await response.json();
            const data = data1.assistants;
            if (data.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-robot mb-3" style="font-size: 3rem;"></i>
                            <p>Ассистенты не созданы</p>
                            <button class="btn btn-primary" onclick = "window.location.href='add_assistant.php'">
                                <i class="fas fa-plus me-2"></i>Добавить ассистента
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = data.map(assistant => `
                <div class="col-md-6 col-lg-4">
                    <div class="assistant-card">
                        <div class="assistant-header">
                            <div class="assistant-avatar">
                                <i class="${this.getIntegrationIcon(assistant.integration_type)}"></i>
                            </div>
                            <div class="assistant-info">
                                <h5>${assistant.name}</h5>
                                <p>Тип: ${this.getIntegrationName(assistant.platform)}</p>
                                <p>Язык: ${this.getLanguageName(assistant.language)}</p>
                            </div>
                            <div class="status-toggle">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" ${assistant.status === 'active' ? 'checked' : ''} 
                                           onchange="app.toggleAssistantStatus(${assistant.id})">
                                </div>
                            </div>
                        </div>
                        <div class="assistant-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="app.openAssistantSettings(${assistant.id})">
                                <i class="fas fa-cog me-1"></i>Настройки
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="app.testAssistant(${assistant.id})">
                                <i class="fas fa-play me-1"></i>Тест
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="app.deleteAssistant(${assistant.id})">
                                <i class="fas fa-trash me-1"></i>Удалить
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

        } catch (error) {
            console.error('Error loading assistants:', error);
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Ошибка загрузки ассистентов: ${error.message}
                    </div>
                </div>
            `;
        }
    }

    getLanguageName(code) {
        const languages = {
            ru: 'Русский',
            en: 'Английский',
            es: 'Испанский',
            de: 'Немецкий',
            fr: 'Французский'
        };
        return languages[code] || code;
    }

    getIntegrationName(type) {
        const types = {
            telegram_bot: 'Telegram Bot',
            telegram_client: 'Telegram Client',
            whatsapp: 'WhatsApp',
            instagram: 'Instagram'
        };
        return types[type] || type;
    }

    getIntegrationIcon(type) {
        const icons = {
            telegram_bot: 'fab fa-telegram',
            telegram_client: 'fab fa-telegram',
            whatsapp: 'fab fa-whatsapp',
            instagram: 'fab fa-instagram'
        };
        return icons[type] || 'fas fa-robot';
    }

    getToneName(tone) {
        const tones = {
            friendly: 'Дружелюбный',
            formal: 'Формальный',
            expert: 'Экспертный',
            professional: 'Профессиональный'
        };
        return tones[tone] || tone;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    async toggleAssistantStatus(assistantId) {
        try {
            let hash = document.getElementById('hidden-data-hash').textContent;
            //alert(assistantId);
            //alert(`/api/toggleAssistant.php?aid=${assistantId}&hash=${hash}`);
            const response = await fetch(`/api/toggleAssistant.php?aid=${assistantId}&hash=${hash}`, {
                method: 'GET'
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to toggle assistant status');
            }

            // Refresh the assistants list and update dashboard stats
            this.renderAssistants();
            this.updateDashboardStatistics();
        } catch (error) {
            console.error('Error toggling assistant status:', error);
            alert('Ошибка изменения статуса ассистента: ' + error.message);
            // Refresh to restore correct state
            this.renderAssistants();
        }
    }

    openAssistantSettings(assistantId) {
        const assistant = this.mockData.assistants.find(a => a.id === assistantId);
        if (!assistant) return;

        // Populate settings modal
        document.getElementById('assistantStatus').checked = assistant.status;
        document.getElementById('settingsTone').value = assistant.tone;
        document.getElementById('settingsPrompt').value = assistant.prompt;

        const modal = new bootstrap.Modal(document.getElementById('assistantSettingsModal'));
        modal.show();
    }

    testAssistant(assistantId) {
        const assistant = this.mockData.assistants.find(a => a.id === assistantId);
        if (!assistant) return;

        // Open chat test modal
        const modal = new bootstrap.Modal(document.getElementById('chatTestModal'));
        const messagesContainer = document.getElementById('chatMessages');

        messagesContainer.innerHTML = `
            <div class="message assistant-message">
                <div class="message-content">
                    Привет! Я ${assistant.name}. Как дела? Чем могу помочь?
                </div>
                <small class="text-muted">${assistant.name} • сейчас</small>
            </div>
        `;

        modal.show();
    }

    async deleteAssistant(assistantId) {
        if (!confirm('Вы уверены, что хотите удалить этого ассистента?')) {
            return;
        }
        const hash = document.getElementById('hidden-data-hash').textContent;
        try {
            const response = await fetch(`/api/delete_assistants.php?hash=${hash}&aid=${assistantId}`);

            const data = await response.json();

            if (data.success) {
                // Refresh assistants list and update dashboard stats
                this.renderAssistants();
                this.updateDashboardStatistics();
                alert('Ассистент успешно удален');
                console.log(`Assistant ${assistantId} deleted successfully`);
                window.location.href = "/index.php?nav=assistants";
            } else {
                alert('Ошибка при удалении ассистента: ' + (data.error || 'Неизвестная ошибка'));
            }
        } catch (error) {
            console.error('Error deleting assistant:', error);
            alert('Ошибка при удалении ассистента: ' + error.message);
        }
    }

    // Documents section
    renderDocuments() {
        const container = document.getElementById('documentsList');
        if (!container) return;

        if (this.mockData.documents.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-file-alt mb-3" style="font-size: 3rem;"></i>
                    <p>Документы не загружены</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.mockData.documents.map(doc => `
            <div class="document-item">
                <div class="document-icon ${doc.type}">
                    <i class="${this.getFileIcon(doc.type)}"></i>
                </div>
                <div class="document-info">
                    <h6>${doc.name}</h6>
                    <small class="text-muted">${doc.size} • ${this.formatTime(doc.uploaded)}</small>
                    ${doc.processing ? `
                        <div class="processing-bar">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     style="width: 65%"></div>
                            </div>
                            <small class="text-muted">Обработка...</small>
                        </div>
                    ` : ''}
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="app.deleteDocument(${doc.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `).join('');
    }

    getFileIcon(type) {
        const icons = {
            pdf: 'fas fa-file-pdf',
            docx: 'fas fa-file-word',
            txt: 'fas fa-file-alt'
        };
        return icons[type] || 'fas fa-file';
    }

    deleteDocument(docId) {
        if (confirm('Удалить документ?')) {
            this.mockData.documents = this.mockData.documents.filter(d => d.id !== docId);
            this.saveData();
            this.renderDocuments();
        }
    }

    // Statistics section
    renderStatistics() {
        if (this.charts.statisticsChart) {
            this.charts.statisticsChart.destroy();
        }
        this.initializeStatisticsChart();
    }

    // File upload functionality
    setupFileUpload() {
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');

        if (!uploadZone || !fileInput) return;

        uploadZone.addEventListener('click', () => {
            fileInput.click();
        });

        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('drag-over');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('drag-over');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            this.handleFileUpload(e.dataTransfer.files);
        });
    }

    handleFileUpload(files) {
        const assistantSelect = document.getElementById('assistantSelect');
        if (!assistantSelect.value) {
            alert('Выберите ассистента для загрузки документов');
            return;
        }

        Array.from(files).forEach(file => {
            if (this.isValidFileType(file)) {
                const doc = {
                    id: Date.now() + Math.random(),
                    name: file.name,
                    type: this.getFileExtension(file.name),
                    size: this.formatFileSize(file.size),
                    uploaded: new Date().toISOString(),
                    assistant: assistantSelect.value,
                    processing: true
                };

                this.mockData.documents.push(doc);
                this.saveData();
                this.renderDocuments();

                // Simulate processing
                setTimeout(() => {
                    doc.processing = false;
                    this.saveData();
                    this.renderDocuments();
                }, 3000);
            } else {
                alert(`Файл ${file.name} имеет неподдерживаемый формат`);
            }
        });
    }

    isValidFileType(file) {
        const validTypes = ['.pdf', '.docx', '.txt'];
        return validTypes.some(type => file.name.toLowerCase().endsWith(type));
    }

    getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Chart initialization
    initializeCharts() {
        this.initializeChannelChart();
        this.initializeStatisticsChart();
    }

    initializeChannelChart() {
        const ctx = document.getElementById('channelChart');
        if (!ctx) return;

        this.charts.channelChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Telegram', 'WhatsApp', 'Instagram', 'Email', 'Сайт'],
                datasets: [{
                    label: 'Сообщения',
                    data: [
                        this.mockData.statistics.channels.telegram,
                        this.mockData.statistics.channels.whatsapp,
                        this.mockData.statistics.channels.instagram,
                        this.mockData.statistics.channels.email,
                        this.mockData.statistics.channels.website
                    ],
                    backgroundColor: [
                        '#0088cc',
                        '#25d366',
                        '#e4405f',
                        '#6c757d',
                        '#007bff'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    initializeStatisticsChart() {
        const ctx = document.getElementById('statisticsChart');
        if (!ctx) return;

        this.charts.statisticsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                datasets: [{
                    label: 'Диалоги',
                    data: [23, 34, 28, 45, 52, 38, 41],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Пользователи',
                    data: [12, 19, 15, 25, 29, 20, 23],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Modal functionality
    setupModals() {
        // Add assistant modal
        window.saveAssistant = () => {
            const name = document.getElementById('assistantName').value;
            const tone = document.getElementById('assistantTone').value;
            const language = document.getElementById('assistantLanguage').value;
            const prompt = document.getElementById('assistantPrompt').value;

            if (!name || !tone || !language) {
                alert('Заполните все обязательные поля');
                return;
            }

            const assistant = {
                id: Date.now(),
                name,
                tone,
                language,
                prompt,
                status: true,
                avatar: this.getRandomAvatar(),
created: new Date().toISOString()
            };

            this.mockData.assistants.push(assistant);
            this.saveData();
            this.renderAssistants();

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addAssistantModal'));
            modal.hide();

            // Reset form
            document.getElementById('addAssistantForm').reset();
        };

        // Set prompt presets
        window.setPromptPreset = (type) => {
            const prompts = {
                support: 'Вы дружелюбный помощник службы поддержки. Отвечайте вежливо и помогайте решать проблемы пользователей.',
                sales: 'Вы эксперт по продажам. Помогайте клиентам выбрать подходящий продукт и отвечайте на вопросы о ценах.',
                consultant: 'Вы профессиональный консультант. Предоставляйте точную информацию и экспертные советы.'
            };
            document.getElementById('assistantPrompt').value = prompts[type] || '';
        };
    }

    getRandomAvatar() {
        const avatars = ['🤖', '👨‍💼', '👩‍💼', '🎧', '💼', '📞', '💬', '🎯'];
        return avatars[Math.floor(Math.random() * avatars.length)];
    }

    // Integration settings
    openIntegrationSettings(integration) {
        const modal = new bootstrap.Modal(document.getElementById('integrationModal'));
        const settingsContainer = document.getElementById('integrationSettings');

        const integrationData = this.mockData.integrations[integration];

        settingsContainer.innerHTML = `
            <div class="mb-3">
                <label for="integrationToken" class="form-label">API Token</label>
                <input type="password" class="form-control" id="integrationToken" 
                       value="${integrationData.token}" placeholder="Введите API токен">
            </div>
            <div class="mb-3">
                <label for="webhookUrl" class="form-label">Webhook URL</label>
                <input type="url" class="form-control" id="webhookUrl" 
                       value="https://api.dashboard.example.com/webhook/${integration}" readonly>
                <div class="form-text">Скопируйте этот URL в настройки интеграции</div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="autoRespond" 
                       ${integrationData.connected ? 'checked' : ''}>
                <label class="form-check-label" for="autoRespond">
                    Автоматические ответы
                </label>
            </div>
        `;

        modal.show();
    }

    // Forms setup
    setupForms() {
        // Profile form
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                alert('Профиль обновлен!');
            });
        }

        // Password form
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (newPassword !== confirmPassword) {
                    alert('Пароли не совпадают');
                    return;
                }

                alert('Пароль изменен!');
                passwordForm.reset();
            });
        }
    }

    // Chat interface
    setupChatInterface() {
        // Chat test
        const sendChatBtn = document.getElementById('sendChatBtn');
        const chatInput = document.getElementById('chatInput');

        if (sendChatBtn && chatInput) {
            const sendMessage = () => {
                const message = chatInput.value.trim();
                if (!message) return;

                const messagesContainer = document.getElementById('chatMessages');

                // Add user message
                messagesContainer.innerHTML += `
                    <div class="message user-message">
                        <div class="message-content">${message}</div>
                        <small class="text-muted">Вы • сейчас</small>
                    </div>
                `;

                chatInput.value = '';

                // Simulate assistant response
                setTimeout(() => {
                    const responses = [
                        'Понятно, давайте разберем этот вопрос.',
                        'Хороший вопрос! Вот что я могу предложить...',
                        'Да, это действительно важная тема.',
                        'Позвольте мне помочь вам с этим.',
                        'Отличная идея! Рассмотрим варианты.'
                    ];
                    const response = responses[Math.floor(Math.random() * responses.length)];

                    messagesContainer.innerHTML += `
                        <div class="message assistant-message">
                            <div class="message-content">${response}</div>
                            <small class="text-muted">Ассистент • сейчас</small>
                        </div>
                    `;

                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }, 1000);

                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            };

            sendChatBtn.addEventListener('click', sendMessage);
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }

        // Conversation chat
        const sendConversationBtn = document.getElementById('sendConversationBtn');
        const conversationInput = document.getElementById('conversationInput');

        if (sendConversationBtn && conversationInput) {
            const sendReply = () => {
                const message = conversationInput.value.trim();
                if (!message) return;

                const messagesContainer = document.getElementById('conversationMessages');

                messagesContainer.innerHTML += `
                    <div class="message user-message">
                        <div class="message-content">${message}</div>
                        <small class="text-muted">Вы • сейчас</small>
                    </div>
                `;

                conversationInput.value = '';
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            };

            sendConversationBtn.addEventListener('click', sendReply);
            conversationInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendReply();
                }
            });
        }
    }

    // Search and filters
    setupSearchAndFilters() {
        // Message search
        const messageSearch = document.getElementById('messageSearch');
        if (messageSearch) {
            messageSearch.addEventListener('input', (e) => {
                this.filterMessages(e.target.value);
            });
        }

        // Message filters
        ['dateFilter', 'channelFilter', 'statusFilter'].forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', () => {
                    this.applyMessageFilters();
                });
            }
        });

        // Statistics filter
        const statsDateFilter = document.getElementById('statsDateFilter');
        if (statsDateFilter) {
            statsDateFilter.addEventListener('change', (e) => {
                this.updateStatisticsPeriod(e.target.value);
            });
        }
    }

    filterMessages(searchTerm) {
        const rows = document.querySelectorAll('#messagesTable tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    }

    applyMessageFilters() {
        const dateFilter = document.getElementById('dateFilter').value;
        const channelFilter = document.getElementById('channelFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;

        let filtered = [...this.mockData.messages];

        if (dateFilter) {
            const now = new Date();
            filtered = filtered.filter(message => {
                const messageDate = new Date(message.time);
                switch (dateFilter) {
                    case 'today':
                        return messageDate.toDateString() === now.toDateString();
                    case 'week':
                        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                        return messageDate >= weekAgo;
                    case 'month':
                        const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                        return messageDate >= monthAgo;
                    default:
                        return true;
                }
            });
        }

        if (channelFilter) {
            filtered = filtered.filter(message => message.channel === channelFilter);
        }

        if (statusFilter) {
            filtered = filtered.filter(message => message.status === statusFilter);
        }

        // Re-render filtered messages
        const tbody = document.getElementById('messagesTable');
        if (tbody) {
            tbody.innerHTML = filtered.map(message => `
                <tr data-message-id="${message.id}">
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${message.avatar}" class="rounded-circle me-2" width="32" height="32">
                            <span>${message.client}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${this.getChannelColor(message.channel)}">
                            <i class="${this.getChannelIcon(message.channel)} me-1"></i>
                            ${this.getChannelName(message.channel)}
                        </span>
                    </td>
                    <td>${message.lastMessage}</td>
                    <td>${this.formatTime(message.time)}</td>
                    <td>
                        <span class="badge bg-${message.status === 'new' ? 'warning' : 'success'}">
                            ${message.status === 'new' ? 'Новое' : 'Завершено'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="app.openConversation(${message.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    }

    updateStatisticsPeriod(period) {
        // Update statistics based on selected period
        // This would normally fetch new data from the server
        console.log(`Updating statistics for period: ${period}`);
    }

    // Data export
    exportData() {
        const data = {
            messages: this.mockData.messages,
            assistants: this.mockData.assistants,
            statistics: this.mockData.statistics,
            exportDate: new Date().toISOString()
        };

        const csvContent = this.convertToCSV(this.mockData.messages);
        this.downloadCSV(csvContent, 'dashboard-export.csv');
    }

    convertToCSV(data) {
        const headers = ['Client', 'Channel', 'Last Message', 'Time', 'Status'];
        const csvRows = [headers.join(',')];

        data.forEach(row => {
            const values = [
                row.client,
                row.channel,
                `"${row.lastMessage}"`,
                row.time,
                row.status
            ];
            csvRows.push(values.join(','));
        });

        return csvRows.join('\n');
    }

    downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');

        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    // Utility functions
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) { // Less than 1 minute
            return 'сейчас';
        } else if (diff < 3600000) { // Less than 1 hour
            const minutes = Math.floor(diff / 60000);
            return `${minutes} мин`;
        } else if (diff < 86400000) { // Less than 1 day
            const hours = Math.floor(diff / 3600000);
            return `${hours} час`;
        } else {
            return date.toLocaleDateString('ru-RU');
        }
    }

    setCurrentDate() {
        const dateElement = document.getElementById('current-date');
        if (dateElement) {
            const today = new Date();
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                weekday: 'long'
            };
            dateElement.textContent = today.toLocaleDateString('ru-RU', options);
        }
    }

    // Integrations section
    async renderIntegrations() {
        const container = document.getElementById('integrationsList');
        if (!container) return;

        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/integrations.php?hash=${hash}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error);
            }

            const integrations = data.integrations;

            if (integrations.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-plug fa-3x mb-3"></i>
                        <h5>Нет интеграций</h5>
                        <p>Добавьте свою первую интеграцию</p>
                        <button class="btn btn-primary" onclick="app.showAddIntegrationModal()">
                            <i class="fas fa-plus me-2"></i>Добавить интеграцию
                        </button>
                    </div>`;
                return;
            }

            container.innerHTML = integrations.map(integration => `
                <div class="integration-item">
                    <div class="integration-info">
                        <div class="integration-icon me-3">
                            <i class="${this.getIntegrationIcon(integration.platform)}" style="font-size: 2rem; color: ${this.getIntegrationColor(integration.platform)};"></i>
                        </div>
                        <div class="integration-details">
                            <h5 class="mb-1">${integration.name}</h5>
                            <p class="text-muted mb-1">${this.getPlatformDescription(integration.platform)}</p>
                            <div class="status-badge ${integration.status === 'active' ? 'connected' : 'disconnected'}">
                                <i class="fas fa-${integration.status === 'active' ? 'check-circle' : 'times-circle'} me-1"></i>
                                ${integration.status === 'active' ? 'Подключено' : 'Отключено'}
                            </div>
                        </div>
                    </div>
                    <div class="integration-actions">
                        <button class="btn btn-outline-primary btn-sm me-2" onclick="app.navigateToAssistants('${integration.platform}')">
                            <i class="fas fa-plus me-1"></i>Добавить ассистента
                        </button>
                        <button class="btn btn-outline-success btn-sm me-2" onclick="app.toggleIntegrationStatus(${integration.id})">
                            <i class="fas fa-power-off me-1"></i>
                            ${integration.status === 'active' ? 'Отключить' : 'Включить'}
                        </button>
                        <button class="btn btn-outline-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#instructionModal">
                            <i class="fas fa-info-circle me-1"></i>Инструкция
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="app.deleteIntegration(${integration.id})">
                            <i class="fas fa-trash me-1"></i>Удалить
                        </button>
                    </div>
                </div>
            `).join('');

        } catch (error) {
            console.error('Error loading integrations:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ошибка загрузки интеграций: ${error.message}
                </div>`;
        }
    }

    async toggleIntegrationStatus(integrationId) {
        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const body_data = JSON.stringify({
                'hash': hash,
                'id': integrationId
            });

            const response = await fetch('/api/toggleIntegration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: body_data
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Статус интеграции изменен', 'success');
                this.renderIntegrations(); // Reload integrations
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error toggling integration:', error);
            this.showNotification('Ошибка изменения статуса: ' + error.message, 'error');
        }
    }

    async deleteIntegration(integrationId) {
        if (!confirm('Вы уверены, что хотите удалить эту интеграцию?')) {
            return;
        }

        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/integrations.php?hash=${hash}&id=${integrationId}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Интеграция удалена', 'success');
                this.renderIntegrations(); // Reload integrations
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error deleting integration:', error);
            this.showNotification('Ошибка удаления: ' + error.message, 'error');
        }
    }

    showAddIntegrationModal() {
        const modal = new bootstrap.Modal(document.getElementById('addIntegrationModal'));
        modal.show();
    }

    getIntegrationIcon(platform) {
        const icons = {
            'telegram_bot': 'fab fa-telegram',
            'telegram_client': 'fab fa-telegram',
            'whatsapp': 'fab fa-whatsapp',
            'instagram': 'fab fa-instagram'
        };
        return icons[platform] || 'fas fa-plug';
    }

    getPlatformName(platform) {
        const names = {
            'telegram_bot': 'Telegram Bot',
            'telegram_client': 'Telegram Client',
            'whatsapp': 'WhatsApp',
            'instagram': 'Instagram'
        };
        return names[platform] || platform;
    }

    getPlatformDescription(platform) {
        const descriptions = {
            'telegram_bot': 'Telegram Bot API для автоматических ответов',
            'telegram_client': 'Telegram Client API интеграция',
            'whatsapp': 'WhatsApp Business API интеграция',
            'instagram': 'Instagram API для автоматизации Direct'
        };
        return descriptions[platform] || 'Интеграция с внешним сервисом';
    }

    getIntegrationColor(platform) {
        const colors = {
            'telegram_bot': '#29ABE2',
            'telegram_client': '#29ABE2',
            'whatsapp': '#25D366',
            'instagram': '#E4405F'
        };
        return colors[platform] || '#6c757d';
    }

    editIntegration(integrationId) {
        // Placeholder for integration editing
        alert(`Редактирование интеграции ${integrationId} пока не поддерживается`);
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notification-container');
        if (!container) {
            console.error('Notification container not found, showing alert instead');
            alert(message);
            return;
        }

        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    // Knowledge Base Management
    async setupKnowledgeBase() {
        await this.loadAssistantsForKnowledge();
        this.setupAssistantSelection();
        this.setupKnowledgeUpload();
        this.setupKnowledgeSearch();
    }

    async loadAssistantsForKnowledge() {
        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/assistants.php?hash=${hash}`);
            const data = await response.json();

            const select = document.getElementById('knowledgeAssistantSelect');
            if (!select) return;

            // Clear existing options except first
            select.innerHTML = '<option value="">Выберите ассистента для управления базой знаний...</option>';

            if (data.success && data.assistants.length > 0) {
                data.assistants.forEach(assistant => {
                    const option = document.createElement('option');
                    option.value = assistant.id;
                    option.textContent = `${assistant.name} (${this.getIntegrationName(assistant.platform)})`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading assistants for knowledge base:', error);
        }
    }

    setupAssistantSelection() {
        const select = document.getElementById('knowledgeAssistantSelect');
        if (!select) return;

        select.addEventListener('change', async (e) => {
            const assistantId = e.target.value;
            
            if (assistantId) {
                // Show knowledge base actions and search
                document.getElementById('knowledgeActions').style.display = 'block';
                document.getElementById('knowledgeSearchContainer').style.display = 'block';
                
                // Update header with assistant name
                const selectedOption = e.target.selectedOptions[0];
                const assistantName = selectedOption.textContent;
                document.getElementById('selectedAssistantName').textContent = `(${assistantName})`;
                
                // Hide "no assistant" message
                const noAssistantEl = document.getElementById('knowledgeNoAssistant');
                if (noAssistantEl) {
                    noAssistantEl.style.display = 'none';
                }
                
                // Load files for selected assistant
                await this.loadKnowledgeFiles(assistantId);
            } else {
                // Hide knowledge base actions and search
                document.getElementById('knowledgeActions').style.display = 'none';
                document.getElementById('knowledgeSearchContainer').style.display = 'none';
                
                // Clear assistant name
                document.getElementById('selectedAssistantName').textContent = '';
                
                // Show "no assistant" message
                const noAssistantEl = document.getElementById('knowledgeNoAssistant');
                if (noAssistantEl) {
                    noAssistantEl.style.display = 'block';
                }
                
                // Clear files list
                const container = document.getElementById('knowledgeFilesList');
                if (container) {
                    container.innerHTML = '<div id="knowledgeNoAssistant" class="text-center py-5"><i class="fas fa-robot fa-3x text-muted mb-3"></i><h5 class="text-muted">Выберите ассистента</h5><p class="text-muted">Для управления базой знаний сначала выберите ассистента из списка выше</p></div>';
                }
            }
        });
    }

    async loadKnowledgeFiles(assistantId = null) {
        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            let url = `/api/knowledgeBase.php?hash=${hash}`;
            
            if (assistantId) {
                url += `&assistant_id=${assistantId}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.renderKnowledgeFiles(data.files);
            } else {
                console.error('Error loading knowledge files:', data.error);
            }
        } catch (error) {
            console.error('Error loading knowledge files:', error);
        }
    }

    renderKnowledgeFiles(files) {
        const container = document.getElementById('knowledgeFilesList');
        if (!container) return;

        if (files.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">База знаний пуста</h5>
                    <p class="text-muted">Загрузите файлы или создайте документы</p>
                </div>
            `;
            return;
        }

        container.innerHTML = files.map(file => `
            <div class="knowledge-file-item card mb-2" data-file-id="${file.id}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="fas ${this.getFileIcon(file.type)} me-2"></i>
                                ${file.name}
                            </h6>
                            <small class="text-muted">
                                ${file.type.toUpperCase()} • 
                                ${file.size ? this.formatFileSize(file.size) + ' • ' : ''}
                                ${new Date(file.created_at).toLocaleDateString('ru-RU')}
                            </small>
                            ${file.content ? `<p class="mb-0 mt-2 text-truncate" style="max-height: 2.4em; overflow: hidden;">${file.content.substring(0, 100)}...</p>` : ''}
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="app.viewKnowledgeFile(${file.id})"><i class="fas fa-eye me-2"></i>Просмотреть</a></li>
                                <li><a class="dropdown-item" href="#" onclick="app.editKnowledgeFile(${file.id})"><i class="fas fa-edit me-2"></i>Редактировать</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="app.deleteKnowledgeFile(${file.id})"><i class="fas fa-trash me-2"></i>Удалить</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    getFileIcon(type) {
        const icons = {
            'txt': 'fa-file-alt',
            'md': 'fa-file-alt',
            'pdf': 'fa-file-pdf',
            'doc': 'fa-file-word',
            'docx': 'fa-file-word',
            'json': 'fa-file-code',
            'csv': 'fa-file-csv',
            'text': 'fa-file-alt'
        };
        return icons[type] || 'fa-file';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    setupKnowledgeUpload() {
        const fileInput = document.getElementById('knowledgeFileInput');
        const uploadArea = document.getElementById('knowledgeUploadArea');

        if (uploadArea) {
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('drag-over');
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('drag-over');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('drag-over');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.validateAndUploadFile(files[0]);
                }
            });

            uploadArea.addEventListener('click', () => {
                fileInput?.click();
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.validateAndUploadFile(e.target.files[0]);
                }
            });
        }
    }

    validateAndUploadFile(file) {
        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            this.showNotification('Файл слишком большой. Максимальный размер: 10MB', 'error');
            return;
        }

        // Validate file type
        const allowedTypes = ['txt', 'md', 'pdf', 'doc', 'docx', 'json', 'csv'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExtension)) {
            this.showNotification('Неподдерживаемый тип файла. Разрешены: ' + allowedTypes.join(', '), 'error');
            return;
        }

        this.uploadKnowledgeFile(file);
    }

    async uploadKnowledgeFile(file) {
        try {
            const assistantSelect = document.getElementById('knowledgeAssistantSelect');
            const assistantId = assistantSelect.value;
            
            if (!assistantId) {
                this.showNotification('Выберите ассистента для загрузки файла', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('hash', document.getElementById('hidden-data-hash').textContent);
            formData.append('assistant_id', assistantId);
            
            // Show upload progress
            this.showNotification('Загружается файл...', 'info');
            
            const response = await fetch('/api/knowledgeBase.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log('Upload response:', data);

            if (data.success) {
                this.showNotification('Файл успешно загружен!', 'success');
                await this.loadKnowledgeFiles(assistantId);
                // Clear file input
                const fileInput = document.getElementById('knowledgeFileInput');
                if (fileInput) fileInput.value = '';
            } else {
                this.showNotification(data.error || 'Ошибка загрузки файла', 'error');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Ошибка загрузки файла: ' + error.message, 'error');
        }
    }

    setupKnowledgeSearch() {
        const searchInput = document.getElementById('knowledgeSearchInput');
        let searchTimeout;

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();

                if (query.length === 0) {
                    this.loadKnowledgeFiles();
                    return;
                }

                if (query.length < 2) return;

                searchTimeout = setTimeout(() => {
                    this.searchKnowledge(query);
                }, 500);
            });
        }
    }

    async searchKnowledge(query) {
        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/searchKnowledge.php?q=${encodeURIComponent(query)}&hash=${hash}`);
            const data = await response.json();

            if (data.success) {
                this.renderKnowledgeSearchResults(data.results, query);
            } else {
                console.error('Search error:', data.error);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    renderKnowledgeSearchResults(results, query) {
        const container = document.getElementById('knowledgeFilesList');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Ничего не найдено</h5>
                    <p class="text-muted">По запросу "${query}" результатов не найдено</p>
                    <button class="btn btn-outline-primary" onclick="document.getElementById('knowledgeSearchInput').value = ''; app.loadKnowledgeFiles();">
                        Показать все файлы
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="mb-3">
                <small class="text-muted">Найдено ${results.length} результатов по запросу "${query}"</small>
                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="document.getElementById('knowledgeSearchInput').value = ''; app.loadKnowledgeFiles();">
                    <i class="fas fa-times me-1"></i>Очистить
                </button>
            </div>
            ${results.map(result => `
                <div class="knowledge-file-item card mb-2" data-file-id="${result.id}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas ${this.getFileIcon(result.type)} me-2"></i>
                                    ${result.name}
                                </h6>
                                <small class="text-muted">
                                    ${result.type.toUpperCase()} • 
                                    ${new Date(result.created_at).toLocaleDateString('ru-RU')}
                                </small>
                                ${result.content_preview ? `<p class="mb-0 mt-2">${result.content_preview}</p>` : ''}
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="app.viewKnowledgeFile(${result.id})"><i class="fas fa-eye me-2"></i>Просмотреть</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="app.editKnowledgeFile(${result.id})"><i class="fas fa-edit me-2"></i>Редактировать</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="app.deleteKnowledgeFile(${result.id})"><i class="fas fa-trash me-2"></i>Удалить</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('')}
        `;
    }

    async viewKnowledgeFile(fileId) {
        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/getKnowledgeFile.php?id=${fileId}&hash=${hash}`);
            const data = await response.json();

            if (data.success) {
                this.showKnowledgeFileModal(data.file, 'view');
            } else {
                this.showNotification(data.error || 'Ошибка загрузки файла', 'error');
            }
        } catch (error) {
            console.error('Error viewing file:', error);
            this.showNotification('Ошибка загрузки файла', 'error');
        }
    }

    async editKnowledgeFile(fileId) {
        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch(`/api/getKnowledgeFile.php?id=${fileId}&hash=${hash}`);
            const data = await response.json();

            if (data.success) {
                this.showKnowledgeFileModal(data.file, 'edit');
            } else {
                this.showNotification(data.error || 'Ошибка загрузки файла', 'error');
            }
        } catch (error) {
            console.error('Error loading file for edit:', error);
            this.showNotification('Ошибка загрузки файла', 'error');
        }
    }

    showKnowledgeFileModal(file, mode = 'view') {
        const modal = document.getElementById('knowledgeFileModal');
        const title = document.getElementById('knowledgeFileModalTitle');
        const content = document.getElementById('knowledgeFileContent');
        const saveBtn = document.getElementById('saveKnowledgeFileBtn');

        if (!modal) return;

        title.textContent = mode === 'edit' ? 'Редактировать файл' : 'Просмотр файла';

        if (mode === 'edit') {
            content.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input type="text" class="form-control" id="editFileName" value="${file.name}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Содержимое</label>
                    <textarea class="form-control" id="editFileContent" rows="15">${file.content || ''}</textarea>
                </div>
            `;
            saveBtn.style.display = 'block';
            saveBtn.onclick = () => this.saveKnowledgeFile(file.id);
        } else {
            content.innerHTML = `
                <div class="mb-3">
                    <h6><i class="fas ${this.getFileIcon(file.type)} me-2"></i>${file.name}</h6>
                    <small class="text-muted">
                        ${file.type.toUpperCase()}
                        ${file.size ? ' • ' + this.formatFileSize(file.size) : ''}
                        • ${new Date(file.created_at).toLocaleDateString('ru-RU')}
                    </small>
                </div>
                <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                    <pre style="white-space: pre-wrap; font-family: inherit;">${file.content || 'Содержимое недоступно'}</pre>
                </div>
            `;
            saveBtn.style.display = 'none';
        }

        new bootstrap.Modal(modal).show();
    }

    async saveKnowledgeFile(fileId) {
        const name = document.getElementById('editFileName').value.trim();
        const content = document.getElementById('editFileContent').value;

        if (!name) {
            this.showNotification('Название файла обязательно', 'error');
            return;
        }

        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch('/api/knowledgeBase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    hash: hash,
                    file_id: fileId,
                    name: name,
                    content: content,
                    action: 'update'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Файл успешно обновлен!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('knowledgeFileModal')).hide();
                // Get current assistant ID
                const assistantSelect = document.getElementById('knowledgeAssistantSelect');
                const assistantId = assistantSelect ? assistantSelect.value : null;
                await this.loadKnowledgeFiles(assistantId);
            } else {
                this.showNotification(data.error || 'Ошибка сохранения файла', 'error');
            }
        } catch (error) {
            console.error('Save error:', error);
            this.showNotification('Ошибка сохранения файла', 'error');
        }
    }

    async deleteKnowledgeFile(fileId) {
        if (!confirm('Вы уверены, что хотите удалить этот файл? Это действие нельзя отменить.')) {
            return;
        }

        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            const response = await fetch('/api/knowledgeBase.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    hash: hash,
                    file_id: fileId
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Файл успешно удален!', 'success');
                // Get current assistant ID
                const assistantSelect = document.getElementById('knowledgeAssistantSelect');
                const assistantId = assistantSelect ? assistantSelect.value : null;
                await this.loadKnowledgeFiles(assistantId);
            } else {
                this.showNotification(data.error || 'Ошибка удаления файла', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Ошибка удаления файла', 'error');
        }
    }

    async createNewDocument() {
        const modal = document.getElementById('createDocumentModal');
        if (modal) {
            new bootstrap.Modal(modal).show();
        }
    }

    async saveNewDocument() {
        const nameField = document.getElementById('newDocumentName');
        const contentField = document.getElementById('newDocumentContent');
        
        if (!nameField || !contentField) {
            this.showNotification('Элементы формы не найдены', 'error');
            return;
        }
        
        const name = nameField.value.trim();
        const content = contentField.value || '';

        if (!name) {
            this.showNotification('Название документа обязательно', 'error');
            nameField.focus();
            return;
        }
        
        const assistantSelect = document.getElementById('knowledgeAssistantSelect');
        const assistantId = assistantSelect.value;
        
        if (!assistantId) {
            this.showNotification('Выберите ассистента для создания документа', 'error');
            return;
        }

        try {
            const hash = document.getElementById('hidden-data-hash').textContent;
            
            // Use FormData for consistent handling
            const formData = new FormData();
            formData.append('hash', hash);
            formData.append('assistant_id', assistantId);
            formData.append('name', name);
            formData.append('content', content);
            formData.append('type', 'text');
            
            // Validate FormData
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            console.log('Sending document data:', {
                name: name,
                content: content.substring(0, 100) + (content.length > 100 ? '...' : ''),
                assistant_id: assistantId,
                nameLength: name.length,
                contentLength: content.length
            });
            
            const response = await fetch('/api/knowledgeBase.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log('Document creation response:', data);

            if (data.success) {
                this.showNotification('Документ успешно создан!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createDocumentModal')).hide();
                document.getElementById('newDocumentName').value = '';
                document.getElementById('newDocumentContent').value = '';
                await this.loadKnowledgeFiles(assistantId);
            } else {
                this.showNotification(data.error || 'Ошибка создания документа', 'error');
            }
        } catch (error) {
            console.error('Create document error:', error);
            this.showNotification('Ошибка создания документа: ' + error.message, 'error');
        }
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing app...');

    try {
        app = new DashboardApp();
        console.log('DashboardApp initialized successfully');

        // Initialize assistant modal
        app.setupAssistantModal();
        console.log('Assistant modal initialized');

        // Initialize add integration modal
        app.setupAddIntegrationModal();
        app.setupKnowledgeBase();
        app.makeGloballyAvailable();
        console.log('Dashboard app loaded successfully');
    } catch (error) {
        console.error('Error during initialization:', error);
    }
});

// Support chat functionality
document.addEventListener('DOMContentLoaded', () => {
    // Initialize chat support button
    const chatSupportBtn = document.getElementById('chatSupportBtn');
    if (chatSupportBtn) {
        chatSupportBtn.addEventListener('click', () => {
            alert('Функция чат-поддержки будет доступна в следующем обновлении');
        });
    }

    // Google Docs integration
    const googleDocsBtn = document.getElementById('googleDocsBtn');
    if (googleDocsBtn) {
        googleDocsBtn.addEventListener('click', () => {
            alert('Интеграция с Google Docs будет доступна в следующем обновлении');
        });
    }
});

// Global variables
let app;
let selectedIntegrationType = null;

// Global function for saving assistant
function saveAssistant() {
    console.log('=== SAVE ASSISTANT FUNCTION STARTED ===');
    console.log('saveAssistant() called');
    console.log('Current selectedIntegrationType:', selectedIntegrationType);
    console.log('Window selectedIntegrationType:', window.selectedIntegrationType);
    console.log('typeof selectedIntegrationType:', typeof selectedIntegrationType);
    console.log('selectedIntegrationType truthy:', !!selectedIntegrationType);

    // Use window variable if local is null
    const integrationType = selectedIntegrationType || window.selectedIntegrationType;
    console.log('Final integration type to use:', integrationType);

    // Check if selectedIntegrationType is set
    if (!integrationType) {
        console.error('No integration type selected');
        alert('Пожалуйста, выберите тип интеграции');
        return;
    }

    // Update selectedIntegrationType for the rest of the function
    selectedIntegrationType = integrationType;

    // Validate assistant form
    const name = document.getElementById('assistantName');
    const tone = document.getElementById('assistantTone');
    const language = document.getElementById('assistantLanguage');
    const prompt = document.getElementById('assistantPrompt');

    console.log('Form elements found:', {
        name: !!name,
        tone: !!tone,
        language: !!language,
        prompt: !!prompt
    });

    if (!name || !tone || !language || !prompt) {
        console.error('Missing form elements');
        alert('Не найдены элементы формы');
        return;
    }

    const nameValue = name.value.trim();
    const toneValue = tone.value;
    const languageValue = language.value;
    const promptValue = prompt.value.trim();

    console.log('Form values:', {
        name: nameValue,
        tone: toneValue,
        language: languageValue,
        prompt: promptValue
    });

    if (!nameValue || !toneValue || !languageValue || !promptValue) {
        console.error('Empty form values detected');
        alert('Пожалуйста, заполните все обязательные поля');
        return;
    }



    // Collect integration data
    console.log('Collecting integration data...');

    const integrationData = {};

    switch (integrationType) {
        case 'telegram_bot':
            const botToken = document.getElementById('botToken');
            if (!botToken || !botToken.value.trim()) {
                alert('Введите Bot Token для Telegram');
                if (botToken) botToken.focus();
                return;
            }
            integrationData.token = botToken.value.trim();
            break;

        case 'telegram_client':
            const apiId = document.getElementById('apiId');
            const apiHash = document.getElementById('apiHash');
            const phoneNumber = document.getElementById('phoneNumber');

            if (!apiId || !apiId.value.trim()) {
                alert('Введите API ID');
                if (apiId) apiId.focus();
                return;
            }
            if (!apiHash || !apiHash.value.trim()) {
                alert('Введите API Hash');
                if (apiHash) apiHash.focus();
                return;
            }
            if (!phoneNumber || !phoneNumber.value.trim()) {
                alert('Введите номер телефона');
                if (phoneNumber) phoneNumber.focus();
                return;
            }

            integrationData.api_id = apiId.value.trim();
            integrationData.api_hash = apiHash.value.trim();
            integrationData.phone = phoneNumber.value.trim();
            break;

        case 'whatsapp':
            const whatsappApiKey = document.getElementById('whatsappApiKey');
            if (!whatsappApiKey || !whatsappApiKey.value.trim()) {
                alert('Введите WhatsApp API Key');
                if (whatsappApiKey) whatsappApiKey.focus();
                return;
            }
            integrationData.api_key = whatsappApiKey.value.trim();
            break;

        case 'instagram':
            const instagramApiKey = document.getElementById('instagramApiKey');
            if (!instagramApiKey || !instagramApiKey.value.trim()) {
                alert('Введите Instagram API Key');
                if (instagramApiKey) instagramApiKey.focus();
                return;
            }
            integrationData.access_token = instagramApiKey.value.trim();
            break;

        default:
            alert('Неизвестный тип интеграции');
            return;
    }

    console.log('Integration data collected:', integrationData);

    if (!integrationData || Object.keys(integrationData).length === 0) {
        console.error('No integration data found');
        alert('Пожалуйста, заполните данные интеграции');
        return;
    }

    // Prepare assistant data
    const assistantData = {
        name: nameValue,
        tone: toneValue,
        language: languageValue,
        prompt: promptValue,
        integration_type: integrationType,
        integration_data: integrationData
    };

    console.log('Prepared assistant data for sending:', assistantData);

    // Show loading state
    const createBtn = document.getElementById('createAssistantBtn');
    const originalText = createBtn.innerHTML;
    createBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Создание...';
    createBtn.disabled = true;

    // Send to server
    console.log('Starting fetch request to /add-assistant...');
    fetch('/add-assistant', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(assistantData)
    })
    .then(response => {
        console.log('Fetch response received:', response.status, response.statusText);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            console.log('Assistant created successfully!');

            // Close modal
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('addAssistantModal'));
            if (modalInstance) {
                modalInstance.hide();
            }

            // Show success message
            alert('Ассистент успешно создан!');

            // Refresh assistants list and update dashboard stats
            if (window.app && window.app.renderAssistants) {
                console.log('Refreshing assistants list...');
                window.app.renderAssistants();
                window.app.updateDashboardStatistics();
            }
        } else {
            console.error('Server returned error:', data.error);
            alert('Ошибка при создании ассистента: ' + (data.error || data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Ошибка при создании ассистента: ' + error.message);
    })
    .finally(() => {
        console.log('Restoring button state...');
        // Restore button state
        createBtn.innerHTML = originalText;
        createBtn.disabled = false;
    });
}

// Ensure functions are available globally
window.saveAssistant = saveAssistant;

// Debug function for create button clicks
function debugCreateButtonClick(e) {
    console.log('DEBUG: Create button clicked!', e);
    console.log('DEBUG: Event target:', e.target);
    console.log('DEBUG: saveAssistant function exists:', typeof window.saveAssistant);

    // Call saveAssistant directly without preventing default
    if (typeof window.saveAssistant === 'function') {
        console.log('DEBUG: Calling saveAssistant...');
        try {
            window.saveAssistant();
        } catch (error) {
            console.error('DEBUG: Error calling saveAssistant:', error);
        }
    } else {
        console.error('DEBUG: saveAssistant function not found!');
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM loaded, initializing app...');

    try {
        app = new DashboardApp();
        console.log('DashboardApp initialized successfully');

        // Initialize assistant modal
        initializeAssistantModal();
        console.log('Assistant modal initialized');

        // Initialize add integration modal
        initializeAddIntegrationModal();
        console.log('Add integration modal initialized');

        // Make functions globally available for onclick handlers
        window.app = app;

        // Make functions globally available for onclick handlers
        window.saveAssistant = saveAssistant;
        window.nextAssistantStep = nextAssistantStep;
        window.prevAssistantStep = prevAssistantStep;
        window.setPromptPreset = setPromptPreset;

        console.log('All functions made globally available');

        // Test if functions are accessible
        console.log('Testing global functions:');
        console.log('window.saveAssistant:', typeof window.saveAssistant);
        console.log('window.nextAssistantStep:', typeof window.nextAssistantStep);
        console.log('window.prevAssistantStep:', typeof window.prevAssistantStep);
        console.log('window.setPromptPreset:', typeof window.setPromptPreset);

        // Add a test function for debugging
        window.testGlobalFunctions = function() {
            console.log('Test function called - global functions are working!');
            console.log('Current selected integration type:', selectedIntegrationType);
            console.log('Current step:', currentAssistantStep);
        };
    } catch (error) {
        console.error('Error during initialization:', error);
    }
});

// Add Integration Modal Management
function initializeAddIntegrationModal() {
    console.log('Initializing add integration modal...');
    let selectedIntegrationType = null;

    // Handle integration type selection
    const integrationTypeCards = document.querySelectorAll('.integration-type-card');
    console.log('Found integration type cards:', integrationTypeCards.length);

    integrationTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.type;
            console.log('Integration type card clicked:', type);

            // Remove selection from all cards
            document.querySelectorAll('.integration-type-card').forEach(c => {
                c.classList.remove('selected');
            });

            // Add selection to clicked card
            this.classList.add('selected');
            selectedIntegrationType = type;

            // Hide all config sections
            document.querySelectorAll('.integration-config').forEach(config => {
                config.classList.add('d-none');
            });

            // Show specific config section
            const configSection = document.getElementById(`${type}-config`);
            if (configSection) {
                configSection.classList.remove('d-none');
                console.log('Showing config section for:', type);
            } else {
                console.error('Config section not found for:', type);
            }

            // Show configuration form
            const configForm = document.getElementById('integrationConfigForm');
            if (configForm) {
                configForm.classList.remove('d-none');
            }

            const addBtn = document.getElementById('addIntegrationBtn');
            if (addBtn) {
                addBtn.disabled = false;
            }
        });
    });

    // Handle form submission
    const addIntegrationBtn = document.getElementById('addIntegrationBtn');
    if (addIntegrationBtn) {
        addIntegrationBtn.addEventListener('click', function() {
            console.log('Add integration button clicked, type:', selectedIntegrationType);

            if (selectedIntegrationType) {
                const integrationData = {
                    platform: selectedIntegrationType,
                    name: document.getElementById('integrationName').value || `${selectedIntegrationType} Integration`,
                    config: {}
                };

                // Collect type-specific data
                switch (selectedIntegrationType) {
                    case 'whatsapp':
                        const whatsappKey = document.getElementById('whatsappApiKey');
                        alert(whatsappKey.value);
                        if (whatsappKey && whatsappKey.value.trim()) {
                            integrationData.config.api_key = whatsappKey.value.trim();
                        } else {
                            alert('Введите WhatsApp API Key');
                            return;
                        }
                        break;
                    case 'telegram_client':
                        const apiId = document.getElementById('telegramClientApiId');
                        const apiHash = document.getElementById('telegramClientApiHash');
                        const phone = document.getElementById('telegramClientPhone');

                        if (apiId && apiHash && phone && 
                            apiId.value.trim() && apiHash.value.trim() && phone.value.trim()) {
                            integrationData.config.api_id = apiId.value.trim();
                            integrationData.config.api_hash = apiHash.value.trim();
                            integrationData.config.phone_number = phone.value.trim();
                        } else {
                            alert('Заполните все поля для Telegram Client');
                            return;
                        }
                        break;
                    case 'telegram_bot':
                        const botToken = document.getElementById('telegramBotToken');
                        if (botToken && botToken.value.trim()) {
                            integrationData.config.bot_token = botToken.value.trim();
                        } else {
                            alert('Введите Bot Token');
                            return;
                        }
                        break;
                    case 'instagram':
                        const instaKey = document.getElementById('instagramApiKey');
                        if (instaKey && instaKey.value.trim()) {
                            integrationData.config.api_key = instaKey.value.trim();
                        } else {
                            alert('Введите Instagram API Key');
                            return;
                        }
                        break;
                }

                saveIntegration(integrationData);
            }
        });
    }

    // Reset modal when closed
    const modal = document.getElementById('addIntegrationModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', resetAddIntegrationModal);
    }
}

function resetAddIntegrationModal() {
    console.log('Resetting add integration modal...');

    // Remove selection from all cards
    document.querySelectorAll('.integration-type-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Hide all config sections
    document.querySelectorAll('.integration-config').forEach(config => {
        config.classList.add('d-none');
    });

    // Hide configuration form
    const configForm = document.getElementById('integrationConfigForm');
    if (configForm) {
        configForm.classList.add('d-none');
    }

    const addBtn = document.getElementById('addIntegrationBtn');
    if (addBtn) {
        addBtn.disabled = true;
    }

    // Reset form
    const form = document.getElementById('newIntegrationForm');
    if (form) {
        form.reset();
    }
}

// Function to save integration
async function saveIntegration(integrationData) {
    console.log('Saving integration:', integrationData);

    const addBtn = document.getElementById('addIntegrationBtn');
    const originalText = addBtn.innerHTML;
    addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Создание...';
    addBtn.disabled = true;

    try {
        const hash = document.getElementById('hidden-data-hash').textContent;

        const response = await fetch('/api/createIntegration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                hash: hash,
                name: integrationData.name,
                platform: integrationData.platform,
                config: integrationData.config,
                status: 'active'
            })
        });

        const data = await response.json();
        console.log('Integration creation response:', data);

        if (data.success) {
            console.log('Integration created successfully!');

            // Close modal
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('addIntegrationModal'));
            if (modalInstance) {
                modalInstance.hide();
            }

            // Show success message
            app.showNotification('Интеграция успешно создана!', 'success');

            // Refresh integrations list
            if (app && app.renderIntegrations) {
                app.renderIntegrations();
            }
        } else {
            console.error('Server returned error:', data.error);
            app.showNotification('Ошибка при создании интеграции: ' + (data.error || 'Неизвестная ошибка'), 'error');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        app.showNotification('Ошибка при создании интеграции: ' + error.message, 'error');
    } finally {
        // Restore button state
        addBtn.innerHTML = originalText;
        addBtn.disabled = false;
    }
}

    // Make saveIntegration globally available
window.saveIntegration = saveIntegration;

// Handle window resize
window.addEventListener('resize', () => {
    if (app && app.charts) {
        Object.values(app.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }
});

// Assistant Modal Management
function initializeAssistantModal() {
    const addAssistantModal = document.getElementById('addAssistantModal');
    if (addAssistantModal) {
        addAssistantModal.addEventListener('show.bs.modal', () => {
            resetAssistantModal();
        });
    }

    // Setup integration option selection
    setupIntegrationSelection();
}

function resetAssistantModal() {
    currentAssistantStep = 1;
    selectedIntegrationType = null;

    // Show step 1, hide others
    document.getElementById('step1-integration').classList.remove('d-none');
    document.getElementById('step2-settings').classList.add('d-none');
    document.getElementById('step3-assistant').classList.add('d-none');

    // Reset button states
    document.getElementById('prevStepBtn').classList.add('d-none');
    const nextBtn = document.getElementById('nextStepBtn');
    nextBtn.classList.remove('d-none');
    nextBtn.disabled = true; // Disable until integration type is selected
    document.getElementById('createAssistantBtn').classList.add('d-none');

    // Reset form
    document.getElementById('addAssistantForm').reset();
    clearAllIntegrationSettings();

    // Remove selection from integration options
    document.querySelectorAll('.integration-option').forEach(option => {
        option.classList.remove('selected');
    });

    // Update modal title
    document.getElementById('assistantModalTitle').textContent = 'Добавить ассистента';
}

function setupIntegrationSelection() {
    console.log('Setting up integration selection...');
    const options = document.querySelectorAll('.integration-option');
    console.log('Found integration options:', options.length);

    options.forEach(option => {
        console.log('Adding click listener to:', option.dataset.type);
        option.addEventListener('click', () => {
            console.log('Integration option clicked:', option.dataset.type);

            // Remove previous selection
            document.querySelectorAll('.integration-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            // Add selection to clicked option
            option.classList.add('selected');
            selectedIntegrationType = option.dataset.type;
            window.selectedIntegrationType = selectedIntegrationType; // Store globally

            console.log('Selected integration type:', selectedIntegrationType);
            console.log('Stored in window:', window.selectedIntegrationType);

            // Enable next button
            const nextBtn = document.getElementById('nextStepBtn');
            if (nextBtn) {
                nextBtn.disabled = false;
                console.log('Next button enabled for type:', selectedIntegrationType);
            } else {
                console.error('Next button not found!');
            }
        });
    });
}

function nextAssistantStep() {
    console.log('nextAssistantStep called, current step:', currentAssistantStep);
    console.log('selectedIntegrationType:', selectedIntegrationType);

    if (currentAssistantStep === 1) {
        if (!selectedIntegrationType) {
            alert('Пожалуйста, выберите тип интеграции');
            return;
        }
        console.log('Moving to step 2');
        showStep2();
    } else if (currentAssistantStep === 2) {
        console.log('Validating integration settings for step 3');
        if (validateIntegrationSettings()) {
            showStep3();
        } else {
            console.log('Integration settings validation failed');
        }
    }
}

function prevAssistantStep() {
    if (currentAssistantStep === 2) {
        showStep1();
    } else if (currentAssistantStep === 3) {
        showStep2();
    }
}

function showStep1() {
    currentAssistantStep = 1;
    document.getElementById('step1-integration').classList.remove('d-none');
    document.getElementById('step2-settings').classList.add('d-none');
    document.getElementById('step3-assistant').classList.add('d-none');

    document.getElementById('prevStepBtn').classList.add('d-none');
    document.getElementById('nextStepBtn').classList.remove('d-none');
    document.getElementById('createAssistantBtn').classList.add('d-none');

    document.getElementById('assistantModalTitle').textContent = 'Выберите тип интеграции';
}

function showStep2() {
    currentAssistantStep = 2;
    document.getElementById('step1-integration').classList.add('d-none');
    document.getElementById('step2-settings').classList.remove('d-none');
    document.getElementById('step3-assistant').classList.add('d-none');

    // Show appropriate settings based on integration type
    clearAllIntegrationSettings();
    showIntegrationSettings(selectedIntegrationType);

    document.getElementById('prevStepBtn').classList.remove('d-none');
    document.getElementById('nextStepBtn').classList.remove('d-none');
    document.getElementById('createAssistantBtn').classList.add('d-none');

    document.getElementById('assistantModalTitle').textContent = 'Настройки интеграции';
}

function showStep3() {
    console.log('showStep3 called');
    currentAssistantStep = 3;

    // Hide previous steps
    const step1 = document.getElementById('step1-integration');
    const step2 = document.getElementById('step2-settings');
    const step3 = document.getElementById('step3-assistant');

    console.log('Step elements found:', {
        step1: !!step1,
        step2: !!step2,
        step3: !!step3
    });

    if (step1) step1.classList.add('d-none');
    if (step2) step2.classList.add('d-none');
    if (step3) step3.classList.remove('d-none');

    // Update buttons
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const createBtn = document.getElementById('createAssistantBtn');

    console.log('Button elements found:', {
        prevBtn: !!prevBtn,
        nextBtn: !!nextBtn,
        createBtn: !!createBtn
    });

    if (prevBtn) prevBtn.classList.remove('d-none');
    if (nextBtn) nextBtn.classList.add('d-none');
    if (createBtn) {
        createBtn.classList.remove('d-none');
        console.log('Create button visibility after show:', !createBtn.classList.contains('d-none'));
        console.log('Create button onclick:', createBtn.getAttribute('onclick'));
        console.log('Create button disabled:', createBtn.disabled);

        // Remove any existing event listeners and add direct call
        createBtn.removeEventListener('click', debugCreateButtonClick);
        createBtn.onclick = function(e) {
            console.log('DIRECT: Create button onclick triggered');
            e.preventDefault();
            console.log('Checking if saveAssistant exists:', typeof saveAssistant);
            console.log('Checking window.saveAssistant:', typeof window.saveAssistant);
            if (typeof saveAssistant === 'function') {
                saveAssistant();
            } else if (typeof window.saveAssistant === 'function') {
                window.saveAssistant();
            } else {
                console.error('saveAssistant function not found anywhere!');
            }
        };
    }

    // Update title
    const titleElement = document.getElementById('assistantModalTitle');
    if (titleElement) {
        titleElement.textContent = 'Настройки ассистента';
    }

    // Pre-fill form fields for testing
    const nameField = document.getElementById('assistantName');
    const toneField = document.getElementById('assistantTone');
    const languageField = document.getElementById('assistantLanguage');
    const promptField = document.getElementById('assistantPrompt');

    if (nameField && !nameField.value) {
        nameField.value = 'Тестовый ассистент';
    }
    if (toneField && !toneField.value) {
        toneField.value = 'friendly';
    }
    if (languageField && !languageField.value) {
        languageField.value = 'ru';
    }
    if (promptField && !promptField.value) {
        promptField.value = 'Вы полезный ассистент, который помогает пользователям.';
    }

    console.log('Form pre-filled with default values');
    console.log('Successfully moved to step 3');
}

function clearAllIntegrationSettings() {
    document.querySelectorAll('.integration-settings').forEach(settings => {
        settings.classList.add('d-none');
    });
}

function showIntegrationSettings(type) {
    const settingsMap = {
        'telegram_bot': 'telegram-bot-settings',
        'telegram_client': 'telegram-client-settings',
        'whatsapp': 'whatsapp-settings',
        'instagram': 'instagram-settings'
    };

    const settingsId = settingsMap[type];
    if (settingsId) {
        document.getElementById(settingsId).classList.remove('d-none');
    }
}

function validateIntegrationSettings() {
    return true;
    /*
    console.log('validateIntegrationSettings called for type:', selectedIntegrationType);

    const validators = {
        'whatsapp': () => {
            const apiKeyElement = document.getElementById('whatsappApiKey');
            alert('WhatsApp API key element:', apiKeyElement.value)
            console.log('WhatsApp API key element:', apiKeyElement);
            if (!apiKeyElement) {
                console.error('WhatsApp API key element not found');
                alert('Элемент WhatsApp API Key не найден');
                return false;
            }
            const apiKey = apiKeyElement.value.trim();
            console.log('WhatsApp API key value:', apiKey);
            if (!apiKey) {
                alert('Пожалуйста, введите WhatsApp API Key');
                return false;
            }
            return true;
        },
        'telegram_client': () => {
            const apiIdElement = document.getElementById('apiId');
            const apiHashElement = document.getElementById('apiHash');
            const phoneElement = document.getElementById('phoneNumber');

            if (!apiIdElement || !apiHashElement || !phoneElement) {
                console.error('Telegram client elements not found');
                alert('Элементы формы Telegram Client не найдены');
                return false;
            }

            const apiId = apiIdElement.value.trim();
            const apiHash = apiHashElement.value.trim();
            const phone = phoneElement.value.trim();

            if (!apiId || !apiHash || !phone) {
                alert('Пожалуйста, заполните все обязательные поля');
                return false;
            }
            return true;
        },
        'telegram_bot': () => {
            const tokenElement = document.getElementById('botToken');
            console.log('Bot token element:', tokenElement);
            if (!tokenElement) {
                console.error('Bot token element not found');
                alert('Элемент Bot Token не найден');
                return false;
            }
            const token = tokenElement.value.trim();
            console.log('Bot token value:', token);
            if (!token) {
                alert('Пожалуйста, введите Bot Token');
                return false;
            }
            return true;
        },
        'instagram': () => {
            const tokenElement = document.getElementById('instagramApiKey');
            console.log('Instagram API key element:', tokenElement);
            if (!tokenElement) {
                console.error('Instagram API key element not found');
                alert('Элемент Instagram API Key не найден');
                return false;
            }
            const token = tokenElement.value.trim();
            console.log('Instagram API key value:', token);
            if (!token) {
                alert('Пожалуйста, введите Instagram API Key');
                return false;
            }
            return true;
        }
    };

    const validator = validators[selectedIntegrationType];
    const result = validator ? validator() : true;
    console.log('Validation result:', result);
    return result;
    */
}





// Prompt preset functions
function setPromptPreset(type) {
    const prompts = {
        support: 'Ты дружелюбный ассистент службы поддержки. Всегда помогай клиентам с их вопросами, будь вежливым и терпеливым. Если не знаешь ответ, предложи обратиться к специалисту.',
        sales: 'Ты профессиональный консультант по продажам. Помогай клиентам выбрать подходящие товары и услуги, отвечай на вопросы о характеристиках и ценах. Будь убедительным, но не навязчивым.',
        consultant: 'Ты экспертный консультант в своей области. Предоставляй детальные и профессиональные ответы, основанные на знаниях и опыте. Объясняй сложные вопросы простым языком.'
    };

    const promptField = document.getElementById('assistantPrompt');
    if (promptField && prompts[type]) {
        promptField.value = prompts[type];
    }
}

// Service worker registration (for future PWA support)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Service worker registration would go here
        console.log('Dashboard app loaded successfully');
    });
}

DashboardApp.prototype.setupAssistantModal = initializeAssistantModal;
DashboardApp.prototype.setupAddIntegrationModal = initializeAddIntegrationModal;
DashboardApp.prototype.makeGloballyAvailable = function() {
    window.app = this;
    window.saveAssistant = saveAssistant;
    window.nextAssistantStep = nextAssistantStep;
    window.prevAssistantStep = prevAssistantStep;
    window.setPromptPreset = setPromptPreset;
};