<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

$github_api = new \ClaudeChatPro\Includes\Api\Github_Api();

// Sprawdź połączenie z GitHubem
if (!$github_api->is_configured()) {
    ?>
    <div class="github-repositories">
        <div class="repo-header">
            <h1><span class="dashicons dashicons-github"></span> <?php _e('Repozytoria GitHub', 'claude-chat-pro'); ?></h1>
        </div>
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

$search = sanitize_text_field($_GET['search'] ?? '');
$sort = sanitize_text_field($_GET['sort'] ?? 'updated');
$direction = sanitize_text_field($_GET['direction'] ?? 'desc');
$page = max(1, intval($_GET['paged'] ?? 1));

try {
    $repositories = $github_api->get_user_repos([
        'sort' => $sort,
        'direction' => $direction,
        'per_page' => 20,
        'page' => $page
    ]);
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
<div class="github-repositories">
    <div class="repo-header">
        <h1><span class="dashicons dashicons-github"></span> <?php _e('Repozytoria GitHub', 'claude-chat-pro'); ?></h1>
    </div>
    <div class="repo-controls">
        <form method="get" class="repo-search-form">
            <input type="hidden" name="page" value="claude-chat-repositories">
            <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Szukaj w repozytoriach...', 'claude-chat-pro'); ?>" class="repo-search-input">
            <select name="sort" class="repo-sort-select">
                <option value="updated" <?php selected($sort, 'updated'); ?>><?php _e('Ostatnio aktualizowane', 'claude-chat-pro'); ?></option>
                <option value="created" <?php selected($sort, 'created'); ?>><?php _e('Ostatnio utworzone', 'claude-chat-pro'); ?></option>
                <option value="name" <?php selected($sort, 'name'); ?>><?php _e('Nazwa', 'claude-chat-pro'); ?></option>
                </select>
            <select name="direction" class="repo-sort-select">
                <option value="desc" <?php selected($direction, 'desc'); ?>><?php _e('Malejąco', 'claude-chat-pro'); ?></option>
                <option value="asc" <?php selected($direction, 'asc'); ?>><?php _e('Rosnąco', 'claude-chat-pro'); ?></option>
                </select>
            <button type="submit" class="button repo-search-btn"><span class="dashicons dashicons-search"></span> <?php _e('Filtruj', 'claude-chat-pro'); ?></button>
        </form>
    </div>
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php else: ?>
        <div class="repositories-grid">
            <?php foreach ($repositories as $repo): ?>
                <div class="repository-card" data-repo="<?php echo esc_attr($repo['name']); ?>">
                    <div class="repository-header">
                        <h3 class="repository-name">
                            <a href="<?php echo esc_url($repo['url']); ?>" target="_blank" rel="noopener noreferrer">
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
                            <?php echo esc_html(
                                sprintf(
                                    __('Ostatnia aktualizacja: %s', 'claude-chat-pro'),
                                    wp_date(get_option('date_format'), strtotime($repo['updated_at']))
                                )
                            ); ?>
                        </div>
                    </div>
                    <div class="repository-actions">
                        <button type="button" class="button view-repo" data-repo="<?php echo esc_attr($repo['name']); ?>" data-branch="<?php echo esc_attr($repo['default_branch']); ?>">
                            <span class="dashicons dashicons-visibility"></span> <?php _e('Podgląd', 'claude-chat-pro'); ?>
                        </button>
                        <a href="<?php echo esc_url($repo['url']); ?>" class="button" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-external"></span> <?php _e('Otwórz', 'claude-chat-pro'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
                    <div class="current-path"></div>
                </div>
                <div class="file-explorer"></div>
            </div>
            <div class="file-preview">
                <div class="file-preview-header">
                        <button type="button" class="button back-to-tree" style="display:none;margin-right:12px;">
                            <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Wróć do drzewa', 'claude-chat-pro'); ?> 
                        </button>
                    <h3 class="file-name"></h3>
                    <div class="file-actions">
                        <button type="button" class="button copy-content">
                                <span class="dashicons dashicons-clipboard"></span> <?php _e('Kopiuj', 'claude-chat-pro'); ?>
                        </button>
                        <button type="button" class="button download-file">
                                <span class="dashicons dashicons-download"></span> <?php _e('Pobierz', 'claude-chat-pro'); ?>
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
    <!-- Prism.js lokalnie -->
    <link rel="stylesheet" href="<?php echo CLAUDE_CHAT_PRO_PLUGIN_URL; ?>admin/css/vendor/prism.css">
    <script src="<?php echo CLAUDE_CHAT_PRO_PLUGIN_URL; ?>admin/js/vendor/prism.js"></script>
</div>