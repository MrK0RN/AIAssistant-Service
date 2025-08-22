
class MessagesHandler {
    constructor() {
        this.messages = [];
        this.filteredMessages = [];
        this.currentPage = 1;
        this.messagesPerPage = 20;
        this.totalMessages = 0;
        this.searchQuery = '';
        this.selectedChannel = '';
        this.selectedStatus = '';
        this.sortBy = 'created_at';
        this.sortOrder = 'desc';

        this.init();
    }

    async init() {
        await this.loadMessages();
        this.setupEventListeners();
        this.renderMessages();
        this.updateStatistics();
    }

    /**
     * Загрузка сообщений из API
     */
    async loadMessages() {
        try {
            const response = await fetch('/api/messages?' + new URLSearchParams({
                page: this.currentPage,
                limit: this.messagesPerPage,
                search: this.searchQuery,
                channel: this.selectedChannel,
                status: this.selectedStatus,
                sort_by: this.sortBy,
                sort_order: this.sortOrder
            }));

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.messages = data.messages || [];
                this.totalMessages = data.total || 0;
                this.filteredMessages = [...this.messages];
                console.log(`Загружено ${this.messages.length} сообщений`);
            } else {
                console.error('Ошибка загрузки сообщений:', data.error);
                this.showError('Не удалось загрузить сообщения');
            }
        } catch (error) {
            console.error('Ошибка при загрузке сообщений:', error);
            this.showError('Ошибка соединения с сервером');
            // Показываем пустое состояние вместо ошибки
            this.messages = [];
            this.filteredMessages = [];
            this.totalMessages = 0;
        }
    }

    /**
     * Настройка обработчиков событий
     */
    setupEventListeners() {
        // Поиск сообщений
        const searchInput = document.getElementById('messageSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchQuery = e.target.value;
                this.debounce(() => this.filterAndReload(), 300)();
            });
        }

        // Фильтры
        const channelFilter = document.getElementById('channelFilter');
        if (channelFilter) {
            channelFilter.addEventListener('change', (e) => {
                this.selectedChannel = e.target.value;
                this.filterAndReload();
            });
        }

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.selectedStatus = e.target.value;
                this.filterAndReload();
            });
        }

        // Сортировка
        const sortSelect = document.getElementById('sortMessages');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                const [sortBy, sortOrder] = e.target.value.split('-');
                this.sortBy = sortBy;
                this.sortOrder = sortOrder;
                this.filterAndReload();
            });
        }

        // Обновление сообщений
        const refreshBtn = document.getElementById('refreshMessages');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadMessages().then(() => this.renderMessages());
            });
        }
    }

    /**
     * Фильтрация и перезагрузка сообщений
     */
    async filterAndReload() {
        this.currentPage = 1;
        await this.loadMessages();
        this.renderMessages();
        this.updatePagination();
    }

    /**
     * Отображение сообщений в таблице
     */
    renderMessages() {
        const messagesContainer = document.getElementById('messagesTable');
        if (!messagesContainer) {
            console.warn('Контейнер для сообщений не найден');
            return;
        }

        // Очистка контейнера
        messagesContainer.innerHTML = '';

        // Если нет сообщений
        if (this.filteredMessages.length === 0) {
            this.renderEmptyState(messagesContainer);
            return;
        }

        // Отображение сообщений
        this.filteredMessages.forEach(message => {
            const messageRow = this.createMessageRow(message);
            messagesContainer.appendChild(messageRow);
        });

        // Обновление пагинации
        this.updatePagination();
    }

    /**
     * Создание строки сообщения
     */
    createMessageRow(message) {
        const row = document.createElement('tr');
        row.className = 'message-row';
        row.dataset.messageId = message.id;

        // Определение статуса и стилей
        const statusClass = this.getStatusClass(message.status);
        const channelInfo = this.getChannelInfo(message.channel);

        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <img src="${this.getAvatarUrl(message.sender_name)}" 
                         class="rounded-circle me-2" width="32" height="32" 
                         alt="${message.sender_name}">
                    <div>
                        <div class="fw-medium">${this.escapeHtml(message.sender_name)}</div>
                        <small class="text-muted">${this.escapeHtml(message.sender_username || '')}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-${channelInfo.color}">
                    <i class="${channelInfo.icon} me-1"></i>
                    ${channelInfo.name}
                </span>
            </td>
            <td>
                <div class="message-content">
                    ${this.formatMessageContent(message.content)}
                </div>
                <small class="text-muted">${this.formatTime(message.created_at)}</small>
            </td>
            <td>
                <span class="badge bg-${statusClass.color}">
                    <i class="${statusClass.icon} me-1"></i>
                    ${statusClass.text}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="messagesHandler.viewMessage(${message.id})" title="Просмотр">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="messagesHandler.replyToMessage(${message.id})" title="Ответить">
                        <i class="fas fa-reply"></i>
                    </button>
                    <button class="btn btn-outline-secondary" onclick="messagesHandler.markAsRead(${message.id})" title="Отметить как прочитанное">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </td>
        `;

        // Добавление обработчика клика для открытия сообщения
        row.addEventListener('click', (e) => {
            if (!e.target.closest('.btn-group')) {
                this.viewMessage(message.id);
            }
        });

        return row;
    }

    /**
     * Отображение пустого состояния
     */
    renderEmptyState(container) {
        container.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Нет сообщений</h5>
                        <p class="text-muted">
                            ${this.searchQuery ? 'По вашему запросу ничего не найдено' : 'Сообщения появятся здесь после настройки ассистентов'}
                        </p>
                        ${!this.searchQuery ? `
                            <a href="/add-assistant" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Создать ассистента
                            </a>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Получение информации о канале
     */
    getChannelInfo(channel) {
        const channels = {
            telegram: { name: 'Telegram', icon: 'fab fa-telegram', color: 'info' },
            whatsapp: { name: 'WhatsApp', icon: 'fab fa-whatsapp', color: 'success' },
            instagram: { name: 'Instagram', icon: 'fab fa-instagram', color: 'danger' },
            email: { name: 'Email', icon: 'fas fa-envelope', color: 'secondary' },
            web: { name: 'Веб-сайт', icon: 'fas fa-globe', color: 'primary' }
        };

        return channels[channel] || { name: channel, icon: 'fas fa-comment', color: 'secondary' };
    }

    /**
     * Получение класса статуса
     */
    getStatusClass(status) {
        const statuses = {
            new: { text: 'Новое', icon: 'fas fa-circle', color: 'primary' },
            read: { text: 'Прочитано', icon: 'fas fa-eye', color: 'info' },
            replied: { text: 'Отвечено', icon: 'fas fa-reply', color: 'success' },
            closed: { text: 'Закрыто', icon: 'fas fa-check-circle', color: 'secondary' },
            pending: { text: 'В ожидании', icon: 'fas fa-clock', color: 'warning' }
        };

        return statuses[status] || { text: status, icon: 'fas fa-circle', color: 'secondary' };
    }

    /**
     * Форматирование содержимого сообщения
     */
    formatMessageContent(content) {
        if (!content) return '<em class="text-muted">Пустое сообщение</em>';

        // Обрезка длинных сообщений
        const maxLength = 100;
        let formatted = this.escapeHtml(content);

        if (formatted.length > maxLength) {
            formatted = formatted.substring(0, maxLength) + '...';
        }

        // Выделение ссылок
        formatted = formatted.replace(
            /(https?:\/\/[^\s]+)/g,
            '<a href="$1" target="_blank" class="text-primary">$1</a>'
        );

        return formatted;
    }

    /**
     * Форматирование времени
     */
    formatTime(timestamp) {
        if (!timestamp) return '';

        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        // Меньше минуты
        if (diff < 60000) {
            return 'только что';
        }

        // Меньше часа
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} мин назад`;
        }

        // Меньше суток
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours} ч назад`;
        }

        // Старше суток
        return date.toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Получение URL аватара
     */
    getAvatarUrl(name) {
        if (!name) return 'https://ui-avatars.com/api/?name=User&background=6c757d&color=fff&size=32';
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=007bff&color=fff&size=32`;
    }

    /**
     * Экранирование HTML
     */
    escapeHtml(text) {
        if (typeof text !== 'string') return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Обновление статистики
     */
    updateStatistics() {
        // Подсчет статистики
        const stats = {
            total: this.totalMessages,
            new: this.messages.filter(m => m.status === 'new').length,
            pending: this.messages.filter(m => m.status === 'pending').length,
            replied: this.messages.filter(m => m.status === 'replied').length
        };

        // Обновление счетчиков в интерфейсе
        this.updateStatElement('totalMessages', stats.total);
        this.updateStatElement('newMessages', stats.new);
        this.updateStatElement('pendingMessages', stats.pending);
        this.updateStatElement('repliedMessages', stats.replied);
    }

    /**
     * Обновление элемента статистики
     */
    updateStatElement(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    }

    /**
     * Обновление пагинации
     */
    updatePagination() {
        const totalPages = Math.ceil(this.totalMessages / this.messagesPerPage);
        const paginationContainer = document.getElementById('messagesPagination');

        if (!paginationContainer || totalPages <= 1) {
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = '<nav><ul class="pagination justify-content-center">';

        // Предыдущая страница
        if (this.currentPage > 1) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="messagesHandler.goToPage(${this.currentPage - 1})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        // Номера страниц
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="messagesHandler.goToPage(${i})">${i}</a>
                </li>
            `;
        }

        // Следующая страница
        if (this.currentPage < totalPages) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="messagesHandler.goToPage(${this.currentPage + 1})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
        }

        paginationHTML += '</ul></nav>';
        paginationContainer.innerHTML = paginationHTML;
    }

    /**
     * Переход к странице
     */
    async goToPage(page) {
        this.currentPage = page;
        await this.loadMessages();
        this.renderMessages();
    }

    /**
     * Просмотр сообщения
     */
    async viewMessage(messageId) {
        try {
            const response = await fetch(`/api/messages/${messageId}`);
            const data = await response.json();

            if (data.success) {
                this.showMessageModal(data.message);
            } else {
                this.showError('Не удалось загрузить сообщение');
            }
        } catch (error) {
            console.error('Ошибка при загрузке сообщения:', error);
            this.showError('Ошибка соединения');
        }
    }

    /**
     * Отображение модального окна сообщения
     */
    showMessageModal(message) {
        // Создание модального окна если его нет
        let modal = document.getElementById('messageModal');
        if (!modal) {
            modal = this.createMessageModal();
            document.body.appendChild(modal);
        }

        // Заполнение данными
        modal.querySelector('#messageModalTitle').textContent = `Сообщение от ${message.sender_name}`;
        modal.querySelector('#messageContent').innerHTML = this.formatMessageContent(message.content);
        modal.querySelector('#messageTime').textContent = this.formatTime(message.created_at);
        modal.querySelector('#messageChannel').innerHTML = `
            <span class="badge bg-${this.getChannelInfo(message.channel).color}">
                <i class="${this.getChannelInfo(message.channel).icon} me-1"></i>
                ${this.getChannelInfo(message.channel).name}
            </span>
        `;

        // Показать модальное окно
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }

    /**
     * Создание модального окна для сообщения
     */
    createMessageModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'messageModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messageModalTitle">Сообщение</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Канал:</strong>
                                <div id="messageChannel"></div>
                            </div>
                            <div class="col-md-6">
                                <strong>Время:</strong>
                                <div id="messageTime"></div>
                            </div>
                        </div>
                        <div>
                            <strong>Содержимое:</strong>
                            <div id="messageContent" class="border rounded p-3 mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="button" class="btn btn-primary">Ответить</button>
                    </div>
                </div>
            </div>
        `;
        return modal;
    }

    /**
     * Ответ на сообщение
     */
    async replyToMessage(messageId) {
        // Здесь будет логика ответа на сообщение
        this.showInfo('Функция ответа на сообщение будет реализована в следующих версиях');
    }

    /**
     * Отметка сообщения как прочитанное
     */
    async markAsRead(messageId) {
        try {
            const response = await fetch(`/api/messages/${messageId}/mark-read`, {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                // Обновляем сообщение в списке
                const messageIndex = this.messages.findIndex(m => m.id === messageId);
                if (messageIndex !== -1) {
                    this.messages[messageIndex].status = 'read';
                    this.renderMessages();
                    this.updateStatistics();
                }
            } else {
                this.showError('Не удалось отметить сообщение как прочитанное');
            }
        } catch (error) {
            console.error('Ошибка при изменении статуса сообщения:', error);
            this.showError('Ошибка соединения');
        }
    }

    /**
     * Вспомогательная функция debounce
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Показ сообщения об ошибке
     */
    showError(message) {
        this.showAlert(message, 'danger');
    }

    /**
     * Показ информационного сообщения
     */
    showInfo(message) {
        this.showAlert(message, 'info');
    }

    /**
     * Показ уведомления
     */
    showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Инициализация при загрузке страницы
let messagesHandler;

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация только если мы на странице с сообщениями
    if (document.getElementById('messagesTable')) {
        messagesHandler = new MessagesHandler();
    }
});

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MessagesHandler;
}