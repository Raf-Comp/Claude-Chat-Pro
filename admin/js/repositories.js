// admin/js/repositories.js
console.log('Ładuje się repositories.js');
// Nowoczesny JS do obsługi panelu repozytoriów GitHub w Claude Chat Pro
jQuery(document).ready(function($) {
    // Inicjalizacja modala repozytorium
    function initRepositoryModal() {
        const $modal = $('#repository-preview-modal');
        const $closeBtn = $modal.find('.modal-close');
        $closeBtn.on('click', function() {
            $modal.fadeOut(200);
            $modal.find('.repository-browser').show();
            $modal.find('.file-preview').removeClass('full-width');
        });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $modal.fadeOut(200);
                $modal.find('.repository-browser').show();
                $modal.find('.file-preview').removeClass('full-width');
            }
        });

        // Doładowanie lokalnego Prism.js i jego pluginów
        if (!window.Prism) {
            console.log('Inicjalizacja Prism.js...');
            const script = document.createElement('script');
            script.src = CLAUDE_CHAT_PRO_PLUGIN_URL + 'admin/js/vendor/prism.js';
            script.onload = function() {
                console.log('Prism.js załadowany pomyślnie');
                // Konfiguracja Prism.js
                Prism.manual = true;
                
                // Dodaj style dla numeracji linii
                const style = document.createElement('style');
                style.textContent = `
                    .line-numbers .line-numbers-rows {
                        position: absolute;
                        pointer-events: none;
                        top: 0;
                        font-size: 100%;
                        left: -3.8em;
                        width: 3em;
                        letter-spacing: -1px;
                        border-right: 1px solid #999;
                        user-select: none;
                    }
                    .line-numbers .line-numbers-rows > span {
                        display: block;
                        counter-increment: linenumber;
                    }
                    .line-numbers .line-numbers-rows > span:before {
                        content: counter(linenumber);
                        color: #999;
                        display: block;
                        padding-right: 0.8em;
                        text-align: right;
                    }
                `;
                document.head.appendChild(style);
                console.log('Style Prism.js dodane');
            };
            document.head.appendChild(script);
            
            // Dodaj style Prism.js
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = CLAUDE_CHAT_PRO_PLUGIN_URL + 'admin/css/vendor/prism.css';
            document.head.appendChild(link);
        }

        // Funkcja pomocnicza do sprawdzania czy język jest obsługiwany
        function isLanguageSupported(lang) {
            return Prism.languages && Prism.languages[lang];
        }
    }

    // Otwieranie modala i ładowanie drzewa repo
    $(document).on('click', '.view-repo', function() {
        const repo = $(this).data('repo');
        openRepositoryModal(repo, '');
    });

    function openRepositoryModal(repo, path) {
        const $modal = $('#repository-preview-modal');
        $modal.fadeIn(200);
        loadRepositoryTree(repo, path);
    }

    // Ładowanie drzewa repozytorium
    function loadRepositoryTree(repo, path) {
        const $explorer = $('.file-explorer');
        $explorer.html('<div class="repo-loading">Ładowanie...</div>');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'claude_get_repo_tree',
                repo,
                path,
                nonce: window.claudeRepo.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderRepositoryTree(response.data, repo, path);
                } else {
                    $explorer.html('<div class="repo-error">Błąd: ' + (response.data.message || 'Nie udało się pobrać drzewa repozytorium') + '</div>');
                }
            },
            error: function() {
                $explorer.html('<div class="repo-error">Błąd połączenia z API</div>');
            }
        });
    }

    // Renderowanie drzewa repozytorium
    function renderRepositoryTree(items, repo, path) {
        const $explorer = $('.file-explorer');
        // Breadcrumbs
        const $currentPath = $('.current-path');
        let parts = path ? path.split('/') : [];
        let breadcrumbs = '<span class="breadcrumb" data-path="">repo</span>';
        let fullPath = '';
        parts.forEach((part, idx) => {
            fullPath += (fullPath ? '/' : '') + part;
            breadcrumbs += ' / <span class="breadcrumb" data-path="' + fullPath + '">' + part + '</span>';
        });
        $currentPath.html(breadcrumbs);

        let html = '<ul class="repo-tree">';
        items.forEach(item => {
            if (item.type === 'dir') {
                html += `<li class="repo-dir" data-path="${item.path}"><span class="dashicons dashicons-portfolio"></span> ${item.name}</li>`;
            } else {
                html += `<li class="repo-file" data-path="${item.path}"><span class="dashicons dashicons-media-text"></span> ${item.name}</li>`;
            }
        });
        html += '</ul>';
        $explorer.html(html);
    }

    // Klik na katalog lub plik w drzewie
    $(document).on('click', '.repo-dir', function() {
        const repo = $('.view-repo').data('repo');
        const path = $(this).data('path');
        loadRepositoryTree(repo, path);
    });
    $(document).on('click', '.repo-file', function() {
        const repo = $('.view-repo').data('repo');
        const path = $(this).data('path');
        showFilePreview(repo, path);
    });

    // Obsługa przycisku "Wróć do drzewa"
    $(document).on('click', '.back-to-tree', function() {
        const $modal = $('#repository-preview-modal');
        $modal.find('.repository-browser').show();
        $modal.find('.file-preview').removeClass('full-width');
        $modal.find('.back-to-tree').hide();
    });

    // Obsługa kliknięcia w breadcrumb
    $(document).on('click', '.breadcrumb', function() {
        const repo = $('.view-repo').data('repo');
        const path = $(this).data('path');
        loadRepositoryTree(repo, path);
    });

    // Ładowanie zawartości pliku
    function showFilePreview(repo, path) {
        const $modal = $('#repository-preview-modal');
        const $filePreview = $modal.find('.file-preview');
        const $repoBrowser = $modal.find('.repository-browser');
        const $backBtn = $modal.find('.back-to-tree');
        
        // Ukryj drzewo, rozciągnij podgląd, pokaż przycisk
        $repoBrowser.hide();
        $filePreview.addClass('full-width');
        $backBtn.show();

        if (!$filePreview.length) {
            console.error('Nie znaleziono elementu .file-preview!');
            return;
        }
        
        $filePreview.show();
        const $pre = $filePreview.find('.file-content pre');
        $pre.addClass('repo-loading').text('Ładowanie...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'claude_get_file_content',
                repo,
                path,
                nonce: window.claudeRepo.nonce
            },
            success: function(response) {
                console.log('Odpowiedź AJAX:', response);
                
                if (!response) {
                    console.error('Brak odpowiedzi z serwera!');
                    handleFileError({ message: 'Brak odpowiedzi z serwera' });
                    return;
                }
                
                if (response.success && response.data && response.data.content) {
                    console.log('Zawartość pliku otrzymana, długość:', response.data.content.length);
                    showFileContent(response.data.content, path);
                } else {
                    console.error('Nieprawidłowa odpowiedź:', response);
                    handleFileError({ 
                        message: response.data?.message || 'Nie udało się pobrać pliku'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd AJAX:', { status, error, xhr });
                handleFileError({ 
                    message: 'Błąd połączenia z API: ' + (error || status)
                });
            }
        });
    }

    // Funkcja do escapowania kodu do HTML
    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Funkcja do wyświetlania zawartości pliku
    function showFileContent(content, filePath) {
        console.log('Rozpoczynam showFileContent:', { filePath, contentLength: content?.length });

        const $fileContent = $('.file-content');
        if ($fileContent.length === 0) {
            console.error('Nie znaleziono elementu .file-content!');
            handleFileError({ message: 'Brak kontenera na kod (.file-content)' });
            return;
        }

        let $pre = $fileContent.find('pre');
        if ($pre.length === 0) {
            $pre = $('<pre></pre>');
            $fileContent.empty().append($pre);
            console.log('Utworzono brakujący element <pre>');
        }

        let $code = $pre.find('code');
        if ($code.length === 0) {
            $code = $('<code></code>');
            $pre.empty().append($code);
            console.log('Utworzono brakujący element <code>');
        }

        // Rozpoznawanie języka na podstawie rozszerzenia
        const extToLang = {
            'js': 'javascript',
            'jsx': 'jsx',
            'ts': 'typescript',
            'tsx': 'tsx',
            'php': 'php',
            'html': 'markup',
            'htm': 'markup',
            'css': 'css',
            'json': 'json',
            'yml': 'yaml',
            'yaml': 'yaml',
            'py': 'python',
            'java': 'java',
            'c': 'c',
            'cpp': 'cpp',
            'h': 'c',
            'md': 'markdown',
            'sql': 'sql',
            'sh': 'bash',
            'bash': 'bash',
            'txt': 'text'
        };

        // Pobierz rozszerzenie pliku
        const extension = filePath.split('.').pop().toLowerCase();
        console.log('Rozszerzenie pliku:', extension);

        // Sprawdź czy język jest obsługiwany
        let lang = extToLang[extension];
        console.log('Wykryty język:', lang);

        if (!lang) {
            console.warn('Nieznane rozszerzenie pliku:', extension);
            // Dla nieznanych rozszerzeń, spróbuj wykryć język na podstawie zawartości
            if (content.includes('<?php')) {
                lang = 'php';
            } else if (content.includes('function') && content.includes('{')) {
                lang = 'javascript';
            } else {
                lang = 'text';
            }
            console.log('Wykryty język na podstawie zawartości:', lang);
        }

        // Ustaw klasę języka
        $code.removeClass().addClass('language-' + lang);
        $pre.removeClass().addClass('line-numbers');
        $code.text(''); // czyść

        if (!content) {
            handleFileError({ message: 'Brak zawartości pliku' });
            return;
        }

        try {
            const escapedContent = escapeHtml(content);
            $code.html(escapedContent);

            if (window.Prism && $code[0]) {
                console.log('Podświetlam kod dla języka:', lang);
                Prism.highlightElement($code[0]);
            }

            $('.file-name').text(filePath.split('/').pop());

            $('.copy-content').off('click').on('click', function() {
                navigator.clipboard.writeText(content).then(() => {
                    $(this).addClass('copied');
                    setTimeout(() => $(this).removeClass('copied'), 1200);
                });
            });

            $('.download-file').off('click').on('click', function() {
                const blob = new Blob([content], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filePath.split('/').pop();
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            });
        } catch (error) {
            console.error('Błąd podczas podświetlania kodu:', error);
            handleFileError({ message: 'Błąd podczas wyświetlania kodu: ' + error.message });
        }
    }

    // Funkcja do obsługi błędów
    function handleFileError(error) {
        console.error('Wystąpił błąd:', error);
        const $filePreview = $('.file-preview');
        const $pre = $filePreview.find('.file-content pre');
        
        $pre.removeClass('repo-loading')
            .html('<div class="error-message">Błąd: ' + (error.message || 'Nie udało się pobrać pliku') + '</div>');
    }

    // Inicjalizacja na starcie
    initRepositoryModal();
}); 