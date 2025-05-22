<?php
namespace ClaudeChatPro\Admin;

class Admin {
    private $plugin_name;
    private $version;
    private $settings;

    public function __construct() {
        $this->plugin_name = 'claude-chat-pro';
        $this->version = CLAUDE_CHAT_PRO_VERSION;
        $this->settings = new Settings();

        $this->init_hooks();
    }

    /**
     * Inicjalizacja hooków
     */
    private function init_hooks() {
        // Menu administracyjne
        add_action('admin_menu', [$this, 'add_menu_pages']);
        
        // Style i skrypty
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_claude_chat_send_message', [$this, 'handle_send_message']);
        add_action('wp_ajax_claude_chat_get_repositories', [$this, 'handle_get_repositories']);
        add_action('wp_ajax_claude_chat_get_file_content', [$this, 'handle_get_file_content']);
        add_action('wp_ajax_claude_chat_check_status', [$this, 'handle_check_status']);
    }

    /**
     * Dodawanie stron menu
     */
    public function add_menu_pages() {
        // Strona główna
        add_menu_page(
            __('Claude Chat Pro', 'claude-chat-pro'),
            __('Claude Chat', 'claude-chat-pro'),
            'manage_options',
            'claude-chat-pro',
            [$this, 'render_chat_page'],
            'dashicons-format-chat',
            30
        );

        // Podmenu
        add_submenu_page(
            'claude-chat-pro',
            __('Historia czatu', 'claude-chat-pro'),
            __('Historia', 'claude-chat-pro'),
            'manage_options',
            'claude-chat-history',
            [$this, 'render_history_page']
        );

        add_submenu_page(
            'claude-chat-pro',
            __('Repozytoria GitHub', 'claude-chat-pro'),
            __('Repozytoria', 'claude-chat-pro'),
            'manage_options',
            'claude-chat-repositories',
            [$this, 'render_repositories_page']
        );

        add_submenu_page(
            'claude-chat-pro',
            __('Ustawienia', 'claude-chat-pro'),
            __('Ustawienia', 'claude-chat-pro'),
            'manage_options',
            'claude-chat-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'claude-chat-pro',
            __('Diagnostyka', 'claude-chat-pro'),
            __('Diagnostyka', 'claude-chat-pro'),
            'manage_options',
            'claude-chat-diagnostics',
            [$this, 'render_diagnostics_page']
        );
    }

    /**
     * Ładowanie assetów
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'claude-chat') === false) {
            return;
        }

        // Style
        wp_enqueue_style(
            $this->plugin_name,
            CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            $this->version
        );

        // Highlight.js
        wp_enqueue_style(
            'highlight-js',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css',
            [],
            '11.9.0'
        );

        wp_enqueue_script(
            'highlight-js',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
            [],
            '11.9.0',
            true
        );

        // Admin scripts
        wp_enqueue_script(
            $this->plugin_name,
            CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery', 'highlight-js'],
            $this->version,
            true
        );

        // Lokalizacja dla JavaScript
        wp_localize_script($this->plugin_name, 'claudeChatPro', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('claude-chat-pro-nonce'),
            'currentUser' => wp_get_current_user()->user_login,
            'currentTimeUTC' => current_time('mysql', true),
            'strings' => [
                'error' => __('Wystąpił błąd!', 'claude-chat-pro'),
                'success' => __('Operacja zakończona sukcesem!', 'claude-chat-pro')
            ]
        ]);
    }

    /**
     * Renderowanie stron
     */
    public function render_chat_page() {
        require_once CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/views/chat-interface.php';
    }

    public function render_history_page() {
        require_once CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/views/chat-history.php';
    }

    public function render_repositories_page() {
        require_once CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/views/repositories.php';
    }

    public function render_settings_page() {
        require_once CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function render_diagnostics_page() {
        require_once CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/views/diagnostics.php';
    }

    /**
     * AJAX Handlers
     */
    public function handle_send_message() {
        check_ajax_referer('claude-chat-pro-nonce');

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        if (empty($message)) {
            wp_send_json_error(['message' => __('Wiadomość jest wymagana', 'claude-chat-pro')]);
        }

        try {
            $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
            $response = $claude_api->send_message($message);
            wp_send_json_success(['response' => $response]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_get_repositories() {
        check_ajax_referer('claude-chat-pro-nonce');

        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $repositories = $github_api->get_user_repos();
            wp_send_json_success(['repositories' => $repositories]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_get_file_content() {
        check_ajax_referer('claude-chat-pro-nonce');

        $repo = sanitize_text_field($_GET['repo'] ?? '');
        $path = sanitize_text_field($_GET['path'] ?? '');

        if (empty($repo) || empty($path)) {
            wp_send_json_error(['message' => __('Brak wymaganych parametrów', 'claude-chat-pro')]);
        }

        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $content = $github_api->get_file_content($repo, $path);
            wp_send_json_success(['content' => $content]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_check_status() {
        check_ajax_referer('claude-chat-pro-nonce');

        try {
            $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();

            $status = [
                'api' => [
                    'claude' => $claude_api->test_connection(),
                    'github' => $github_api->test_connection()
                ]
            ];

            wp_send_json_success($status);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}