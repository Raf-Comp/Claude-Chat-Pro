<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

$current_user = wp_get_current_user();

// Sprawdź czy API są skonfigurowane
$claude_api_configured = !empty(get_option('claude_api_key'));
$github_api_configured = !empty(get_option('github_token'));
?>

<div class="wrap claude-chat-interface">
    <h1><?php _e('Claude Chat Pro', 'claude-chat-pro'); ?></h1>
    
    <?php if (!$claude_api_configured): ?>
        <div class="notice notice-warning">
            <p>
                <?php 
                _e('Klucz API Claude nie jest skonfigurowany. ', 'claude-chat-pro');
                printf(
                    __('Przejdź do <a href="%s">ustawień</a> aby skonfigurować API.', 'claude-chat-pro'),
                    admin_url('admin.php?page=claude-chat-settings')
                );
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="chat-container">
        <!-- Panel boczny z repozytoriami -->
        <div class="repo-sidebar">
            <h3><?php _e('Repozytoria GitHub', 'claude-chat-pro'); ?></h3>
            
            <?php if (!$github_api_configured): ?>
                <div class="notice notice-info inline">
                    <p><?php _e('GitHub API nie jest skonfigurowane', 'claude-chat-pro'); ?></p>
                </div>
            <?php else: ?>
                <div class="repo-search">
                    <input type="text" 
                           id="repo-search" 
                           placeholder="<?php _e('Szukaj repozytoriów...', 'claude-chat-pro'); ?>"
                           class="regular-text">
                </div>
                <div id="repo-list" class="repo-list">
                    <div class="loading-repos">
                        <div class="spinner"></div>
                        <p><?php _e('Ładowanie repozytoriów...', 'claude-chat-pro'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Główny panel czatu -->
        <div class="chat-main">
            <!-- Historia konwersacji -->
            <div id="chat-messages" class="chat-messages">
                <div class="welcome-message">
                    <h2><?php _e('Witaj', 'claude-chat-pro'); ?> <?php echo esc_html($current_user->display_name); ?>!</h2>
                    <p><?php _e('Rozpocznij rozmowę z Claude AI. Możesz:', 'claude-chat-pro'); ?></p>
                    <ul>
                        <li><?php _e('Zadawać pytania i otrzymywać odpowiedzi', 'claude-chat-pro'); ?></li>
                        <li><?php _e('Załączać pliki do analizy', 'claude-chat-pro'); ?></li>
                        <li><?php _e('Wybierać fragmenty kodu z repozytoriów GitHub', 'claude-chat-pro'); ?></li>
                        <li><?php _e('Eksportować odpowiedzi do plików', 'claude-chat-pro'); ?></li>
                    </ul>
                    
                    <?php if (!$claude_api_configured): ?>
                        <div class="api-warning">
                            <p><strong><?php _e('Uwaga:', 'claude-chat-pro'); ?></strong> 
                            <?php _e('Aby rozpocząć czat, musisz najpierw skonfigurować klucz API Claude.', 'claude-chat-pro'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel wprowadzania -->
            <div class="chat-input-panel">
                <!-- Przyciski akcji -->
                <div class="action-buttons">
                    <button type="button" 
                            id="attach-file" 
                            class="button"
                            title="<?php _e('Załącz plik tekstowy (maks. 1MB)', 'claude-chat-pro'); ?>">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Załącz plik', 'claude-chat-pro'); ?>
                    </button>
                    
                    <button type="button" 
                            id="attach-repo" 
                            class="button"
                            <?php echo !$github_api_configured ? 'disabled' : ''; ?>
                            title="<?php _e('Załącz kod z repozytorium GitHub', 'claude-chat-pro'); ?>">
                        <span class="dashicons dashicons-github"></span>
                        <?php _e('Kod z GitHub', 'claude-chat-pro'); ?>
                    </button>
                    
                    <button type="button" 
                            id="clear-chat" 
                            class="button"
                            title="<?php _e('Wyczyść historię czatu', 'claude-chat-pro'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Wyczyść', 'claude-chat-pro'); ?>
                    </button>
                </div>

                <!-- Lista załączników -->
                <div id="attachments-list" class="attachments-list"></div>

                <!-- Pole wprowadzania -->
                <div class="input-container">
                    <textarea id="chat-input" 
                              placeholder="<?php _e('Napisz wiadomość...', 'claude-chat-pro'); ?>"
                              rows="3"
                              <?php echo !$claude_api_configured ? 'disabled' : ''; ?>></textarea>
                    <button type="button" 
                            id="send-message" 
                            class="button button-primary"
                            <?php echo !$claude_api_configured ? 'disabled' : ''; ?>
                            title="<?php _e('Wyślij wiadomość (Enter)', 'claude-chat-pro'); ?>">
                        <span class="dashicons dashicons-send"></span>
                    </button>
                </div>
                
                <div class="input-help">
                    <small>
                        <?php _e('Naciśnij Enter aby wysłać, Shift+Enter dla nowej linii. Obsługiwane formaty plików: txt, md, js, php, css, html, json, xml, py, java, cpp, c, cs, rb, go, rs, sql', 'claude-chat-pro'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template dla wiadomości -->
<template id="message-template">
    <div class="chat-message">
        <div class="message-header">
            <span class="message-author"></span>
            <span class="message-time"></span>
        </div>
        <div class="message-content"></div>
        <div class="message-actions">
            <button type="button" class="button-link save-message" title="<?php _e('Zapisz wiadomość', 'claude-chat-pro'); ?>">
                <span class="dashicons dashicons-download"></span>
            </button>
        </div>
    </div>
</template>

<!-- Template dla bloku kodu -->
<template id="code-block-template">
    <div class="code-block">
        <div class="code-header">
            <span class="code-language"></span>
            <div class="code-actions">
                <button type="button" class="button copy-code" title="<?php _e('Kopiuj kod', 'claude-chat-pro'); ?>">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
                <button type="button" class="button download-code" title="<?php _e('Pobierz kod', 'claude-chat-pro'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </button>
            </div>
        </div>
        <pre><code></code></pre>
    </div>
</template>

<script>
// Przekaż dane do JavaScript
window.claudeChatConfig = {
    apiConfigured: <?php echo $claude_api_configured ? 'true' : 'false'; ?>,
    githubConfigured: <?php echo $github_api_configured ? 'true' : 'false'; ?>,
    maxFileSize: <?php echo wp_max_upload_size(); ?>,
    strings: {
        errorApiNotConfigured: '<?php echo esc_js(__('Skonfiguruj najpierw klucz API Claude w ustawieniach', 'claude-chat-pro')); ?>',
        errorFileTooLarge: '<?php echo esc_js(__('Plik jest za duży. Maksymalny rozmiar:', 'claude-chat-pro')); ?>',
        errorFileRead: '<?php echo esc_js(__('Nie udało się odczytać pliku', 'claude-chat-pro')); ?>',
        confirmClearChat: '<?php echo esc_js(__('Czy na pewno chcesz wyczyścić historię czatu?', 'claude-chat-pro')); ?>',
        githubNotConfigured: '<?php echo esc_js(__('GitHub API nie jest skonfigurowane', 'claude-chat-pro')); ?>'
    }
};
</script>