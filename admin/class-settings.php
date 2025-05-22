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

        // Rejestracja pól
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
                'default' => 'claude-3-opus-20240229'
            ]
        );

        // Rejestracja pól
        add_settings_field(
            'claude_api_key',
            __('Klucz API Claude', 'claude-chat-pro'),
            [$this, 'render_api_key_field'],
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

        add_settings_field(
            'claude_default_model',
            __('Model Claude AI', 'claude-chat-pro'),
            [$this, 'render_model_field'],
            $this->option_page,
            'claude_chat_api_section'
        );
    }

    /**
     * Renderowanie sekcji API
     */
    public function render_api_section() {
        echo '<p>' . __('Skonfiguruj klucze API niezbędne do działania wtyczki.', 'claude-chat-pro') . '</p>';
    }

    /**
     * Renderowanie pola klucza API Claude
     */
    public function render_api_key_field() {
        $value = get_option('claude_api_key');
        ?>
        <input type="password" 
               id="claude_api_key" 
               name="claude_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <button type="button" 
                class="button toggle-password" 
                data-target="claude_api_key">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Wprowadź swój klucz API z Claude.ai', 'claude-chat-pro'); ?>
        </p>
        <?php
    }

    /**
     * Renderowanie pola tokenu GitHub
     */
    public function render_github_token_field() {
        $value = get_option('github_token');
        ?>
        <input type="password" 
               id="github_token" 
               name="github_token" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <button type="button" 
                class="button toggle-password" 
                data-target="github_token">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Wprowadź swój token dostępu do GitHub', 'claude-chat-pro'); ?>
        </p>
        <?php
    }

    /**
     * Renderowanie pola wyboru modelu
     */
    public function render_model_field() {
        $current_model = get_option('claude_default_model', 'claude-3-opus-20240229');
        $models = [
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240229' => 'Claude 3 Haiku',
            'claude-2.1' => 'Claude 2.1',
            'claude-2.0' => 'Claude 2.0'
        ];
        ?>
        <select id="claude_default_model" name="claude_default_model">
            <?php foreach ($models as $id => $name): ?>
                <option value="<?php echo esc_attr($id); ?>" 
                        <?php selected($current_model, $id); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('Wybierz domyślny model Claude AI', 'claude-chat-pro'); ?>
        </p>
        <?php
    }

    /**
     * Sanityzacja klucza API
     */
    public function sanitize_api_key($value) {
        return \ClaudeChatPro\Includes\Security::encrypt(sanitize_text_field($value));
    }
}