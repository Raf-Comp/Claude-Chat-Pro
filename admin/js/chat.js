jQuery(document).ready(function($) {
    // Inicjalizacja zmiennych
    let currentRepo = null;
    let attachments = [];
    let isProcessing = false;

    // Obsługa wysyłania wiadomości
    $('#send-message').on('click', function() {
        sendMessage();
    });

    // Obsługa wysyłania wiadomości przez Enter (bez Shift)
    $('#message-input').on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Funkcja wysyłania wiadomości
    function sendMessage() {
        const messageInput = $('#message-input');
        const message = messageInput.val().trim();
        
        if (!message && attachments.length === 0) return;
        
        if (isProcessing) return;
        isProcessing = true;

        // Dodaj wiadomość użytkownika do czatu
        addMessageToChat('user', message, attachments);

        // Wyczyść input i załączniki
        messageInput.val('');
        attachments = [];
        updateAttachmentsList();

        // Pokaż wskaźnik pisania
        showTypingIndicator();

        // Wyślij wiadomość do API
        $.ajax({
            url: claudeChatPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'claude_chat_send_message',
                nonce: claudeChatPro.nonce,
                message: message,
                attachments: attachments,
                repo: currentRepo
            },
            success: function(response) {
                hideTypingIndicator();
                if (response.success) {
                    addMessageToChat('assistant', response.data.message);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                hideTypingIndicator();
                showNotification('Wystąpił błąd podczas wysyłania wiadomości.', 'error');
            },
            complete: function() {
                isProcessing = false;
            }
        });
    }

    // Dodawanie wiadomości do czatu
    function addMessageToChat(role, content, messageAttachments = []) {
        const messageHtml = `
            <div class="chat-message ${role}">
                <div class="message-header">
                    <span class="message-author">${role === 'user' ? 'Ty' : 'Asystent'}</span>
                    <span class="message-time">${new Date().toLocaleTimeString()}</span>
                </div>
                <div class="message-content">${formatMessageContent(content)}</div>
                ${messageAttachments.length > 0 ? formatAttachments(messageAttachments) : ''}
            </div>
        `;
        
        $('.chat-messages').append(messageHtml);
        scrollToBottom();
    }

    // Formatowanie zawartości wiadomości
    function formatMessageContent(content) {
        if (!content) return '';
        
        // Formatowanie kodu
        content = content.replace(/```(\w+)?\n([\s\S]*?)```/g, function(match, language, code) {
            return `<pre><code class="language-${language || 'text'}">${escapeHtml(code.trim())}</code></pre>`;
        });

        // Formatowanie inline kodu
        content = content.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Formatowanie linków
        content = content.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');

        return content;
    }

    // Formatowanie załączników
    function formatAttachments(attachments) {
        if (!attachments.length) return '';
        
        const attachmentsHtml = attachments.map(attachment => `
            <div class="attachment-preview">
                <span class="attachment-name">${attachment.name}</span>
                <span class="attachment-size">${formatFileSize(attachment.size)}</span>
            </div>
        `).join('');

        return `<div class="message-attachments">${attachmentsHtml}</div>`;
    }

    // Obsługa załączników
    $('#file-upload').on('change', function(e) {
        const files = e.target.files;
        if (!files.length) return;

        Array.from(files).forEach(file => {
            if (isValidFile(file)) {
                attachments.push(file);
            } else {
                showNotification('Nieprawidłowy typ pliku.', 'error');
            }
        });

        updateAttachmentsList();
        this.value = ''; // Reset input
    });

    // Aktualizacja listy załączników
    function updateAttachmentsList() {
        const attachmentsList = $('.attachments-list');
        attachmentsList.empty();

        attachments.forEach((file, index) => {
            const attachmentHtml = `
                <div class="attachment-item">
                    <span class="attachment-name">${file.name}</span>
                    <span class="attachment-size">${formatFileSize(file.size)}</span>
                    <button type="button" class="remove-attachment" data-index="${index}">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `;
            attachmentsList.append(attachmentHtml);
        });
    }

    // Usuwanie załącznika
    $(document).on('click', '.remove-attachment', function() {
        const index = $(this).data('index');
        attachments.splice(index, 1);
        updateAttachmentsList();
    });

    // Obsługa wyboru repozytorium
    $('.repo-item').on('click', function() {
        const repoId = $(this).data('repo-id');
        if (currentRepo === repoId) return;

        currentRepo = repoId;
        $('.repo-item').removeClass('active');
        $(this).addClass('active');

        // Wyczyść czat
        $('.chat-messages').empty();
        addWelcomeMessage();
    });

    // Wskaźnik pisania
    function showTypingIndicator() {
        const indicator = `
            <div class="typing-indicator">
                <div class="spinner"></div>
                <span>Asystent pisze...</span>
            </div>
        `;
        $('.chat-messages').append(indicator);
        scrollToBottom();
    }

    function hideTypingIndicator() {
        $('.typing-indicator').remove();
    }

    // Powiadomienia
    function showNotification(message, type = 'success') {
        const notification = $(`
            <div class="notification ${type}">
                ${message}
            </div>
        `);

        $('body').append(notification);

        setTimeout(() => {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Pomocnicze funkcje
    function scrollToBottom() {
        const chatMessages = $('.chat-messages');
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function isValidFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['text/plain', 'application/json', 'application/javascript', 'text/css', 'text/html'];
        return file.size <= maxSize && allowedTypes.includes(file.type);
    }

    // Inicjalizacja
    function init() {
        addWelcomeMessage();
        setupDarkMode();
    }

    function addWelcomeMessage() {
        const welcomeHtml = `
            <div class="welcome-message">
                <h2>Witaj w Claude Chat Pro!</h2>
                <ul>
                    <li>Wybierz repozytorium z listy po lewej stronie</li>
                    <li>Zadaj pytanie dotyczące kodu</li>
                    <li>Możesz załączać pliki do analizy</li>
                    <li>Użyj trybu ciemnego dla lepszego komfortu</li>
                </ul>
            </div>
        `;
        $('.chat-messages').html(welcomeHtml);
    }

    function setupDarkMode() {
        const darkModeToggle = $('#dark-mode-toggle');
        const isDarkMode = localStorage.getItem('darkMode') === 'true';

        if (isDarkMode) {
            $('body').addClass('dark-mode');
            darkModeToggle.prop('checked', true);
        }

        darkModeToggle.on('change', function() {
            $('body').toggleClass('dark-mode');
            localStorage.setItem('darkMode', $(this).is(':checked'));
        });
    }

    // Inicjalizacja aplikacji
    init();
}); 