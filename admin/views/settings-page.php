<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

// Obsługa zapisywania ustawień
if (isset($_POST['submit']) && check_admin_referer('claude_chat_pro_settings')) {
    $claude_api_key = sanitize_text_field($_POST['claude_api_key'] ?? '');
    $github_token = sanitize_text_field($_POST['github_token'] ?? '');
    $claude_default_model = sanitize_text_field($_POST['claude_default_model'] ?? 'claude-3-haiku-20240307');
    
    // Szyfruj i zapisz klucze API
    if (!empty($claude_api_key)) {
        update_option('claude_api_key', \ClaudeChatPro\Includes\Security::encrypt($claude_api_key));
    }
    
    if (!empty($github_token)) {
        update_option('github_token', \ClaudeChatPro\Includes\Security::encrypt($github_token));
    }
    
    update_option('claude_default_model', $claude_default_model);
    
    // Testuj połączenia po zapisaniu
    $connection_tests = [];
    try {
        if (!empty($claude_api_key)) {
            $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
            $connection_tests['claude'] = $claude_api->test_connection();
        }
        
        if (!empty($github_token)) {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $connection_tests['github'] = $github_api->test_connection();
        }
    } catch (\Exception $e) {
        // Ignoruj błędy testów połączenia przy zapisywaniu
    }
    
    echo '<div class="notice notice-success"><p>' . __('Ustawienia zostały zapisane.', 'claude-chat-pro') . '</p></div>';
}

// Pobierz aktualne wartości
$claude_api_key = get_option('claude_api_key');
$github_token = get_option('github_token');
$claude_default_model = get_option('claude_default_model', 'claude-3-haiku-20240307');

// Odszyfruj klucze dla wyświetlenia (tylko część)
$claude_api_display = '';
$github_token_display = '';

if (!empty($claude_api_key)) {
    $decrypted = \ClaudeChatPro\Includes\Security::decrypt($claude_api_key);
    $claude_api_display = substr($decrypted, 0, 8) . str_repeat('*', max(0, strlen($decrypted) - 8));
}

if (!empty($github_token)) {
    $decrypted = \ClaudeChatPro\Includes\Security::decrypt($github_token);
    $github_token_display = substr($decrypted, 0, 8) . str_repeat('*', max(0, strlen($decrypted) - 8));
}

$current_user = wp_get_current_user();
$current_time_utc = current_time('mysql', true);

// Dostępne modele Claude
$available_models = [
    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Najnowszy)',
    'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Szybki)',
    'claude-3-opus-20240229' => 'Claude 3 Opus (Najbardziej zaawansowany)',
    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Zbalansowany)',
    'claude-3-haiku-20240307' => 'Claude 3 Haiku (Ekonomiczny)'
];
?>

