<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

$current_user = wp_get_current_user();
?>

<div class="wrap claude-chat-interface">
    <h1><?php _e('Claude Chat Pro', 'claude-chat-pro'); ?></h1>
    
    <div class="chat-container">
        <!-- Panel boczny z repozytoriami -->
        <div class="repo-sidebar">
            <h3><?php _e('Repozytoria GitHub', 'claude-chat-pro'); ?></h3>
            <div class="repo-search">
                <input type="text" id="repo-search" placeholder="<?php _e('Szukaj repozytoriów...', 'claude-chat-pro'); ?>">
            </div>
            <div id="repo-list" class="repo-list">
                <!-- Lista repozytoriów będzie generowana przez JavaScript -->
            </div>
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
                    </ul>
                </div>
            </div>

            <!-- Panel wprowadzania -->
            <div class="chat-input-panel">
                <!-- Przyciski akcji -->
                <div class="action-buttons">
                    <button type="button" id="attach-file" class="button">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Załącz plik', 'claude-chat-pro'); ?>
                    </button>
                    <button type="button" id="attach-repo" class="button">
                        <span class="dashicons dashicons-github"></span>
                        <?php _e('Kod z GitHub', 'claude-chat-pro'); ?>
                    </button>
                </div>

                <!-- Lista załączników -->
                <div id="attachments-list" class="attachments-list"></div>

                <!-- Pole wprowadzania -->
                <div class="input-container">
                    <textarea id="chat-input" 
                              placeholder="<?php _e('Napisz wiadomość...', 'claude-chat-pro'); ?>"
                              rows="3"></textarea>
                    <button type="button" id="send-message" class="button button-primary">
                        <span class="dashicons dashicons-send"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template dla wiadomości -->
<template id="message-template">
    <div class="chat-message {role}">
        <div class="message-header">
            <span class="message-author"></span>
            <span class="message-time"></span>
        </div>
        <div class="message-content"></div>
        <div class="message-actions"></div>
    </div>
</template>

<!-- Template dla bloku kodu -->
<template id="code-block-template">
    <div class="code-block">
        <div class="code-header">
            <span class="code-language"></span>
            <div class="code-actions">
                <button class="button copy-code" title="<?php _e('Kopiuj kod', 'claude-chat-pro'); ?>">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
                <button class="button download-code" title="<?php _e('Pobierz kod', 'claude-chat-pro'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </button>
            </div>
        </div>
        <pre><code></code></pre>
    </div>
</template>