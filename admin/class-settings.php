<?php
namespace ClaudeChatPro\Admin;

error_log('TEST LOG: settings.php loaded');

use ClaudeChatPro\Includes\Database\Settings_DB;

class Settings {
    private $option_group = 'claude_chat_pro_options';
    private $option_page = 'claude-chat-settings';
    
    private static $hooks_registered = false;

    /**
     * Konstruktor
     */
    public function __construct() {
        // Konstruktor bez rejestracji hooków
    }

    /**
     * Rejestracja hooków (wywoływana tylko raz przez Admin)
     */
    public function register_hooks() {
        if (self::$hooks_registered) {
            return;
        }
        add_action('admin_init', [
            $this, 'register_settings'
        ]);
        add_action('admin_enqueue_scripts', [
            $this, 'enqueue_scripts'
        ]);
        add_action('wp_ajax_save_claude_settings', [ $this, 'ajax_save_settings' ]);
        add_action('wp_ajax_test_api_connection', [ $this, 'ajax_test_api_connection' ]);
        self::$hooks_registered = true;
    }

    /**
     * Ładowanie skryptów i stylów
     */
    public function enqueue_scripts($hook) {
        // Ładuj tylko na stronie ustawień (poprawiony hook)
        if ($hook !== 'claude-chat_page_claude-chat-settings') {
            return;
        }
        wp_enqueue_style(
            'claude-settings',
            CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/settings.css',
            [],
            filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/css/settings.css')
        );
        wp_enqueue_script(
            'claude-settings',
            CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/settings.js',
            ['jquery'],
            filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/js/settings.js'),
            true
        );
    }

    /**
     * Rejestracja ustawień
     */
    public function register_settings() {
        // Rejestracja sekcji
        add_settings_section(
            'claude_api_settings',
            __('Ustawienia API', 'claude-chat-pro'),
            [$this, 'render_api_section'],
            $this->option_page
        );

        add_settings_section(
            'claude_general_settings',
            __('Ustawienia ogólne', 'claude-chat-pro'),
            [$this, 'render_general_section'],
            $this->option_page
        );

        // Nowa sekcja: Zaawansowane
        add_settings_section(
            'claude_advanced_settings',
            __('Zaawansowane', 'claude-chat-pro'),
            [$this, 'render_advanced_section'],
            $this->option_page
        );

        // Nowa sekcja: Integracje
        add_settings_section(
            'claude_integrations',
            __('Integracje', 'claude-chat-pro'),
            [$this, 'render_integrations_section'],
            $this->option_page
        );

        // Nowa sekcja: Wygląd
        add_settings_section(
            'claude_appearance',
            __('Wygląd', 'claude-chat-pro'),
            [$this, 'render_appearance_section'],
            $this->option_page
        );

        // Dodaj pola ustawień
        $this->add_settings_fields();
    }

    /**
     * Dodawanie pól ustawień
     */
    private function add_settings_fields() {
        // Pole API Key
        add_settings_field(
            'claude_api_key',
            __('Klucz API Claude', 'claude-chat-pro'),
            [$this, 'render_api_key_field'],
            $this->option_page,
            'claude_api_settings'
        );

        // Pole GitHub Token
        add_settings_field(
            'claude_github_token',
            __('Token GitHub', 'claude-chat-pro'),
            [$this, 'render_github_token_field'],
            $this->option_page,
            'claude_api_settings'
        );

        // Pole modelu
        add_settings_field(
            'claude_default_model',
            __('Domyślny model', 'claude-chat-pro'),
            [$this, 'render_model_field'],
            $this->option_page,
            'claude_api_settings'
        );

        // Pole auto-save
        add_settings_field(
            'claude_auto_save_history',
            __('Automatyczne zapisywanie historii', 'claude-chat-pro'),
            [$this, 'render_auto_save_field'],
            $this->option_page,
            'claude_general_settings'
        );

        // Pole maksymalnego rozmiaru pliku
        add_settings_field(
            'claude_max_file_size',
            __('Maksymalny rozmiar pliku (MB)', 'claude-chat-pro'),
            [$this, 'render_max_file_size_field'],
            $this->option_page,
            'claude_general_settings'
        );

        // Zaawansowane
        add_settings_field(
            'claude_debug_mode',
            __('Tryb debugowania', 'claude-chat-pro'),
            [$this, 'render_debug_mode_field'],
            $this->option_page,
            'claude_advanced_settings'
        );

        // Integracje
        add_settings_field(
            'claude_enable_github',
            __('Włącz integrację z GitHub', 'claude-chat-pro'),
            [$this, 'render_enable_github_field'],
            $this->option_page,
            'claude_integrations'
        );

        // Wygląd
        add_settings_field(
            'claude_theme',
            __('Motyw panelu', 'claude-chat-pro'),
            [$this, 'render_theme_field'],
            $this->option_page,
            'claude_appearance'
        );

        // Rejestracja ustawień
        register_setting(
            $this->option_group,
            'claude_api_key',
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitize_api_key'],
                'default' => ''
            ]
        );

