document.addEventListener('DOMContentLoaded', function() {
    const ChatInterface = {
        elements: {
            chatMessages: document.getElementById('chat-messages'),
            chatInput: document.getElementById('chat-input'),
            sendButton: document.getElementById('send-message'),
            attachFileBtn: document.getElementById('attach-file'),
            attachRepoBtn: document.getElementById('attach-repo'),
            attachmentsList: document.getElementById('attachments-list'),
            repoSearch: document.getElementById('repo-search'),
            repoList: document.getElementById('repo-list')
        },

        templates: {
            message: document.getElementById('message-template'),
            codeBlock: document.getElementById('code-block-template')
        },

        attachments: [],
        
        init() {
            this.bindEvents();
            this.loadGitHubRepos();
            hljs.highlightAll();
        },

        bindEvents() {
            // Wysyłanie wiadomości
            this.elements.sendButton.addEventListener('click', () => this.sendMessage());
            this.elements.chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Załączniki
            this.elements.attachFileBtn.addEventListener('click', () => this.handleFileAttachment());
            this.elements.attachRepoBtn.addEventListener('click', () => this.handleRepoAttachment());

            // Wyszukiwanie repozytoriów
            this.elements.repoSearch.addEventListener('input', 
                debounce((e) => this.searchRepos(e.target.value), 300)
            );
        },

        async sendMessage() {
            const message = this.elements.chatInput.value.trim();
            if (!message && !this.attachments.length) return;

            // Dodaj wiadomość użytkownika do czatu
            this.addMessage({
                role: 'user',
                content: message,
                attachments: this.attachments
            });

            // Przygotuj dane do wysłania
            const formData = new FormData();
            formData.append('action', 'claude_chat_send_message');
            formData.append('message', message);
            formData.append('nonce', claudeChatPro.nonce);
            
            // Dodaj załączniki
            this.attachments.forEach(attachment => {
                if (attachment.type === 'file') {
                    formData.append('files[]', attachment.file);
                } else if (attachment.type === 'repo') {
                    formData.append('repo_files[]', JSON.stringify(attachment.data));
                }
            });

            try {
                const response = await fetch(claudeChatPro.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success) {
                    this.addMessage({
                        role: 'assistant',
                        content: data.response,
                        timestamp: new Date()
                    });
                } else {
                    throw new Error(data.message || 'Błąd komunikacji z serwerem');
                }
            } catch (error) {
                this.showError(error.message);
            }

            // Wyczyść pole wprowadzania i załączniki
            this.elements.chatInput.value = '';
            this.clearAttachments();
        },

        addMessage({ role, content, attachments = [], timestamp = new Date() }) {
            const messageEl = this.templates.message.content.cloneNode(true);
            const messageDiv = messageEl.querySelector('.chat-message');
            
            messageDiv.classList.add(role);
            messageDiv.querySelector('.message-author').textContent = 
                role === 'user' ? 'Ty' : 'Claude AI';
            messageDiv.querySelector('.message-time').textContent = 
                this.formatTimestamp(timestamp);

            const contentDiv = messageDiv.querySelector('.message-content');
            
            // Przetwarzanie treści wiadomości (kod, tekst)
            if (role === 'assistant') {
                contentDiv.innerHTML = this.processMessageContent(content);
                // Podświetlanie składni dla bloków kodu
                contentDiv.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightBlock(block);
                });
            } else {
                contentDiv.textContent = content;
                // Dodaj załączniki do wiadomości użytkownika
                if (attachments.length) {
                    const attachmentsDiv = document.createElement('div');
                    attachmentsDiv.classList.add('message-attachments');
                    attachments.forEach(att => {
                        const attEl = document.createElement('div');
                        attEl.classList.add('attachment-preview');
                        attEl.textContent = att.name;
                        attachmentsDiv.appendChild(attEl);
                    });
                    contentDiv.appendChild(attachmentsDiv);
                }
            }

            this.elements.chatMessages.appendChild(messageDiv);
            this.scrollToBottom();
        },

        processMessageContent(content) {
            // Zamiana bloków kodu na elementy z przyciskami kopiowania/pobierania
            return content.replace(/```(\w+)?\n([\s\S]+?)```/g, (match, lang, code) => {
                const codeBlock = this.templates.codeBlock.content.cloneNode(true);
                const pre = codeBlock.querySelector('pre');
                const codeEl = pre.querySelector('code');
                
                if (lang) {
                    codeEl.classList.add(`language-${lang}`);
                    codeBlock.querySelector('.code-language').textContent = lang;
                }
                
                codeEl.textContent = code.trim();
                
                // Dodaj obsługę przycisków
                const copyBtn = codeBlock.querySelector('.copy-code');
                copyBtn.addEventListener('click', () => this.copyCodeToClipboard(code.trim()));
                
                const downloadBtn = codeBlock.querySelector('.download-code');
                downloadBtn.addEventListener('click', () => this.downloadCode(code.trim(), lang));
                
                return codeBlock.innerHTML;
            });
        },

        async handleFileAttachment() {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            
            input.addEventListener('change', (e) => {
                Array.from(e.target.files).forEach(file => {
                    this.attachments.push({
                        type: 'file',
                        file: file,
                        name: file.name
                    });
                });
                this.updateAttachmentsList();
            });
            
            input.click();
        },

        handleRepoAttachment() {
            // Implementacja wyboru plików z repozytorium
            // To będzie zintegrowane z panelem bocznym repozytoriów
        },

        async loadGitHubRepos() {
            try {
                const response = await fetch(`${claudeChatPro.ajaxUrl}?action=claude_chat_get_repos&nonce=${claudeChatPro.nonce}`);
                const data = await response.json();

                if (data.success) {
                    this.renderRepoList(data.repos);
                }
            } catch (error) {
                this.showError('Nie udało się załadować repozytoriów');
            }
        },

        renderRepoList(repos) {
            this.elements.repoList.innerHTML = '';
            repos.forEach(repo => {
                const repoEl = document.createElement('div');
                repoEl.classList.add('repo-item');
                repoEl.innerHTML = `
                    <h4>${repo.name}</h4>
                    <p>${repo.description || ''}</p>
                `;
                this.elements.repoList.appendChild(repoEl);
            });
        },

        copyCodeToClipboard(code) {
            navigator.clipboard.writeText(code).then(() => {
                this.showNotification('Kod skopiowany do schowka');
            });
        },

        downloadCode(code, language) {
            const extension = language || 'txt';
            const blob = new Blob([code], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `code.${extension}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        },

        updateAttachmentsList() {
            this.elements.attachmentsList.innerHTML = '';
            this.attachments.forEach((att, index) => {
                const attEl = document.createElement('div');
                attEl.classList.add('attachment-item');
                attEl.innerHTML = `
                    <span>${att.name}</span>
                    <button type="button" class="remove-attachment" data-index="${index}">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                `;
                this.elements.attachmentsList.appendChild(attEl);
            });
        },

        clearAttachments() {
            this.attachments = [];
            this.updateAttachmentsList();
        },

        scrollToBottom() {
            this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;
        },

        showNotification(message) {
            // Implementacja powiadomień
        },

        showError(message) {
            // Implementacja wyświetlania błędów
        },

        formatTimestamp(date) {
            return new Intl.DateTimeFormat('pl', {
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        }
    };

    // Inicjalizacja interfejsu
    ChatInterface.init();
});

// Funkcja pomocnicza debounce
function debounce(func, wait) {
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