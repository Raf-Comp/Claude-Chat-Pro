<?php
// Bezpośredni dostęp do pliku jest zabroniony
if (!defined('ABSPATH')) {
    exit;
}

use ClaudeChatPro\Includes\Database\Settings_DB;
$api_key = Settings_DB::get('claude_api_key', '');
$github_token = Settings_DB::get('claude_github_token', '');
$default_model = Settings_DB::get('claude_default_model', 'claude-3-opus-20240229');
$auto_save = Settings_DB::get('claude_auto_save_history', 1);
$max_file_size = Settings_DB::get('claude_max_file_size', 10);
$debug_mode = Settings_DB::get('claude_debug_mode', 0);
$enable_github = Settings_DB::get('claude_enable_github', 1);
$theme = Settings_DB::get('claude_theme', 'light');
$allowed_file_extensions = Settings_DB::get('claude_allowed_file_extensions', 'txt,pdf,php,js,css,html,json,md');
$prompt_templates = Settings_DB::get('claude_prompt_templates', []);
$auto_purge_enabled = Settings_DB::get('claude_auto_purge_enabled', false);
$auto_purge_days = Settings_DB::get('claude_auto_purge_days', 30);

// Załaduj style/skrypty Claude
wp_enqueue_style('claude-settings', CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/settings.css', [], CLAUDE_CHAT_PRO_VERSION);
wp_enqueue_script('claude-settings', CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/settings.js', ['jquery'], CLAUDE_CHAT_PRO_VERSION, true);
wp_localize_script('claude-settings', 'claudeSettings', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('claude_chat_settings'),
    'saving' => __('Zapisywanie...', 'claude-chat-pro'),
    'saved' => __('Zapisano!', 'claude-chat-pro'),
    'error' => __('Wystąpił błąd!', 'claude-chat-pro')
]);
?>
<div class="claude-admin-container">
    <div class="claude-header">
        <div class="claude-header-content">
            <h1><?php _e('Claude Chat Pro', 'claude-chat-pro'); ?></h1>
            <p class="claude-version"><?php echo 'v' . CLAUDE_CHAT_PRO_VERSION; ?></p>
        </div>
        <div class="claude-header-actions">
            <a href="<?php echo admin_url('admin.php?page=claude-chat-pro'); ?>" class="claude-button claude-button-outlined">
                <span class="dashicons dashicons-format-chat"></span>
                <?php _e('Otwórz czat', 'claude-chat-pro'); ?>
            </a>
            <a href="https://anthropic.com/claude" target="_blank" class="claude-button claude-button-text">
                <span class="dashicons dashicons-external"></span>
                <?php _e('O Claude AI', 'claude-chat-pro'); ?>
            </a>
        </div>
    </div>

    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="claude-notice claude-notice-success">
            <span class="claude-notice-icon dashicons dashicons-yes-alt"></span>
            <div class="claude-notice-content">
                <p class="claude-notice-title"><?php _e('Ustawienia zapisane pomyślnie', 'claude-chat-pro'); ?></p>
                <p class="claude-notice-message"><?php _e('Wszystkie zmiany zostały zapisane.', 'claude-chat-pro'); ?></p>
            </div>
            <button type="button" class="claude-notice-dismiss"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
    <?php endif; ?>

    <div class="claude-settings-container">
        <div class="claude-settings-sidebar">
            <div class="claude-sidebar-header">
                <div class="claude-sidebar-avatar">
                    <img src="<?php echo CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/images/logo-claude.png'; ?>" alt="Claude AI">
                </div>
                <div class="claude-sidebar-info">
                    <p class="claude-sidebar-title"><?php _e('Claude AI', 'claude-chat-pro'); ?></p>
                    <p class="claude-sidebar-desc"><?php _e('by Anthropic', 'claude-chat-pro'); ?></p>
                </div>
            </div>
            <ul class="claude-settings-tabs">
                <li class="claude-tab-item active" data-tab="claude-settings">
                    <span class="claude-tab-icon dashicons dashicons-admin-generic"></span>
                    <span class="claude-tab-text"><?php _e('Claude API', 'claude-chat-pro'); ?></span>
                </li>
                <li class="claude-tab-item" data-tab="models-settings">
                    <span class="claude-tab-icon dashicons dashicons-superhero"></span>
                    <span class="claude-tab-text"><?php _e('Modele AI', 'claude-chat-pro'); ?></span>
                </li>
                <li class="claude-tab-item" data-tab="repositories-settings">
                    <span class="claude-tab-icon dashicons dashicons-code-standards"></span>
                    <span class="claude-tab-text"><?php _e('Repozytoria', 'claude-chat-pro'); ?></span>
                </li>
                <li class="claude-tab-item" data-tab="general-settings">
                    <span class="claude-tab-icon dashicons dashicons-admin-settings"></span>
                    <span class="claude-tab-text"><?php _e('Ogólne', 'claude-chat-pro'); ?></span>
                </li>
            </ul>
            <div class="claude-sidebar-footer">
                <p><?php _e('Ostatnia aktualizacja modeli:', 'claude-chat-pro'); ?><br><strong><?php echo esc_html(Settings_DB::get('claude_models_last_update', __('Nigdy', 'claude-chat-pro'))); ?></strong></p>
                <button type="button" id="refresh-models" class="claude-button claude-button-sm">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Odśwież', 'claude-chat-pro'); ?>
                </button>
            </div>
        </div>
        <div class="claude-settings-content">
            <form method="post" action="" id="claude-settings-form">
                <?php wp_nonce_field('claude_settings_save', 'claude_settings_nonce'); ?>
                <input type="hidden" name="action" value="save_claude_settings">
                <div id="claude-settings" class="claude-tab-content active">
                    <div class="claude-settings-card">
                        <div class="claude-card-header">
                            <h2><?php _e('Ustawienia Claude API', 'claude-chat-pro'); ?></h2>
                            <p class="claude-card-description"><?php _e('Skonfiguruj połączenie z Anthropic Claude API do integracji czatu.', 'claude-chat-pro'); ?></p>
                        </div>
                        <div class="claude-card-body">
                            <div class="claude-field-row">
                                <div class="claude-field-label">
                                    <label for="claude_api_key"><?php _e('Klucz API Claude', 'claude-chat-pro'); ?></label>
                                    <p class="claude-field-description">
                                        <?php _e('Klucz API do usługi Claude.ai. Możesz go uzyskać na stronie', 'claude-chat-pro'); ?> 
                                        <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>
                                    </p>
                                </div>
                                <div class="claude-field-input">
                                    <div class="claude-api-key-field">
                                        <input type="password" id="claude_api_key" name="claude_api_key" value="<?php echo esc_attr($api_key); ?>" />
                                        <button type="button" class="claude-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'claude-chat-pro'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="claude-field-row claude-field-actions">
                                <button type="button" id="test-claude-api" class="claude-button claude-button-secondary">
                                    <span class="dashicons dashicons-database-view"></span>
                                    <?php _e('Testuj połączenie z API', 'claude-chat-pro'); ?>
                                </button>
                                <div id="api-test-result" class="claude-api-test-result"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="models-settings" class="claude-tab-content">
                    <div class="claude-settings-card">
                        <div class="claude-card-header">
                            <h2><?php _e('Modele Claude AI', 'claude-chat-pro'); ?></h2>
                            <p class="claude-card-description"><?php _e('Wybierz model Claude AI, który ma być używany w czacie.', 'claude-chat-pro'); ?></p>
                        </div>
                        <div class="claude-card-body">
                            <div class="claude-models-grid">
                                <?php 
                                $current_model = $default_model;
                                $available_models = [
                                    'claude-3-5-sonnet-20240620' => 'Claude 3.5 Sonnet (2024-06-20)',
                                    'claude-3-opus-20240229' => 'Claude 3 Opus (2024-02-29)',
                                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (2024-02-29)',
                                    'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
                                    'claude-2.1' => 'Claude 2.1',
                                    'claude-2.0' => 'Claude 2.0',
                                    'claude-instant-1.2' => 'Claude Instant 1.2',
                                ];
                                foreach ($available_models as $model_id => $model_name) :
                                    $is_selected = ($current_model === $model_id) ? 'selected' : '';
                                    $model_type = '';
                                    if (strpos($model_id, 'opus') !== false) {
                                        $model_type = 'opus';
                                    } elseif (strpos($model_id, 'sonnet') !== false) {
                                        $model_type = 'sonnet';
                                    } elseif (strpos($model_id, 'haiku') !== false) {
                                        $model_type = 'haiku';
                                    } elseif (strpos($model_id, 'instant') !== false) {
                                        $model_type = 'instant';
                                    } else {
                                        $model_type = 'claude';
                                    }
                                ?>
                                <div class="claude-model-card <?php echo $is_selected . ' ' . $model_type; ?>">
                                    <input type="radio" id="model_<?php echo esc_attr($model_id); ?>" name="claude_default_model" value="<?php echo esc_attr($model_id); ?>" <?php checked($current_model, $model_id); ?>>
                                    <label for="model_<?php echo esc_attr($model_id); ?>" class="claude-model-content">
                                        <div class="claude-model-header">
                                            <div class="claude-model-icon">
                                                <span class="dashicons dashicons-superhero"></span>
                                            </div>
                                            <div class="claude-model-badge"><?php echo esc_html($model_name); ?></div>
                                        </div>
                                        <h3 class="claude-model-name"><?php echo esc_html($model_name); ?></h3>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="repositories-settings" class="claude-tab-content">
                    <div class="claude-settings-card">
                        <div class="claude-card-header">
                            <h2><?php _e('Ustawienia GitHub', 'claude-chat-pro'); ?></h2>
                            <p class="claude-card-description"><?php _e('Skonfiguruj dostęp do repozytoriów GitHub.', 'claude-chat-pro'); ?></p>
                        </div>
                        <div class="claude-card-body">
                            <div class="claude-field-row">
                                <div class="claude-field-label">
                                    <label for="claude_github_token"><?php _e('Token dostępu GitHub', 'claude-chat-pro'); ?></label>
                                    <p class="claude-field-description">
                                        <?php _e('Token dostępu osobistego z uprawnieniami do odczytu repozytoriów.', 'claude-chat-pro'); ?>
                                        <a href="https://github.com/settings/tokens" target="_blank"><?php _e('Utwórz token', 'claude-chat-pro'); ?></a>
                                    </p>
                                </div>
                                <div class="claude-field-input">
                                    <div class="claude-api-key-field">
                                        <input type="password" id="claude_github_token" name="claude_github_token" value="<?php echo esc_attr($github_token); ?>" />
                                        <button type="button" class="claude-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'claude-chat-pro'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="claude-field-row claude-field-actions">
                                <button type="button" id="test-github-api" class="claude-button claude-button-secondary">
                                    <span class="dashicons dashicons-database-view"></span>
                                    <?php _e('Testuj połączenie z GitHub', 'claude-chat-pro'); ?>
                                </button>
                                <div id="github-test-result" class="claude-api-test-result"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="general-settings" class="claude-tab-content">
                    <div class="claude-settings-card">
                        <div class="claude-card-header">
                            <h2><?php _e('Ustawienia ogólne', 'claude-chat-pro'); ?></h2>
                            <p class="claude-card-description"><?php _e('Ogólne ustawienia wtyczki.', 'claude-chat-pro'); ?></p>
                        </div>
                        <div class="claude-card-body">
                            <div class="claude-field-row">
                                <div class="claude-field-label">
                                    <label for="claude_allowed_file_extensions"><?php _e('Dozwolone rozszerzenia plików', 'claude-chat-pro'); ?></label>
                                    <p class="claude-field-description">
                                        <?php _e('Lista rozszerzeń plików oddzielonych przecinkami, które można przesyłać.', 'claude-chat-pro'); ?>
                                    </p>
                                </div>
                                <div class="claude-field-input">
                                    <div class="claude-tags-input-container">
                                        <div class="claude-tags-input-field">
                                            <input type="text" id="claude_file_extension_input" placeholder="<?php _e('Dodaj rozszerzenie...', 'claude-chat-pro'); ?>" />
                                            <button type="button" id="add-extension" class="claude-button claude-button-icon">
                                                <span class="dashicons dashicons-plus-alt"></span>
                                            </button>
                                        </div>
                                        <div class="claude-tags-container" id="extensions-container">
                                            <?php 
                                            $extensions = explode(',', $allowed_file_extensions);
                                            foreach ($extensions as $ext) {
                                                $ext = trim($ext);
                                                if (!empty($ext)) {
                                                    echo '<span class="claude-tag">' . esc_html($ext) . '<button type="button" class="claude-remove-tag" data-value="' . esc_attr($ext) . '"><span class="dashicons dashicons-no-alt"></span></button></span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <input type="hidden" id="claude_allowed_file_extensions" name="claude_allowed_file_extensions" value="<?php echo esc_attr($allowed_file_extensions); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="claude-field-row">
                                <div class="claude-field-label">
                                    <label for="claude_debug_mode"><?php _e('Tryb debugowania', 'claude-chat-pro'); ?></label>
                                    <p class="claude-field-description">
                                        <?php _e('Włącz rejestrowanie zdarzeń w celach diagnostycznych.', 'claude-chat-pro'); ?>
                                    </p>
                                </div>
                                <div class="claude-field-input">
                                    <label class="claude-toggle-switch">
                                        <input type="checkbox" id="claude_debug_mode" name="claude_debug_mode" value="1" <?php checked($debug_mode, 1); ?> />
                                        <span class="claude-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="claude-field-row">
                                <div class="claude-field-label">
                                    <label for="claude_auto_purge_history"><?php _e('Automatyczne czyszczenie historii', 'claude-chat-pro'); ?></label>
                                    <p class="claude-field-description">
                                        <?php _e('Automatycznie usuwaj stare rozmowy po określonej liczbie dni.', 'claude-chat-pro'); ?>
                                    </p>
                                </div>
                                <div class="claude-field-input">
                                    <div class="claude-auto-purge-container">
                                        <label class="claude-toggle-switch">
                                            <input type="checkbox" id="claude_auto_purge_enabled" name="claude_auto_purge_enabled" value="1" <?php checked($auto_purge_enabled, true); ?> />
                                            <span class="claude-toggle-slider"></span>
                                        </label>
                                        <div class="claude-auto-purge-days">
                                            <input type="number" id="claude_auto_purge_days" name="claude_auto_purge_days" min="1" max="365" value="<?php echo esc_attr($auto_purge_days); ?>" <?php echo $auto_purge_enabled ? '' : 'disabled'; ?> />
                                            <span class="claude-days-label"><?php _e('dni', 'claude-chat-pro'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="claude-form-actions">
                    <button type="submit" name="claude_save_settings" class="claude-button claude-button-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Zapisz ustawienia', 'claude-chat-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>