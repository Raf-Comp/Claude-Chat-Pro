<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

$github_api = new \ClaudeChatPro\Includes\Api\Github_Api();

// Sprawdź połączenie z GitHubem
if (!$github_api->is_configured()) {
    ?>
    <div class="wrap">
        <h1><?php _e('Repozytoria GitHub', 'claude-chat-pro'); ?></h1>
        <div class="notice notice-error">
            <p>
                <?php 
                _e('API GitHub nie jest skonfigurowane. ', 'claude-chat-pro');
                printf(
                    __('Przejdź do <a href="%s">ustawień</a> aby skonfigurować token GitHub.', 'claude-chat-pro'),
                    admin_url('admin.php?page=claude-chat-settings')
                );
                ?>
            </p>
        </div>
    </div>
    <?php
    return;
}

// Parametry filtrowania i sortowania
$search = sanitize_text_field($_GET['search'] ?? '');
$sort = sanitize_text_field($_GET['sort'] ?? 'updated');
$direction = sanitize_text_field($_GET['direction'] ?? 'desc');
$page = max(1, intval($_GET['paged'] ?? 1));

try {
    // Pobierz repozytoria
    $repositories = $github_api->get_user_repos([
        'sort' => $sort,
        'direction' => $direction,
        'per_page' => 20,
        'page' => $page
    ]);

    // Filtruj repozytoria jeśli jest wyszukiwanie
    if (!empty($search)) {
        $repositories = array_filter($repositories, function($repo) use ($search) {
            return (
                stripos($repo['name'], $search) !== false ||
                stripos($repo['description'], $search) !== false
            );
        });
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<div class="wrap github-repositories">
    <h1 class="wp-heading-inline"><?php _e('Repozytoria GitHub', 'claude-chat-pro'); ?></h1>
    
    <!-- Informacje o użytkowniku i czasie -->
    <div class="user-info-bar">
        <div class="user-info">
            <span class="dashicons dashicons-admin-users"></span>
            <?php echo esc_html('Raf-Comp'); ?>
        </div>
        <div class="current-time">
            <span class="dashicons dashicons-clock"></span>
            <?php echo esc_html('2025-05-22 13:43:29'); ?> UTC
        </div>
    </div>

    <!-- Panel filtrowania i wyszukiwania -->
    <div class="tablenav top">
        <form method="get" class="search-form">
            <input type="hidden" name="page" value="claude-chat-repositories">
            
            <div class="alignleft actions">
                <input type="search" 
                       name="search" 
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php _e('Szukaj w repozytoriach...', 'claude-chat-pro'); ?>"
                       class="regular-text">
                
                <select name="sort" class="postform">
                    <option value="updated" <?php selected($sort, 'updated'); ?>>
                        <?php _e('Ostatnio aktualizowane', 'claude-chat-pro'); ?>
                    </option>
                    <option value="created" <?php selected($sort, 'created'); ?>>
                        <?php _e('Ostatnio utworzone', 'claude-chat-pro'); ?>
                    </option>
                    <option value="name" <?php selected($sort, 'name'); ?>>
                        <?php _e('Nazwa', 'claude-chat-pro'); ?>
                    </option>
                </select>
                
                <select name="direction" class="postform">
                    <option value="desc" <?php selected($direction, 'desc'); ?>>
                        <?php _e('Malejąco', 'claude-chat-pro'); ?>
                    </option>
                    <option value="asc" <?php selected($direction, 'asc'); ?>>
                        <?php _e('Rosnąco', 'claude-chat-pro'); ?>
                    </option>
                </select>
                
                <input type="submit" 
                       class="button" 
                       value="<?php _e('Filtruj', 'claude-chat-pro'); ?>">
            </div>
        </form>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php else: ?>
        <!-- Lista repozytoriów -->
        <div class="repositories-grid">
            <?php foreach ($repositories as $repo): ?>
                <div class="repository-card" data-repo="<?php echo esc_attr($repo['name']); ?>">
                    <div class="repository-header">
                        <h3 class="repository-name">
                            <a href="<?php echo esc_url($repo['url']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <?php echo esc_html($repo['name']); ?>
                            </a>
                        </h3>
                        <div class="repository-visibility">
                            <span class="visibility-badge <?php echo esc_attr($repo['visibility']); ?>">
                                <?php echo esc_html($repo['visibility']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="repository-description">
                        <?php echo esc_html($repo['description'] ?: __('Brak opisu', 'claude-chat-pro')); ?>
                    </div>

                    <div class="repository-meta">
                        <?php if ($repo['language']): ?>
                            <div class="meta-item">
                                <span class="dashicons dashicons-editor-code"></span>
                                <?php echo esc_html($repo['language']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php 
                            echo esc_html(
                                sprintf(
                                    __('Ostatnia aktualizacja: %s', 'claude-chat-pro'),
                                    wp_date(
                                        get_option('date_format'),
                                        strtotime($repo['updated_at'])
                                    )
                                )
                            ); 
                            ?>
                        </div>
                    </div>

                    <div class="repository-actions">
                        <button type="button" 
                                class="button view-repo" 
                                data-repo="<?php echo esc_attr($repo['name']); ?>"
                                data-branch="<?php echo esc_attr($repo['default_branch']); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('Podgląd', 'claude-chat-pro'); ?>
                        </button>
                        <a href="<?php echo esc_url($repo['url']); ?>" 
                           class="button" 
                           target="_blank" 
                           rel="noopener noreferrer">
                            <span class="dashicons dashicons-external"></span>
                            <?php _e('Otwórz', 'claude-chat-pro'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal podglądu repozytorium -->
<div id="repository-preview-modal" class="repository-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"></h2>
            <button type="button" class="modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="repository-browser">
                <div class="browser-header">
                    <select class="branch-selector"></select>
                    <div class="current-path"></div>
                </div>
                <div class="file-explorer"></div>
            </div>
            <div class="file-preview">
                <div class="file-preview-header">
                    <h3 class="file-name"></h3>
                    <div class="file-actions">
                        <button type="button" class="button copy-content">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Kopiuj', 'claude-chat-pro'); ?>
                        </button>
                        <button type="button" class="button download-file">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Pobierz', 'claude-chat-pro'); ?>
                        </button>
                    </div>
                </div>
                <div class="file-content">
                    <pre><code></code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.github-repositories {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.user-info-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 10px 15px;
    margin: 15px 0;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.user-info,
.current-time {
    display: flex;
    align-items: center;
    gap: 8px;
}

.repositories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.repository-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.repository-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.repository-name {
    margin: 0;
    font-size: 1.2em;
}

.visibility-badge {
    font-size: 0.8em;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
}

.visibility-badge.public {
    background: #e6ffed;
    color: #28a745;
}

.visibility-badge.private {
    background: #ffeef0;
    color: #d73a49;
}

.repository-description {
    color: #666;
    font-size: 0.9em;
    min-height: 40px;
}

.repository-meta {
    display: flex;
    gap: 15px;
    font-size: 0.9em;
    color: #666;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.repository-actions {
    display: flex;
    gap: 10px;
    margin-top: auto;
    padding-top: 10px;
}

/* Modal */
.repository-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 999999;
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    width: 90%;
    max-width: 1200px;
    height: 80vh;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #ddd;
}

.modal-title {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
}

.modal-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.repository-browser {
    width: 300px;
    border-right: 1px solid #ddd;
    display: flex;
    flex-direction: column;
}

.browser-header {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.file-explorer {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.file-preview {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.file-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.file-content {
    flex: 1;
    overflow: auto;
    padding: 10px;
}

.file-content pre {
    margin: 0;
}

/* Responsywność */
@media screen and (max-width: 782px) {
    .repositories-grid {
        grid-template-columns: 1fr;
    }

    .modal-content {
        width: 95%;
        height: 95vh;
    }

    .modal-body {
        flex-direction: column;
    }

    .repository-browser {
        width: 100%;
        height: 200px;
        border-right: none;
        border-bottom: 1px solid #ddd;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const modal = $('#repository-preview-modal');
    let currentRepo = null;
    let currentPath = '';

    // Otwieranie modalu podglądu
    $('.view-repo').on('click', function() {
        const repoName = $(this).data('repo');
        const defaultBranch = $(this).data('branch');
        currentRepo = repoName;
        currentPath = '';
        
        openRepositoryPreview(repoName, defaultBranch);
    });

    // Zamykanie modalu
    $('.modal-close').on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target === modal[0]) {
            modal.hide();
        }
    });

    // Otwieranie podglądu repozytorium
    function openRepositoryPreview(repoName, defaultBranch) {
        modal.find('.modal-title').text(repoName);
        
        // Pobierz gałęzie repozytorium
        loadBranches(repoName, defaultBranch);
        
        // Pobierz zawartość głównego katalogu
        loadDirectory(repoName, '', defaultBranch);
        
        modal.show();
    }

    // Ładowanie gałęzi repozytorium
    function loadBranches(repoName, defaultBranch) {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'claude_chat_get_branches',
                repo: repoName,
                nonce: '<?php echo wp_create_nonce("claude_chat_github"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const select = modal.find('.branch-selector');
                    select.empty();
                    
                    response.data.forEach(branch => {
                        select.append(
                            $('<option>', {
                                value: branch.name,
                                text: branch.name,
                                selected: branch.name === defaultBranch
                            })
                        );
                    });
                    
                    // Obsługa zmiany gałęzi
                    select.off('change').on('change', function() {
                        loadDirectory(repoName, '', $(this).val());
                    });
                }
            }
        });
    }

    // Ładowanie zawartości katalogu
    function loadDirectory(repoName, path, branch) {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'claude_chat_get_directory',
                repo: repoName,
                path: path,
                branch: branch,
                nonce: '<?php echo wp_create_nonce("claude_chat_github"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderFileExplorer(response.data, path);
                }
            }
        });
    }

    // Renderowanie eksploratora plików
    function renderFileExplorer(items, path) {
        const explorer = modal.find('.file-explorer');
        explorer.empty();

        // Dodaj link do katalogu nadrzędnego
        if (path) {
            const parentPath = path.split('/').slice(0, -1).join('/');
            explorer.append(
                $('<div>', {
                    class: 'directory-item parent',
                    html: '<span class="dashicons dashicons-arrow-up-alt2"></span> ..',
                    click: () => loadDirectory(currentRepo, parentPath, modal.find('.branch-selector').val())
                })
            );
        }

        // Sortuj elementy (katalogi pierwsze, potem pliki)
        items.sort((a, b) => {
            if (a.type === b.type) return a.name.localeCompare(b.name);
            return a.type === 'dir' ? -1 : 1;
        });

        // Renderuj elementy
        items.forEach(item => {
            const icon = item.type === 'dir' ? 
                'dashicons-portfolio' : 
                'dashicons-media-text';
            
            explorer.append(
                $('<div>', {
                    class: `explorer-item ${item.type}`,
                    html: `
                        <span class="dashicons ${icon}"></span>
                        <span class="item-name">${item.name}</span>
                    `,
                    click: () => {
                        if (item.type === 'dir') {
                            loadDirectory(
                                currentRepo,
                                item.path,
                                modal.find('.branch-selector').val()
                            );
                        } else {
                            loadFile(
                                currentRepo,
                                item.path,
                                modal.find('.branch-selector').val()
                            );
                        }
                    }
                })
            );
        });

        // Aktualizuj ścieżkę
        modal.find('.current-path').text(path || '/');
    }

    // Ładowanie zawartości pliku
    function loadFile(repoName, path, branch) {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'claude_chat_get_file',
                repo: repoName,
                path: path,
                branch: branch,
                nonce: '<?php echo wp_create_nonce("claude_chat_github"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderFilePreview(path, response.data);
                }
            }
        });
    }

    // Renderowanie podglądu pliku
    function renderFilePreview(path, content) {
        const preview = modal.find('.file-preview');
        preview.find('.file-name').text(path.split('/').pop());
        
        const codeElement = preview.find('code');
        codeElement.text(content);
        
        // Wykryj język na podstawie rozszerzenia pliku
        const extension = path.split('.').pop().toLowerCase();
        codeElement.attr('class', `language-${extension}`);
        
        // Podświetl składnię
        hljs.highlightElement(codeElement[0]);
        
        // Obsługa przycisków akcji
        preview.find('.copy-content').off('click').on('click', () => {
            navigator.clipboard.writeText(content).then(() => {
                alert('<?php _e('Skopiowano do schowka!', 'claude-chat-pro'); ?>');
            });
        });
        
        preview.find('.download-file').off('click').on('click', () => {
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = path.split('/').pop();
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        });
    }
});
</script>