        register_setting(
            $this->option_group,
            'claude_github_token',
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitize_github_token'],
                'default' => ''
            ]
        );

        register_setting(
            $this->option_group,
            'claude_default_model',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'claude-3-opus-20240229'
            ]
        );

        register_setting(
            $this->option_group,
            'claude_auto_save_history',
            [
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true
            ]
        );

        register_setting(
            $this->option_group,
            'claude_max_file_size',
            [
                'type' => 'integer',
                'sanitize_callback' => [$this, 'sanitize_max_file_size'],
                'default' => 10
            ]
        );

        register_setting(
            $this->option_group,
            'claude_debug_mode',
            [
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ]
        );

        register_setting(
            $this->option_group,
            'claude_enable_github',
            [
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true
            ]
        );

        register_setting(
            $this->option_group,
            'claude_theme',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'light'
            ]
        );
    }

    /**
     * Renderowanie sekcji API
     */
    public function render_api_section() {
        ?>
        <p><?php _e('Skonfiguruj ustawienia API Claude i GitHub.', 'claude-chat-pro'); ?></p>
        <?php
    }

    /**
     * Renderowanie sekcji ogólnej
     */
    public function render_general_section() {
        ?>
        <p><?php _e('Skonfiguruj ogólne ustawienia wtyczki.', 'claude-chat-pro'); ?></p>
        <?php
    }

    /**
     * Renderowanie sekcji Zaawansowane
     */
    public function render_advanced_section() {
        echo '<p>' . __('Zaawansowane opcje i debugowanie.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Renderowanie sekcji Integracje
     */
    public function render_integrations_section() {
        echo '<p>' . __('Integracje z zewnętrznymi usługami, np. GitHub.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Renderowanie sekcji Wygląd
     */
    public function render_appearance_section() {
        echo '<p>' . __('Personalizuj wygląd panelu Claude Chat Pro.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Renderowanie pola API Key
     */
    public function render_api_key_field() {
        $value = Settings_DB::get('claude_api_key', '');
        $display_value = $value ? str_repeat('•', 20) : '';
        ?>
        <label for="claude_api_key"><?php _e('Klucz API Claude', 'claude-chat-pro'); ?></label>
        <input type="password" id="claude_api_key" name="claude_api_key" value="" placeholder="<?php echo esc_attr($display_value ?: __('Wprowadź klucz API Claude', 'claude-chat-pro')); ?>" class="regular-text">
        <button type="button" class="button toggle-password" data-target="claude_api_key"><span class="dashicons dashicons-visibility"></span></button>
        <?php if ($value): ?><span class="status-indicator configured"><span class="dashicons dashicons-yes"></span></span><?php endif; ?>
        <p class="description"><?php _e('Klucz API Claude jest wymagany do komunikacji z API.', 'claude-chat-pro'); ?></p>
        <?php
    }

    /**
     * Renderowanie pola GitHub Token
     */
    public function render_github_token_field() {
        $value = Settings_DB::get('claude_github_token', '');
        $display_value = $value ? str_repeat('•', 20) : '';
        ?>
        <label for="claude_github_token"><?php _e('Token GitHub', 'claude-chat-pro'); ?></label>
        <input type="password" id="claude_github_token" name="claude_github_token" value="" placeholder="<?php echo esc_attr($display_value ?: __('Wprowadź token GitHub (opcjonalnie)', 'claude-chat-pro')); ?>" class="regular-text">
        <button type="button" class="button toggle-password" data-target="claude_github_token"><span class="dashicons dashicons-visibility"></span></button>
        <?php if ($value): ?><span class="status-indicator configured"><span class="dashicons dashicons-yes"></span></span><?php endif; ?>
        <p class="description"><?php _e('Token GitHub jest wymagany do integracji z repozytoriami.', 'claude-chat-pro'); ?></p>
        <?php
    }

    /**
     * Renderowanie pola modelu
     */
    public function render_model_field() {
        $current_model = Settings_DB::get('claude_default_model', 'claude-3-opus-20240229');
        $models = get_option('claude_available_models');
        if (!is_array($models) || empty($models)) {
            // fallback do statycznej listy jeśli nie ma pobranych modeli
            $models = [
                ['id' => 'claude-3-5-sonnet-20241022', 'name' => 'Claude 3.5 Sonnet (Najnowszy)'],
                ['id' => 'claude-3-opus-20240229', 'name' => 'Claude 3 Opus'],
                ['id' => 'claude-3-sonnet-20240229', 'name' => 'Claude 3 Sonnet'],
                ['id' => 'claude-3-haiku-20240307', 'name' => 'Claude 3 Haiku']
            ];
        }
        ?>
        <label for="claude_default_model"><?php _e('Domyślny model', 'claude-chat-pro'); ?></label>
        <select id="claude_default_model" name="claude_default_model" class="regular-text">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr(is_array($model) ? $model['id'] : $model); ?>" <?php selected($current_model, is_array($model) ? $model['id'] : $model); ?>><?php echo esc_html(is_array($model) ? $model['name'] : $model); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Wybierz model Claude, którego chcesz używać domyślnie.', 'claude-chat-pro'); ?></p>
        <?php
    }

    /**
     * Renderowanie pola auto-save
     */
    public function render_auto_save_field() {
        $value = Settings_DB::get('claude_auto_save_history', 1);a
        ?>
        <label class="toggle-switch">
            <input type="checkbox" id="claude_auto_save_history" name="claude_auto_save_history" value="1" <?php checked($value, 1); ?>>
            <span class="toggle-slider"></span>
        </label>
        <span class="toggle-label"><?php _e('Automatyczne zapisywanie historii', 'claude-chat-pro'); ?></span>
        <p class="description"><?php _e('Automatycznie zapisuj historię czatu.', 'claude-chat-pro'); ?></p>
        <?php
    }

    /**
     * Renderowanie pola maksymalnego rozmiaru pliku
     */
    public function render_max_file_size_field() {
        $current_size = Settings_DB::get('claude_max_file_size', 10);
        ?>
        <label for="claude_max_file_size"><?php _e('Maksymalny rozmiar pliku (MB)', 'claude-chat-pro'); ?></label>
        <input type="number" id="claude_max_file_size" name="claude_max_file_size" value="<?php echo esc_attr($current_size); ?>" min="1" max="100" step="1" class="small-text">
        <span class="unit">MB</span>
        <p class="description"><?php _e('Maksymalny rozmiar pliku, który można przesłać do analizy.', 'claude-chat-pro'); ?></p>
        <?php
    }

    /**
     * Renderowanie pola enable_github
     */
    public function render_enable_github_field() {
        $value = Settings_DB::get('claude_enable_github', 1);
        echo '<label class="toggle-switch">';
        echo '<input type="checkbox" id="claude_enable_github" name="claude_enable_github" value="1"' . checked($value, 1, false) . '>';
        echo '<span class="toggle-slider"></span>';
        echo '</label> <span class="toggle-label">' . __('Włącz integrację z GitHub', 'claude-chat-pro') . '</span>';
        echo '<p class="description">' . __('Pozwala na korzystanie z funkcji GitHub w Claude Chat Pro.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Renderowanie pola theme
     */
    public function render_theme_field() {
        $current = Settings_DB::get('claude_theme', 'light');
        echo '<label for="claude_theme">' . __('Motyw', 'claude-chat-pro') . '</label>';
        echo '<select id="claude_theme" name="claude_theme" class="regular-text">';
        echo '<option value="light"' . selected($current, 'light', false) . '>' . __('Jasny', 'claude-chat-pro') . '</option>';
        echo '<option value="dark"' . selected($current, 'dark', false) . '>' . __('Ciemny', 'claude-chat-pro') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Wybierz motyw panelu ustawień.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Renderowanie pola debug_mode
     */
    public function render_debug_mode_field() {
        $value = Settings_DB::get('claude_debug_mode', 0);
        echo '<label class="toggle-switch">';
        echo '<input type="checkbox" id="claude_debug_mode" name="claude_debug_mode" value="1"' . checked($value, 1, false) . '>';
        echo '<span class="toggle-slider"></span>';
        echo '</label> <span class="toggle-label">' . __('Włącz tryb debugowania', 'claude-chat-pro') . '</span>';
        echo '<p class="description">' . __('Wyświetlaj dodatkowe logi i komunikaty dla deweloperów.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Sanityzacja klucza API
     */
    public function sanitize_api_key($value) {
        if (empty($value)) {
            return '';
        }
        
        // Sprawdź czy klucz jest prawidłowy
        if (!\ClaudeChatPro\Includes\Security::validate_api_key($value)) {
            add_settings_error(
                'claude_api_key',
                'invalid_api_key',
                __('Nieprawidłowy format klucza API Claude', 'claude-chat-pro')
            );
            return '';
        }
        
        return \ClaudeChatPro\Includes\Security::encrypt(sanitize_text_field($value));
    }

    /**
     * Sanityzacja tokenu GitHub
     */
    public function sanitize_github_token($value) {
        if (empty($value)) {
            return '';
        }
        
        // Sprawdź czy token jest prawidłowy
        if (!\ClaudeChatPro\Includes\Security::validate_github_token($value)) {
            add_settings_error(
                'claude_github_token',
                'invalid_github_token',
                __('Nieprawidłowy format tokenu GitHub', 'claude-chat-pro')
            );
            return '';
        }
        
        return \ClaudeChatPro\Includes\Security::encrypt(sanitize_text_field($value));
    }

    /**
     * Sanityzacja maksymalnego rozmiaru pliku
     */
    public function sanitize_max_file_size($value) {
        if (empty($value)) {
            return 10;
        }
        return absint($value);
    }

    /**
     * Renderowanie strony ustawień (nowoczesny szablon)
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        // Obsługa zapisu ustawień
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claude_settings_nonce']) && wp_verify_nonce($_POST['claude_settings_nonce'], 'claude_settings_save')) {
            if (isset($_POST['claude_api_key'])) {
                Settings_DB::set('claude_api_key', sanitize_text_field($_POST['claude_api_key']));
            }
            if (isset($_POST['claude_github_token'])) {
                Settings_DB::set('claude_github_token', sanitize_text_field($_POST['claude_github_token']));
            }
            Settings_DB::set('claude_default_model', sanitize_text_field($_POST['claude_default_model'] ?? 'claude-3-opus-20240229'));
            Settings_DB::set('claude_auto_save_history', isset($_POST['claude_auto_save_history']) ? 1 : 0);
            Settings_DB::set('claude_max_file_size', absint($_POST['claude_max_file_size'] ?? 10));
            Settings_DB::set('claude_debug_mode', isset($_POST['claude_debug_mode']) ? 1 : 0);
            Settings_DB::set('claude_enable_github', isset($_POST['claude_enable_github']) ? 1 : 0);
            Settings_DB::set('claude_theme', sanitize_text_field($_POST['claude_theme'] ?? 'light'));
            add_settings_error('claude_messages', 'claude_message', __('Ustawienia zostały zapisane.', 'claude-chat-pro'), 'updated');
        }
        settings_errors('claude_messages');
        require_once CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/views/settings-modern.php';
    }

    /**
     * Obsługa AJAX testowania połączenia z API Claude i GitHub
     */
    public function ajax_test_api_connection() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'claude_chat_settings')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'claude-chat-pro')]);
        }
        if (!isset($_POST['api_type'])) {
            wp_send_json_error(['message' => __('Nie określono typu API.', 'claude-chat-pro')]);
        }
        $api_type = sanitize_text_field($_POST['api_type']);
        switch ($api_type) {
            case 'claude':
                $this->test_claude_connection();
                break;
            case 'github':
                $this->test_github_connection();
                break;
            default:
                wp_send_json_error(['message' => __('Nieznany typ API.', 'claude-chat-pro')]);
        }
    }
    private function test_claude_connection() {
        if (!isset($_POST['api_key']) || empty($_POST['api_key'])) {
            wp_send_json_error(['message' => __('Nie podano klucza API.', 'claude-chat-pro')]);
        }
        $api_key = sanitize_text_field($_POST['api_key']);
        $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api($api_key);
        $result = $claude_api->test_connection();
        if ($result) {
            wp_send_json_success(['message' => __('Połączenie z API Claude działa poprawnie.', 'claude-chat-pro')]);
        } else {
            wp_send_json_error(['message' => __('Nie udało się połączyć z API Claude. Sprawdź klucz API i spróbuj ponownie.', 'claude-chat-pro')]);
        }
    }
    public function test_github_connection() {
        error_log('START test_github_connection');
        check_ajax_referer('claude_chat_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            error_log('Brak uprawnień');
            wp_send_json_error(['message' => 'Brak uprawnień.']);
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        error_log('GitHub token from AJAX: ' . $api_key);

        if (empty($api_key)) {
            error_log('Token GitHub jest wymagany.');
            wp_send_json_error(['message' => 'Token GitHub jest wymagany.']);
        }

        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api($api_key);
            $result = $github_api->test_connection();
            error_log('GitHub test connection result: ' . print_r($result, true));

            if ($result['status']) {
                error_log('END test_github_connection: success');
                wp_send_json_success(['message' => 'Połączenie z GitHubem udane!']);
            } else {
                error_log('END test_github_connection: fail');
                wp_send_json_error(['message' => 'Błąd połączenia: ' . $result['error']]);
            }
        } catch (\Exception $e) {
            error_log('GitHub test connection exception: ' . $e->getMessage());
            error_log('END test_github_connection: exception');
            wp_send_json_error(['message' => 'Błąd połączenia: ' . $e->getMessage()]);
        }
    }

    /**
     * Obsługa AJAX zapisu ustawień (bez szyfrowania kluczy/tokenów)
     */
    public function ajax_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień.', 'claude-chat-pro')]);
        }
        if (!isset($_POST['claude_settings_nonce']) || !wp_verify_nonce($_POST['claude_settings_nonce'], 'claude_settings_save')) {
            wp_send_json_error(['message' => __('Błąd weryfikacji bezpieczeństwa (nonce).', 'claude-chat-pro')]);
        }
        // Claude API Key (bez szyfrowania)
        if (isset($_POST['claude_api_key'])) {
            Settings_DB::set('claude_api_key', sanitize_text_field($_POST['claude_api_key']));
        }
        // GitHub Token (bez szyfrowania)
        if (isset($_POST['claude_github_token'])) {
            Settings_DB::set('claude_github_token', sanitize_text_field($_POST['claude_github_token']));
        }
        Settings_DB::set('claude_default_model', sanitize_text_field($_POST['claude_default_model'] ?? 'claude-3-opus-20240229'));
        Settings_DB::set('claude_auto_save_history', isset($_POST['claude_auto_save_history']) ? 1 : 0);
        Settings_DB::set('claude_max_file_size', absint($_POST['claude_max_file_size'] ?? 10));
        Settings_DB::set('claude_debug_mode', isset($_POST['claude_debug_mode']) ? 1 : 0);
        Settings_DB::set('claude_enable_github', isset($_POST['claude_enable_github']) ? 1 : 0);
        Settings_DB::set('claude_theme', sanitize_text_field($_POST['claude_theme'] ?? 'light'));
        // Dodatkowe pola (jeśli są)
        if (isset($_POST['claude_allowed_file_extensions'])) {
            Settings_DB::set('claude_allowed_file_extensions', sanitize_text_field($_POST['claude_allowed_file_extensions']));
        }
        if (isset($_POST['claude_auto_purge_enabled'])) {
            Settings_DB::set('claude_auto_purge_enabled', 1);
        } else {
            Settings_DB::set('claude_auto_purge_enabled', 0);
        }
        if (isset($_POST['claude_auto_purge_days'])) {
            Settings_DB::set('claude_auto_purge_days', absint($_POST['claude_auto_purge_days']));
        }
        wp_send_json_success(['message' => __('Ustawienia zostały zapisane.', 'claude-chat-pro')]);
    }
}