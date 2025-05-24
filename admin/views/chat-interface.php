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
            <div class="repo-header">
                <h3>Repozytoria</h3>
            </div>
            <div class="repo-list">
                <?php
                $repositories = get_option('claude_chat_repositories', array());
                if (!empty($repositories)) {
                    foreach ($repositories as $repo) {
                        printf(
                            '<div class="repo-item" data-repo-id="%s">
                                <span class="repo-name">%s</span>
                                <span class="repo-path">%s</span>
                            </div>',
                            esc_attr($repo['id']),
                            esc_html($repo['name']),
                            esc_html($repo['path'])
                        );
                    }
                } else {
                    echo '<div class="no-repos">Brak dodanych repozytoriów</div>';
                }
                ?>
            </div>
        </div>

        <!-- Główny panel czatu -->
        <div class="chat-main">
            <!-- Nagłówek czatu -->
            <div class="chat-header">
                <h2>Czat z Asystentem</h2>
            </div>

            <!-- Panel wiadomości -->
            <div class="chat-messages">
                <div class="welcome-message">
                    <h2><?php _e('Witaj', 'claude-chat-pro'); ?> <?php echo esc_html($current_user->display_name); ?>!</h2>
                    <p><?php _e('Rozpocznij rozmowę z Claude AI. Możesz:', 'claude-chat-pro'); ?></p>
                    <ul>
                        <li><?php _e('Zadawać pytania i otrzymywać odpowiedzi', 'claude-chat-pro'); ?></li>
                        <li><?php _e('Załączać pliki do analizy', 'claude-chat-pro'); ?></li>
                        <li><?php _e('Wybierać fragmenty kodu z repozytoriów', 'claude-chat-pro'); ?></li>
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
                    <button type="button" id="add-repo-button" class="add-repo-button">
                        <span class="dashicons dashicons-database"></span>
                        Dodaj repozytorium
                    </button>
                    <button type="button" id="upload-button">
                        <span class="dashicons dashicons-upload"></span>
                        Załącz plik
                    </button>
                    <input type="file" id="file-upload" multiple accept=".txt,.js,.php,.css,.html,.json" style="display: none;">
                    <button type="button" 
                            id="clear-chat" 
                            class="button"
                            title="<?php _e('Wyczyść historię czatu', 'claude-chat-pro'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Wyczyść', 'claude-chat-pro'); ?>
                    </button>
                    <label class="dark-mode-toggle">
                        <input type="checkbox" id="dark-mode-toggle">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Tryb ciemny</span>
                    </label>
                </div>

                <!-- Lista załączników -->
                <div class="attachments-list"></div>

                <!-- Pole wprowadzania -->
                <div class="input-container">
                    <textarea id="message-input" 
                              placeholder="<?php _e('Napisz wiadomość...', 'claude-chat-pro'); ?>"
                              rows="3"
                              <?php echo !$claude_api_configured ? 'disabled' : ''; ?>></textarea>
                    <button type="button" 
                            id="send-message" 
                            class="button button-primary"
                            <?php echo !$claude_api_configured ? 'disabled' : ''; ?>
                            title="<?php _e('Wyślij wiadomość (Enter)', 'claude-chat-pro'); ?>">
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
                
                <div class="input-help">
                    <small>
                        <?php _e('Naciśnij Enter aby wysłać, Shift+Enter dla nowej linii. Obsługiwane formaty plików: txt, md, js, php, css, html, json', 'claude-chat-pro'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Szablony dla dynamicznych elementów -->
<template id="typing-indicator-template">
    <div class="typing-indicator">
        <div class="spinner"></div>
        <span>Asystent pisze...</span>
    </div>
</template>

<template id="notification-template">
    <div class="notification">
        <span class="notification-message"></span>
    </div>
</template>

<script>
// Przekaż dane do JavaScript
window.claudeChatConfig = {
    apiConfigured: <?php echo $claude_api_configured ? 'true' : 'false'; ?>,
    maxFileSize: <?php echo wp_max_upload_size(); ?>,
    strings: {
        errorApiNotConfigured: '<?php echo esc_js(__('Skonfiguruj najpierw klucz API Claude w ustawieniach', 'claude-chat-pro')); ?>',
        errorFileTooLarge: '<?php echo esc_js(__('Plik jest za duży. Maksymalny rozmiar:', 'claude-chat-pro')); ?>',
        errorFileRead: '<?php echo esc_js(__('Nie udało się odczytać pliku', 'claude-chat-pro')); ?>',
        confirmClearChat: '<?php echo esc_js(__('Czy na pewno chcesz wyczyścić historię czatu?', 'claude-chat-pro')); ?>'
    }
};

var claudeChatPro = {
    ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('claude_chat_nonce'); ?>',
    i18n: {
        sendError: '<?php echo esc_js(__('Wystąpił błąd podczas wysyłania wiadomości.', 'claude-chat-pro')); ?>',
        invalidFile: '<?php echo esc_js(__('Nieprawidłowy typ pliku.', 'claude-chat-pro')); ?>',
        maxFileSize: '<?php echo esc_js(__('Plik jest zbyt duży. Maksymalny rozmiar to 5MB.', 'claude-chat-pro')); ?>'
    }
};
</script>