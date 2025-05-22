<?php
namespace ClaudeChatPro\Admin;

class Settings {
    private $option_group = 'claude_chat_pro_options';
    private $option_page = 'claude_chat_pro_settings';
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Rejestracja ustawień
     */
    public function register_settings() {
        // Rejestracja sekcji
        add_settings_section(
            'claude_chat_api_section',
            __('Ustawienia API', 'claude-chat-pro'),
            [$this, 'render_api_section'],
            $this->option_page
        );

        add_settings_section(
            'claude_chat_general_section',
            __('Ustawienia ogólne', 'claude-chat-pro'),
            [$this, 'render_general_section'],
            $this->option_page
        );

        // Rejestracja pól API
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
            'github_token',
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitize_api_key'],
                'default' => ''
            ]
        );

        register_setting(
            $this->option_group,
            'claude_default_model',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'claude-3-haiku-20240307'
            ]
        );

        // Rejestracja ustawień ogólnych
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
                'sanitize_callback' => 'absint',
                'default' => 1048576
            ]
        );

        // Dodaj pola ustawień
        $this->add_settings_fields();
    }

    /**
     * Dodawanie pól ustawień
     */
    private function add_settings_fields() {
        // Pola API
        add_settings_field(
            'claude_api_key',
            __('Klucz API Claude', 'claude-chat-pro'),
            [$this, 'render_api_key_field'],
            $this->option_page,
            'claude_chat_api_section'
        );

        add_settings_field(
            'claude_default_model',
            __('Domyślny model Claude', 'claude-chat-pro'),
            [$this, 'render_model_field'],
            $this->option_page,
            'claude_chat_api_section'
        );

        add_settings_field(
            'github_token',
            __('Token GitHub', 'claude-chat-pro'),
            [$this, 'render_github_token_field'],
            $this->option_page,
            'claude_chat_api_section'
        );

        // Pola ogólne
        add_settings_field(
            'claude_auto_save_history',
            __('Automatyczne zapisywanie', 'claude-chat-pro'),
            [$this, 'render_auto_save_field'],
            $this->option_page,
            'claude_chat_general_section'
        );

        add_settings_field(
            'claude_max_file_size',
            __('Maksymalny rozmiar pliku', 'claude-chat-pro'),
            [$this, 'render_max_file_size_field'],
            $this->option_page,
            'claude_chat_general_section'
        );
    }

    /**
     * Renderowanie sekcji
     */
    public function render_api_section() {
        echo '<p>' . __('Skonfiguruj klucze API niezbędne do działania wtyczki.', 'claude-chat-pro') . '</p>';
    }

    public function render_general_section() {
        echo '<p>' . __('Ogólne ustawienia wtyczki.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Renderowanie pól
     */
    public function render_api_key_field() {
        $value = get_option('claude_api_key');
        $display_value = '';
        
        if (!empty($value)) {
            $decrypted = \ClaudeChatPro\Includes\Security::decrypt($value);
            $display_value = substr($decrypted, 0, 8) . str_repeat('*', max(0, strlen($decrypted) - 8));
        }
        ?>
        <input type="password" 
               id="claude_api_key" 
               name="claude_api_key" 
               value="" 
               placeholder="<?php echo esc_attr($display_value ?: __('Wprowadź klucz API Claude', 'claude-chat-pro')); ?>"
               class="regular-text">
        <button type="button" 
                class="button toggle-password" 
                data-target="claude_api_key">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Wprowadź swój klucz API z Claude.ai. Klucz zostanie zaszyfrowany.', 'claude-chat-pro'); ?>
            <?php if (!empty($value)): ?>
                <br><strong><?php _e('Status:', 'claude-chat-pro'); ?></strong> 
                <span style="color: #46b450;"><?php _e('Skonfigurowane', 'claude-chat-pro'); ?></span>
            <?php endif; ?>
        </p>
        <?php
    }

    public function render_github_token_field() {
        $value = get_option('github_token');
        $display_value = '';
        
        if (!empty($value)) {
            $decrypted = \ClaudeChatPro\Includes\Security::decrypt($value);
            $display_value = substr($decrypted, 0, 8) . str_repeat('*', max(0, strlen($decrypted) - 8));
        }
        ?>
        <input type="password" 
               id="github_token" 
               name="github_token" 
               value=""
               placeholder="<?php echo esc_attr($display_value ?: __('Wprowadź token GitHub (opcjonalnie)', 'claude-chat-pro')); ?>"
               class="regular-text">
        <button type="button" 
                class="button toggle-password" 
                data-target="github_token">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Wprowadź swój Personal Access Token z GitHub aby uzyskać dostęp do repozytoriów.', 'claude-chat-pro'); ?>
            <?php if (!empty($value)): ?>
                <br><strong><?php _e('Status:', 'claude-chat-pro'); ?></strong> 
                <span style="color: #46b450;"><?php _e('Skonfigurowane', 'claude-chat-pro'); ?></span>
            <?php endif; ?>
        </p>
        <?php
    }

    public function render_model_field() {
        $current_model = get_option('claude_default_model', 'claude-3-haiku-20240307');
        $models = [
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Najnowszy)',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Szybki)',
            'claude-3-opus-20240229' => 'Claude 3 Opus (Najbardziej zaawansowany)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Zbalansowany)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Ekonomiczny)'
        ];
        ?>
        <select id="claude_default_model" name="claude_default_model" class="regular-text">
            <?php foreach ($models as $id => $name): ?>
                <option value="<?php echo esc_attr($id); ?>" 
                        <?php selected($current_model, $id); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('Wybierz domyślny model Claude AI. Różne modele mają różną szybkość i możliwości.', 'claude-chat-pro'); ?>
        </p>
        <?php
    }

    public function render_auto_save_field() {
        $value = get_option('claude_auto_save_history', true);
        ?>
        <label for="claude_auto_save_history">
            <input type="checkbox" 
                   id="claude_auto_save_history" 
                   name="claude_auto_save_history" 
                   value="1"
                   <?php checked($value); ?>>
            <?php _e('Automatycznie zapisuj historię rozmów w bazie danych', 'claude-chat-pro'); ?>
        </label>
        <p class="description">
            <?php _e('Jeśli włączone, wszystkie rozmowy będą zapisywane i dostępne w sekcji Historia.', 'claude-chat-pro'); ?>
        </p>
        <?php
    }

    public function render_max_file_size_field() {
        $current_size = get_option('claude_max_file_size', 1048576);
        $sizes = [
            1048576 => '1 MB',
            2097152 => '2 MB',
            5242880 => '5 MB',
            10485760 => '10 MB'
        ];
        ?>
        <select id="claude_max_file_size" name="claude_max_file_size">
            <?php foreach ($sizes as $bytes => $label): ?>
                <option value="<?php echo esc_attr($bytes); ?>" 
                        <?php selected($current_size, $bytes); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('Maksymalny rozmiar załączanych plików. Większe pliki mogą spowalniać działanie i zwiększać koszty API.', 'claude-chat-pro'); ?>
        </p>
        <?php
    }

    /**
     * Sanityzacja klucza API
     */
    public function sanitize_api_key($value) {
        if (empty($value)) {
            return '';
        }
        return \ClaudeChatPro\Includes\Security::encrypt(sanitize_text_field($value));
    }
}