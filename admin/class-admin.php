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
        
        // Podstawowe AJAX handlers
        add_action('wp_ajax_claude_chat_send_message', [$this, 'handle_send_message']);
        add_action('wp_ajax_claude_chat_get_repositories', [$this, 'handle_get_repositories']);
        add_action('wp_ajax_claude_chat_get_file_content', [$this, 'handle_get_file_content']);
        add_action('wp_ajax_claude_chat_check_status', [$this, 'handle_check_status']);
        
        // Nowe AJAX handlers dla diagnostyki
        add_action('wp_ajax_claude_chat_get_system_info', [$this, 'handle_get_system_info']);
        add_action('wp_ajax_claude_chat_get_database_info', [$this, 'handle_get_database_info']);
        add_action('wp_ajax_claude_chat_export_data', [$this, 'handle_export_data']);
        add_action('wp_ajax_claude_chat_repair_tables', [$this, 'handle_repair_tables']);
        add_action('wp_ajax_claude_chat_clear_cache', [$this, 'handle_clear_cache']);
        add_action('wp_ajax_claude_chat_test_specific_api', [$this, 'handle_test_specific_api']);
        add_action('wp_ajax_claude_chat_test_api', [$this, 'handle_test_api']);
        
        // GitHub AJAX handlers
        add_action('wp_ajax_claude_chat_get_branches', [$this, 'handle_get_branches']);
        add_action('wp_ajax_claude_chat_get_directory', [$this, 'handle_get_directory']);
        add_action('wp_ajax_claude_chat_get_file', [$this, 'handle_get_file']);

        // W metodzie init_hooks() dodaj:
        add_action('wp_ajax_claude_chat_health_check', [$this, 'handle_health_check']);
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

        // Podstawowe style
        wp_enqueue_style(
            $this->plugin_name,
            CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            $this->version
        );

        // Style diagnostyki
        if (strpos($hook, 'claude-chat-diagnostics') !== false) {
            wp_enqueue_style(
                'claude-chat-diagnostics',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/diagnostics.css',
                [],
                $this->version
            );
        }

        // Enhanced diagnostics script
        if (strpos($hook, 'claude-chat-diagnostics') !== false) {
            wp_enqueue_script(
                'claude-chat-diagnostics-enhanced',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/diagnostics-enhanced.js',
                ['claude-chat-diagnostics'],
                $this->version,
                true
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

        // Podstawowy admin script
        wp_enqueue_script(
            $this->plugin_name,
            CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery', 'highlight-js'],
            $this->version,
            true
        );

        // Script diagnostyki
        if (strpos($hook, 'claude-chat-diagnostics') !== false) {
            wp_enqueue_script(
                'claude-chat-diagnostics',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/diagnostics.js',
                ['jquery'],
                $this->version,
                true
            );
        }

        // Chat interface script
        if (strpos($hook, 'claude-chat-pro') !== false && !strpos($hook, 'settings') && !strpos($hook, 'diagnostics') && !strpos($hook, 'history') && !strpos($hook, 'repositories')) {
            wp_enqueue_script(
                'claude-chat-interface',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/chat-interface.js',
                ['jquery', 'highlight-js'],
                $this->version,
                true
            );
        }

        // Lokalizacja dla JavaScript
        wp_localize_script($this->plugin_name, 'claudeChatPro', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('claude-chat-pro-nonce'),
            'currentUser' => wp_get_current_user()->user_login,
            'currentTimeUTC' => current_time('mysql', true),
            'pluginVersion' => $this->version,
            'strings' => [
                'error' => __('Wystąpił błąd!', 'claude-chat-pro'),
                'success' => __('Operacja zakończona sukcesem!', 'claude-chat-pro'),
                'loading' => __('Ładowanie...', 'claude-chat-pro'),
                'confirm' => __('Czy jesteś pewien?', 'claude-chat-pro'),
                'cancel' => __('Anuluj', 'claude-chat-pro'),
                'save' => __('Zapisz', 'claude-chat-pro')
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
     * Podstawowe AJAX Handlers
     */
    public function handle_send_message() {
        check_ajax_referer('claude-chat-pro-nonce');

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        if (empty($message)) {
            wp_send_json_error(['message' => __('Wiadomość jest wymagana', 'claude-chat-pro')]);
        }

        try {
            $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
            
            // Obsługa załączników
            $attachments = [];
            if (isset($_FILES['files'])) {
                $attachments = $this->process_file_attachments($_FILES['files']);
            }
            
            if (isset($_POST['repo_files'])) {
                $repo_attachments = $this->process_repo_attachments($_POST['repo_files']);
                $attachments = array_merge($attachments, $repo_attachments);
            }
            
            $response = $claude_api->send_message($message, $attachments);
            
            // Zapisz w historii jeśli włączone
            if (get_option('claude_auto_save_history', true)) {
                $this->save_to_history($message, $attachments, $response);
            }
            
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
                    'claude' => [
                        'status' => $claude_api->test_connection(),
                        'message' => $claude_api->test_connection() ? 
                            __('Połączenie aktywne', 'claude-chat-pro') : 
                            __('Brak połączenia', 'claude-chat-pro')
                    ],
                    'github' => [
                        'status' => $github_api->test_connection(),
                        'message' => $github_api->test_connection() ? 
                            __('Połączenie aktywne', 'claude-chat-pro') : 
                            __('Brak połączenia', 'claude-chat-pro')
                    ]
                ]
            ];

            wp_send_json_success($status);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX Handlers dla diagnostyki
     */
    public function handle_get_system_info() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        try {
            $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
            $system_info = $diagnostics->check_system_requirements();
            
            wp_send_json_success($system_info);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_get_database_info() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        try {
            $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
            $database_info = $diagnostics->check_database_tables();
            
            wp_send_json_success($database_info);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_export_data() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $table = sanitize_text_field($_POST['table'] ?? 'all');
        
        try {
            $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
            
            switch ($format) {
                case 'sql':
                    $content = $diagnostics->export_tables_sql();
                    $filename = 'claude-chat-tables-' . date('Y-m-d-H-i-s') . '.sql';
                    $mime_type = 'text/sql';
                    break;
                    
                case 'json':
                    $content = $diagnostics->export_tables_json($table);
                    $filename = 'claude-chat-' . $table . '-' . date('Y-m-d-H-i-s') . '.json';
                    $mime_type = 'application/json';
                    break;
                    
                default: // csv
                    $content = $diagnostics->export_tables_csv($table);
                    $filename = 'claude-chat-' . $table . '-' . date('Y-m-d-H-i-s') . '.csv';
                    $mime_type = 'text/csv';
                    break;
            }
            
            wp_send_json_success([
                'content' => $content,
                'filename' => $filename,
                'mime_type' => $mime_type
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_repair_tables() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        try {
            $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
            $results = $diagnostics->repair_database_tables();
            
            wp_send_json_success([
                'results' => $results,
                'message' => __('Tabele zostały naprawione', 'claude-chat-pro')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_clear_cache() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        try {
            // Wyczyść cache WordPress
            wp_cache_flush();
            
            // Wyczyść cache obiektów
            if (function_exists('wp_cache_delete_group')) {
                wp_cache_delete_group('claude_chat_pro');
            }
            
            // Wyczyść cache wtyczki
            delete_transient('claude_chat_system_info');
            delete_transient('claude_chat_api_status');
            delete_transient('claude_available_models');
            
            wp_send_json_success([
                'message' => __('Cache został wyczyszczony', 'claude-chat-pro')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_test_specific_api() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        
        try {
            $status = false;
            $message = '';
            
            switch ($api_type) {
                case 'claude':
                    $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
                    $status = $claude_api->test_connection();
                    $message = $status ? 
                        __('Połączenie z Claude AI działa poprawnie', 'claude-chat-pro') :
                        __('Nie można połączyć się z Claude AI', 'claude-chat-pro');
                    break;
                    
                case 'github':
                    $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
                    $status = $github_api->test_connection();
                    $message = $status ? 
                        __('Połączenie z GitHub działa poprawnie', 'claude-chat-pro') :
                        __('Nie można połączyć się z GitHub', 'claude-chat-pro');
                    break;
                    
                default:
                    throw new \Exception(__('Nieznany typ API', 'claude-chat-pro'));
            }
            
            wp_send_json_success([
                'status' => $status,
                'message' => $message,
                'api_type' => $api_type
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_test_api() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        try {
            $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            
            wp_send_json_success([
                'claude' => $claude_api->test_connection(),
                'github' => $github_api->test_connection()
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * GitHub AJAX Handlers
     */
    public function handle_get_branches() {
        check_ajax_referer('claude_chat_github');
        
        $repo = sanitize_text_field($_GET['repo'] ?? '');
        
        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $branches = $github_api->get_repository_branches($repo);
            wp_send_json_success($branches);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_get_directory() {
        check_ajax_referer('claude_chat_github');
        
        $repo = sanitize_text_field($_GET['repo'] ?? '');
        $path = sanitize_text_field($_GET['path'] ?? '');
        $branch = sanitize_text_field($_GET['branch'] ?? 'main');
        
        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $items = $github_api->get_repository_tree($repo, $path, $branch);
            wp_send_json_success($items);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_get_file() {
        check_ajax_referer('claude_chat_github');
        
        $repo = sanitize_text_field($_GET['repo'] ?? '');
        $path = sanitize_text_field($_GET['path'] ?? '');
        $branch = sanitize_text_field($_GET['branch'] ?? 'main');
        
        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $content = $github_api->get_file_content($repo, $path, $branch);
            wp_send_json_success($content);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Pomocnicze metody
     */
    private function process_file_attachments($files) {
        $attachments = [];
        $max_size = get_option('claude_max_file_size', 1048576); // 1MB domyślnie
        
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'tmp_name' => [$files['tmp_name']],
                'size' => [$files['size']],
                'error' => [$files['error']]
            ];
        }
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            if ($files['size'][$i] > $max_size) {
                continue;
            }
            
            $content = file_get_contents($files['tmp_name'][$i]);
            if ($content !== false) {
                $attachments[] = [
                    'type' => 'file',
                    'name' => sanitize_file_name($files['name'][$i]),
                    'content' => $content
                ];
            }
        }
        
        return $attachments;
    }

    private function process_repo_attachments($repo_files) {
        $attachments = [];
        
        if (is_array($repo_files)) {
            foreach ($repo_files as $repo_file) {
                $data = json_decode(stripslashes($repo_file), true);
                if ($data) {
                    $attachments[] = [
                        'type' => 'github',
                        'name' => $data['path'],
                        'content' => $data['content']
                    ];
                }
            }
        }
        
        return $attachments;
    }

    private function save_to_history($message, $attachments, $response) {
        try {
            $chat_history = new \ClaudeChatPro\Includes\Database\Chat_History();
            
            // Zapisz wiadomość użytkownika
            $chat_history->save_message([
                'message_content' => $message,
                'message_type' => 'user',
                'files_data' => $this->extract_file_data($attachments),
                'github_data' => $this->extract_github_data($attachments)
            ]);
            
            // Zapisz odpowiedź Claude
            $chat_history->save_message([
                'message_content' => $response,
                'message_type' => 'assistant'
            ]);
            
        } catch (\Exception $e) {
            error_log('Claude Chat Pro: Error saving to history - ' . $e->getMessage());
        }
    }

    private function extract_file_data($attachments) {
        $files = [];
        foreach ($attachments as $attachment) {
            if ($attachment['type'] === 'file') {
                $files[] = [
                    'name' => $attachment['name'],
                    'size' => strlen($attachment['content'])
                ];
            }
        }
        return empty($files) ? null : $files;
    }

    private function extract_github_data($attachments) {
        $github_data = [];
        foreach ($attachments as $attachment) {
            if ($attachment['type'] === 'github') {
                $github_data[] = [
                    'path' => $attachment['name'],
                    'size' => strlen($attachment['content'])
                ];
            }
        }
        return empty($github_data) ? null : $github_data;
    }
    public function handle_health_check() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        try {
            $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
            $health_data = $diagnostics->health_check();
            
            wp_send_json_success($health_data);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}