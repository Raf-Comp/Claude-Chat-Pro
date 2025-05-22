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
            if (!this.elements.chatMessages) {
                console.error('Chat interface elements not found');
                return;
            }
            
            this.bindEvents();
            this.loadGitHubRepos();
            
            // Inicjalizuj highlight.js jeśli jest dostępne
            if (typeof hljs !== 'undefined') {
                hljs.highlightAll();
            }
        },

        bindEvents() {
            // Wysyłanie wiadomości
            if (this.elements.sendButton) {
                this.elements.sendButton.addEventListener('click', () => this.sendMessage());
            }
            
            if (this.elements.chatInput) {
                this.elements.chatInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
            }

            // Załączniki
            if (this.elements.attachFileBtn) {
                this.elements.attachFileBtn.addEventListener('click', () => this.handleFileAttachment());
            }
            
            if (this.elements.attachRepoBtn) {
                this.elements.attachRepoBtn.addEventListener('click', () => this.handleRepoAttachment());
            }

            // Wyszukiwanie repozytoriów
            if (this.elements.repoSearch) {
                this.elements.repoSearch.addEventListener('input', 
                    this.debounce((e) => this.searchRepos(e.target.value), 300)
                );
            }

            // Obsługa usuwania załączników
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-attachment')) {
                    const index = parseInt(e.target.dataset.index);
                    this.removeAttachment(index);
                }
            });
        },

        async sendMessage() {
            const message = this.elements.chatInput.value.trim();
            if (!message && !this.attachments.length) return;

            // Dodaj wiadomość użytkownika do czatu
            this.addMessage({
                role: 'user',
                content: message,
                attachments: [...this.attachments]
            });

            // Pokaż wskaźnik ładowania
            this.showTypingIndicator();

            const formData = new FormData();
            formData.append('action', 'claude_chat_send_message');
            formData.append('message', message);
            formData.append('nonce', claudeChatPro.nonce);
            
            // Dodaj załączniki
            this.attachments.forEach((attachment, index) => {
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

                this.hideTypingIndicator();

                if (data.success) {
                    this.addMessage({
                        role: 'assistant',
                        content: data.data.response,
                        timestamp: new Date()
                    });
                } else {
                    throw new Error(data.data?.message || 'Błąd komunikacji z serwerem');
                }
            } catch (error) {
                this.hideTypingIndicator();
                this.showError(error.message);
            }

            // Wyczyść pole wprowadzania i załączniki
            this.elements.chatInput.value = '';
            this.clearAttachments();
        },

        addMessage({ role, content, attachments = [], timestamp = new Date() }) {
            if (!this.templates.message) {
                console.error('Message template not found');
                return;
            }

            const messageEl = this.templates.message.content.cloneNode(true);
            const messageDiv = messageEl.querySelector('.chat-message');
            
            messageDiv.classList.add(role);
            messageDiv.querySelector('.message-author').textContent = 
                role === 'user' ? 'Ty' : 'Claude AI';
            messageDiv.querySelector('.message-time').textContent = 
                this.formatTimestamp(timestamp);

            const contentDiv = messageDiv.querySelector('.message-content');
            
            // Przetwarzanie treści wiadomości
            if (role === 'assistant') {
                contentDiv.innerHTML = this.processMessageContent(content);
                // Podświetlanie składni dla bloków kodu
                if (typeof hljs !== 'undefined') {
                    contentDiv.querySelectorAll('pre code').forEach((block) => {
                        hljs.highlightElement(block);
                    });
                }
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
            // Obsługa podstawowych formatowań markdown
            let processed = content
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`([^`]+)`/g, '<code>$1</code>');

            // Zamiana bloków kodu na elementy z przyciskami
            processed = processed.replace(/```(\w+)?\n([\s\S]+?)```/g, (match, lang, code) => {
                const languageLabel = lang || 'text';
                const codeId = 'code-' + Math.random().toString(36).substr(2, 9);
                
                return `
                    <div class="code-block">
                        <div class="code-header">
                            <span class="code-language">${languageLabel}</span>
                            <div class="code-actions">
                                <button type="button" class="copy-code" data-code-id="${codeId}" title="Kopiuj kod">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                                <button type="button" class="download-code" data-code-id="${codeId}" data-lang="${languageLabel}" title="Pobierz kod">
                                    <span class="dashicons dashicons-download"></span>
                                </button>
                            </div>
                        </div>
                        <pre><code class="language-${lang || 'text'}" id="${codeId}">${this.escapeHtml(code.trim())}</code></pre>
                    </div>
                `;
            });

            // Obsługa przycisków kodu
            setTimeout(() => {
                document.querySelectorAll('.copy-code').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const codeId = e.currentTarget.dataset.codeId;
                        const codeElement = document.getElementById(codeId);
                        if (codeElement) {
                            this.copyCodeToClipboard(codeElement.textContent);
                        }
                    });
                });

                document.querySelectorAll('.download-code').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const codeId = e.currentTarget.dataset.codeId;
                        const lang = e.currentTarget.dataset.lang;
                        const codeElement = document.getElementById(codeId);
                        if (codeElement) {
                            this.downloadCode(codeElement.textContent, lang);
                        }
                    });
                });
            }, 100);

            return processed;
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        showTypingIndicator() {
            const indicator = document.createElement('div');
            indicator.classList.add('chat-message', 'assistant', 'typing-indicator');
            indicator.innerHTML = `
                <div class="message-header">
                    <span class="message-author">Claude AI</span>
                    <span class="message-time">pisze...</span>
                </div>
                <div class="message-content">
                    <div class="spinner"></div>
                </div>
            `;
            this.elements.chatMessages.appendChild(indicator);
            this.scrollToBottom();
        },

        hideTypingIndicator() {
            const indicator = this.elements.chatMessages.querySelector('.typing-indicator');
            if (indicator) {
                indicator.remove();
            }
        },

        async handleFileAttachment() {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.accept = '.txt,.md,.js,.php,.css,.html,.json,.xml,.yml,.yaml,.py,.java,.cpp,.c,.h,.cs,.rb,.go,.rs,.sql';
            
            input.addEventListener('change', async (e) => {
                for (const file of Array.from(e.target.files)) {
                    if (file.size > 1024 * 1024) { // 1MB limit
                        this.showError(`Plik ${file.name} jest za duży (maksymalnie 1MB)`);
                        continue;
                    }

                    try {
                        const content = await this.readFileContent(file);
                        this.attachments.push({
                            type: 'file',
                            file: file,
                            name: file.name,
                            content: content
                        });
                    } catch (error) {
                        this.showError(`Nie udało się odczytać pliku ${file.name}`);
                    }
                }
                this.updateAttachmentsList();
            });
            
            input.click();
        },

        readFileContent(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = reject;
                reader.readAsText(file);
            });
        },

        handleRepoAttachment() {
            this.showNotification('Funkcja załączania kodu z GitHub będzie dostępna wkrótce');
        },

        async loadGitHubRepos() {
            if (!this.elements.repoList) return;

            try {
                const response = await fetch(`${claudeChatPro.ajaxUrl}?action=claude_chat_get_repositories&nonce=${claudeChatPro.nonce}`);
                const data = await response.json();

                if (data.success) {
                    this.renderRepoList(data.data.repositories || []);
                }
            } catch (error) {
                console.error('Nie udało się załadować repozytoriów:', error);
            }
        },

        renderRepoList(repos) {
            if (!this.elements.repoList) return;

            this.elements.repoList.innerHTML = '';
            
            if (repos.length === 0) {
                this.elements.repoList.innerHTML = '<p>Brak dostępnych repozytoriów</p>';
                return;
            }

            repos.forEach(repo => {
                const repoEl = document.createElement('div');
                repoEl.classList.add('repo-item');
                repoEl.innerHTML = `
                    <h4>${this.escapeHtml(repo.name)}</h4>
                    <p>${this.escapeHtml(repo.description || 'Brak opisu')}</p>
                `;
                
                repoEl.addEventListener('click', () => {
                    this.showNotification(`Wybrano repozytorium: ${repo.name}`);
                });
                
                this.elements.repoList.appendChild(repoEl);
            });
        },

        searchRepos(query) {
            if (!this.elements.repoList) return;

            const items = this.elements.repoList.querySelectorAll('.repo-item');
            items.forEach(item => {
                const title = item.querySelector('h4').textContent.toLowerCase();
                const desc = item.querySelector('p').textContent.toLowerCase();
                const matches = title.includes(query.toLowerCase()) || desc.includes(query.toLowerCase());
                item.style.display = matches ? 'block' : 'none';
            });
        },

        copyCodeToClipboard(code) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    this.showNotification('Kod skopiowany do schowka');
                }).catch(() => {
                    this.fallbackCopyToClipboard(code);
                });
            } else {
                this.fallbackCopyToClipboard(code);
            }
        },

        fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showNotification('Kod skopiowany do schowka');
            } catch (err) {
                this.showError('Nie udało się skopiować kodu');
            }
            
            document.body.removeChild(textArea);
        },

        downloadCode(code, language) {
            const extension = this.getFileExtension(language);
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

        getFileExtension(language) {
            const extensions = {
                'javascript': 'js',
                'python': 'py',
                'php': 'php',
                'html': 'html',
                'css': 'css',
                'java': 'java',
                'cpp': 'cpp',
                'c': 'c',
                'csharp': 'cs',
                'ruby': 'rb',
                'go': 'go',
                'rust': 'rs',
                'sql': 'sql',
                'json': 'json',
                'xml': 'xml',
                'yaml': 'yml'
            };
            return extensions[language] || 'txt';
        },

        updateAttachmentsList() {
            if (!this.elements.attachmentsList) return;

            this.elements.attachmentsList.innerHTML = '';
            this.attachments.forEach((att, index) => {
                const attEl = document.createElement('div');
                attEl.classList.add('attachment-item');
                attEl.innerHTML = `
                    <span>${this.escapeHtml(att.name)}</span>
                    <button type="button" class="remove-attachment" data-index="${index}">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                `;
                this.elements.attachmentsList.appendChild(attEl);
            });
        },

        removeAttachment(index) {
            this.attachments.splice(index, 1);
            this.updateAttachmentsList();
        },

        clearAttachments() {
            this.attachments = [];
            this.updateAttachmentsList();
        },

        scrollToBottom() {
            if (this.elements.chatMessages) {
                this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;
            }
        },

        showNotification(message) {
            // Prosta implementacja powiadomień
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #46b450;
                color: white;
                padding: 10px 15px;
                border-radius: 4px;
                z-index: 10000;
                animation: fadeIn 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        },

        showError(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3232;
                color: white;
                padding: 10px 15px;
                border-radius: 4px;
                z-index: 10000;
                animation: fadeIn 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        },

        formatTimestamp(date) {
            return new Intl.DateTimeFormat('pl', {
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        },

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
    };

    // Inicjalizacja interfejsu
    ChatInterface.init();

    // Dodaj style dla animacji
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
    `;
    document.head.appendChild(style);
});