<div class="wrap claude-chat-settings">
    <h1><?php _e('Ustawienia Claude Chat Pro', 'claude-chat-pro'); ?></h1>

    <!-- Informacje systemowe -->
    <div class="system-info">
        <p>
            <strong><?php _e('Aktualny użytkownik:', 'claude-chat-pro'); ?></strong> 
            <?php echo esc_html($current_user->user_login); ?>
        </p>
        <p>
            <strong><?php _e('Data i czas (UTC):', 'claude-chat-pro'); ?></strong> 
            <?php echo esc_html($current_time_utc); ?>
        </p>
        <p>
            <strong><?php _e('Wersja wtyczki:', 'claude-chat-pro'); ?></strong> 
            <?php echo esc_html(CLAUDE_CHAT_PRO_VERSION); ?>
        </p>
    </div>

    <!-- Test połączenia API -->
    <div class="api-status">
        <h2><?php _e('Status połączeń API', 'claude-chat-pro'); ?></h2>
        <div id="api-status-results">
            <button type="button" id="test-connections" class="button">
                <?php _e('Testuj połączenia', 'claude-chat-pro'); ?>
            </button>
        </div>
    </div>

    <!-- Formularz ustawień -->
    <form method="post" action="" class="claude-chat-settings-form">
        <?php wp_nonce_field('claude_chat_pro_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="claude_api_key"><?php _e('Klucz API Claude', 'claude-chat-pro'); ?></label>
                </th>
                <td>
                    <div class="api-key-field">
                        <input type="password" 
                               id="claude_api_key" 
                               name="claude_api_key" 
                               value=""
                               placeholder="<?php echo esc_attr($claude_api_display ?: __('Wprowadź klucz API Claude', 'claude-chat-pro')); ?>"
                               class="regular-text">
                        <button type="button" 
                                class="button toggle-password" 
                                data-target="claude_api_key">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <p class="description">
                        <?php 
                        printf(
                            __('Wprowadź swój klucz API z <a href="%s" target="_blank">Claude.ai</a>. Klucz zostanie zaszyfrowany.', 'claude-chat-pro'),
                            'https://console.anthropic.com/'
                        );
                        ?>
                        <?php if (!empty($claude_api_key)): ?>
                            <br><strong><?php _e('Status:', 'claude-chat-pro'); ?></strong> 
                            <span class="api-configured"><?php _e('Skonfigurowane', 'claude-chat-pro'); ?></span>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="claude_default_model"><?php _e('Domyślny model Claude', 'claude-chat-pro'); ?></label>
                </th>
                <td>
                    <select id="claude_default_model" name="claude_default_model" class="regular-text">
                        <?php foreach ($available_models as $model_id => $model_name): ?>
                            <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected($claude_default_model, $model_id); ?>>
                                <?php echo esc_html($model_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Wybierz domyślny model Claude AI do rozmów. Różne modele mają różną szybkość i możliwości.', 'claude-chat-pro'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="github_token"><?php _e('Token GitHub', 'claude-chat-pro'); ?></label>
                </th>
                <td>
                    <div class="api-key-field">
                        <input type="password" 
                               id="github_token" 
                               name="github_token" 
                               value=""
                               placeholder="<?php echo esc_attr($github_token_display ?: __('Wprowadź token GitHub (opcjonalnie)', 'claude-chat-pro')); ?>"
                               class="regular-text">
                        <button type="button" 
                                class="button toggle-password" 
                                data-target="github_token">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <p class="description">
                        <?php 
                        printf(
                            __('Wprowadź swój Personal Access Token z <a href="%s" target="_blank">GitHub</a> aby uzyskać dostęp do repozytoriów.', 'claude-chat-pro'),
                            'https://github.com/settings/tokens'
                        );
                        ?>
                        <?php if (!empty($github_token)): ?>
                            <br><strong><?php _e('Status:', 'claude-chat-pro'); ?></strong> 
                            <span class="api-configured"><?php _e('Skonfigurowane', 'claude-chat-pro'); ?></span>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php _e('Dodatkowe opcje', 'claude-chat-pro'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Automatyczne zapisywanie historii', 'claude-chat-pro'); ?></th>
                <td>
                    <label for="auto_save_history">
                        <input type="checkbox" 
                               id="auto_save_history" 
                               name="auto_save_history" 
                               value="1"
                               <?php checked(get_option('claude_auto_save_history', true)); ?>>
                        <?php _e('Automatycznie zapisuj historię rozmów', 'claude-chat-pro'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Jeśli włączone, wszystkie rozmowy będą automatycznie zapisywane w bazie danych.', 'claude-chat-pro'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Maksymalny rozmiar pliku', 'claude-chat-pro'); ?></th>
                <td>
                    <select name="max_file_size">
                        <option value="1048576" <?php selected(get_option('claude_max_file_size', 1048576), 1048576); ?>>1 MB</option>
                        <option value="2097152" <?php selected(get_option('claude_max_file_size'), 2097152); ?>>2 MB</option>
                        <option value="5242880" <?php selected(get_option('claude_max_file_size'), 5242880); ?>>5 MB</option>
                    </select>
                    <p class="description">
                        <?php _e('Maksymalny rozmiar załączanych plików. Większe pliki mogą spowalniać działanie.', 'claude-chat-pro'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Zapisz ustawienia', 'claude-chat-pro')); ?>
    </form>
    
    <!-- Sekcja pomocy -->
    <div class="help-section">
        <h2><?php _e('Pomoc', 'claude-chat-pro'); ?></h2>
        <div class="help-cards">
            <div class="help-card">
                <h3><?php _e('Jak uzyskać klucz API Claude?', 'claude-chat-pro'); ?></h3>
                <ol>
                    <li><?php _e('Przejdź na stronę', 'claude-chat-pro'); ?> <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></li>
                    <li><?php _e('Załóż konto lub zaloguj się', 'claude-chat-pro'); ?></li>
                    <li><?php _e('Przejdź do sekcji API Keys', 'claude-chat-pro'); ?></li>
                    <li><?php _e('Utwórz nowy klucz API', 'claude-chat-pro'); ?></li>
                    <li><?php _e('Skopiuj klucz i wklej tutaj', 'claude-chat-pro'); ?></li>
                </ol>
            </div>
            
            <div class="help-card">
                <h3><?php _e('Jak uzyskać token GitHub?', 'claude-chat-pro'); ?></h3>
                <ol>
                    <li><?php _e('Przejdź na stronę', 'claude-chat-pro'); ?> <a href="https://github.com/settings/tokens" target="_blank">github.com/settings/tokens</a></li>
                    <li><?php _e('Kliknij "Generate new token (classic)"', 'claude-chat-pro'); ?></li>
                    <li><?php _e('Nadaj nazwę tokenowi', 'claude-chat-pro'); ?></li>
                    <li><?php _e('Wybierz uprawnienia: repo, user', 'claude-chat-pro'); ?></li>
                    <li><?php _e('Kliknij "Generate token"', 'claude-chat-pro'); ?></li>
                    <li><?php _e('Skopiuj token i wklej tutaj', 'claude-chat-pro'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<style>
.api-configured {
    color: #46b450;
    font-weight: bold;
}

.help-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ccd0d4;
}

.help-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.help-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.help-card h3 {
    margin-top: 0;
    color: #333;
}

.help-card ol {
    margin: 0;
    padding-left: 20px;
}

.help-card li {
    margin-bottom: 5px;
}

#api-status-results {
    margin-top: 10px;
}

.api-status-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-indicator.success {
    background: #46b450;
}

.status-indicator.error {
    background: #dc3232;
}

.status-indicator.testing {
    background: #ffb900;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Obsługa pokazywania/ukrywania haseł
    $('.toggle-password').on('click', function() {
        const targetId = $(this).data('target');
        const input = $('#' + targetId);
        const icon = $(this).find('.dashicons');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // Test połączeń API
    $('#test-connections').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        const resultsDiv = $('#api-status-results');
        
        button.prop('disabled', true).text('<?php echo esc_js(__('Testowanie...', 'claude-chat-pro')); ?>');
        
        // Wyczyść poprzednie wyniki
        resultsDiv.find('.api-status-item').remove();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'claude_chat_test_api',
                nonce: '<?php echo wp_create_nonce('claude-chat-pro-nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Claude API
                    if (typeof data.claude !== 'undefined') {
                        resultsDiv.append(
                            '<div class="api-status-item">' +
                            '<div class="status-indicator ' + (data.claude ? 'success' : 'error') + '"></div>' +
                            '<span><strong>Claude API:</strong> ' + (data.claude ? '<?php echo esc_js(__('Połączenie OK', 'claude-chat-pro')); ?>' : '<?php echo esc_js(__('Błąd połączenia', 'claude-chat-pro')); ?>') + '</span>' +
                            '</div>'
                        );
                    }
                    
                    // GitHub API
                    if (typeof data.github !== 'undefined') {
                        resultsDiv.append(
                            '<div class="api-status-item">' +
                            '<div class="status-indicator ' + (data.github ? 'success' : 'error') + '"></div>' +
                            '<span><strong>GitHub API:</strong> ' + (data.github ? '<?php echo esc_js(__('Połączenie OK', 'claude-chat-pro')); ?>' : '<?php echo esc_js(__('Błąd połączenia', 'claude-chat-pro')); ?>') + '</span>' +
                            '</div>'
                        );
                    }
                } else {
                    resultsDiv.append(
                        '<div class="api-status-item">' +
                        '<div class="status-indicator error"></div>' +
                        '<span><?php echo esc_js(__('Błąd podczas testowania połączeń', 'claude-chat-pro')); ?></span>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                resultsDiv.append(
                    '<div class="api-status-item">' +
                    '<div class="status-indicator error"></div>' +
                    '<span><?php echo esc_js(__('Błąd komunikacji z serwerem', 'claude-chat-pro')); ?></span>' +
                    '</div>'
                );
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Potwierdzenie przed zapisaniem zmian
    $('.claude-chat-settings-form').on('submit', function(e) {
        const claudeKey = $('#claude_api_key').val();
        const githubToken = $('#github_token').val();
        
        if (!claudeKey && !githubToken) {
            if (!confirm('<?php echo esc_js(__('Nie wprowadzono żadnych kluczy API. Czy kontynuować?', 'claude-chat-pro')); ?>')) {
                e.preventDefault();
            }
        }
    });
});
</script>