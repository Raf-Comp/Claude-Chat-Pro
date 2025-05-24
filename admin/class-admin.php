<?php
namespace ClaudeChatPro\Admin;

class Admin {
    private $plugin_name;
    private $version;
    private $settings;
    private $loader;

    public function __construct() {
        $this->plugin_name = 'claude-chat-pro';
        $this->version = CLAUDE_CHAT_PRO_VERSION;
        $this->loader = new \ClaudeChatPro\Includes\Loader();
        $this->settings = new Settings();
        $this->settings->register_hooks();

        $this->init_hooks();
    }

    /**
     * Inicjalizacja hooków
     */
    private function init_hooks() {
        // Dodawanie menu
        add_action('admin_menu', [$this, 'add_menu_pages']);
        
        // Ładowanie skryptów i stylów
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Obsługa AJAX
        add_action('wp_ajax_claude_send_message', [$this, 'handle_send_message']);
        add_action('wp_ajax_claude_get_repositories', [$this, 'handle_get_repositories']);
        add_action('wp_ajax_claude_run_diagnostics', [$this, 'handle_run_diagnostics']);
        // DIAGNOSTYKA
        add_action('wp_ajax_claude_get_system_requirements', [$this, 'handle_get_system_requirements']);
        add_action('wp_ajax_claude_get_api_connections', [$this, 'handle_get_api_connections']);
        add_action('wp_ajax_claude_get_database_tables', [$this, 'handle_get_database_tables']);
        add_action('wp_ajax_claude_run_performance_test', [$this, 'handle_run_performance_test']);
        add_action('wp_ajax_claude_repair_database_tables', [$this, 'handle_repair_database_tables']);
        add_action('wp_ajax_claude_export_diagnostic_report', [$this, 'handle_export_diagnostic_report']);
        add_action('wp_ajax_claude_export_data', [$this, 'handle_export_data']);
        // --- DODAJEMY OBSŁUGĘ DRZEWA REPOZYTORIUM ---
        add_action('wp_ajax_claude_get_repo_tree', [
            $this, 'handle_get_repo_tree'
        ]);
        add_action('wp_ajax_claude_get_file_content', [
            $this, 'handle_get_file_content'
        ]);
        // OBSŁUGA BRANCHY TYLKO DLA ZALOGOWANYCH ADMINISTRATORÓW
        add_action('wp_ajax_claude_get_branches', [
            $this, 'handle_get_branches'
        ]);
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
        // Ładowanie stylów i skryptów historii czatu
        if ($hook === 'claude-chat_page_claude-chat-history') {
            wp_enqueue_style(
                'claude-chat-history',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/chat-history.css',
                [],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/css/chat-history.css')
            );
            wp_enqueue_script(
                'claude-chat-history',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/chat-history.js',
                ['jquery'],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/js/chat-history.js'),
                true
            );
            wp_localize_script('claude-chat-history', 'claudeChat', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('claude_chat_nonce')
            ]);
        }
        
        // Ładowanie stylów i skryptów diagnostyki tylko na stronie diagnostyki
        if ($hook === 'claude-chat_page_claude-chat-diagnostics') {
            // Dodaj style WordPress admin
            wp_enqueue_style('wp-admin');
            
            // Dodaj style diagnostyki
            wp_enqueue_style(
                'claude-diagnostics',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/diagnostics.css',
                ['wp-admin'],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/css/diagnostics.css'),
                'all'
            );
            
            // Dodaj skrypty diagnostyki
            wp_enqueue_script(
                'claude-diagnostics',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/diagnostics.js',
                ['jquery'],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/js/diagnostics.js'),
                true
            );
            
            // Dodaj lokalizację skryptu
            wp_localize_script('claude-diagnostics', 'claudeDiagnostics', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('claude_diagnostics_nonce'),
                'pluginUrl' => CLAUDE_CHAT_PRO_PLUGIN_URL
            ]);
        }
        
        // Strona ustawień
        if ($hook === 'claude-chat-pro_page_claude-chat-settings') {
            // Ładowanie stylów
            wp_enqueue_style(
                'claude-settings',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/settings.css',
                [],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/css/settings.css')
            );

            // Ładowanie skryptów
            wp_enqueue_script(
                'claude-settings',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/settings.js',
                ['jquery'],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/js/settings.js'),
                true
            );

            // Lokalizacja skryptu
            wp_localize_script('claude-settings', 'claudeSettings', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('claude_settings_nonce'),
                'saving' => __('Zapisywanie...', 'claude-chat-pro'),
                'saved' => __('Zapisano!', 'claude-chat-pro'),
                'error' => __('Wystąpił błąd!', 'claude-chat-pro')
            ]);
        }
        
        // Strona czatu
        if ($hook === 'toplevel_page_claude-chat-pro') {
            // Ładowanie stylów
            wp_enqueue_style(
                'claude-chat',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/chat.css',
                [],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/css/chat.css')
            );

            // Ładowanie skryptów
            wp_enqueue_script(
                'claude-chat',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/chat.js',
                ['jquery'],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/js/chat.js'),
                true
            );

            // Lokalizacja skryptu
            wp_localize_script('claude-chat', 'claudeChatPro', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('claude_chat_nonce')
            ]);
        }

        if ($hook === 'claude-chat_page_claude-chat-repositories') {
            wp_enqueue_style(
                'claude-repositories',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/css/repositories.css',
                [],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/css/repositories.css')
            );
            wp_enqueue_script(
                'claude-repositories',
                CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/repositories.js',
                ['jquery'],
                filemtime(CLAUDE_CHAT_PRO_PLUGIN_DIR . 'admin/js/repositories.js'),
                true
            );
            // Przekaż nonce do JS
            wp_localize_script('claude-repositories', 'claudeRepo', [
                'nonce' => wp_create_nonce('claude_chat_github')
            ]);
        }
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

    /**
     * Renderowanie strony ustawień
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->settings->render_settings_page();
    }

    public function render_diagnostics_page() {
        // Inicjalizacja zmiennych diagnostycznych
        $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
        
        // Sprawdzenie statusu API Claude
        $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
        $claude_api_status = [
            'valid' => $claude_api->test_connection(),
            'message' => __('Sprawdź konfigurację API Claude', 'claude-chat-pro'),
            'details' => [
                'current_model' => 'claude-3-haiku-20240307',
                'model_available' => true,
                'models' => $claude_api->get_available_models()
            ]
        ];

        // Sprawdzenie statusu API GitHub
        $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
        $github_api_status = [
            'valid' => $github_api->test_connection(),
            'message' => __('Sprawdź konfigurację API GitHub', 'claude-chat-pro')
        ];

        // Sprawdzenie statusu bazy danych
        $database_status = $diagnostics->check_database_tables();

        // Pobranie zaleceń
        $recommendations = [];
        if (!$claude_api_status['valid']) {
            $recommendations[] = __('Skonfiguruj klucz API Claude w ustawieniach', 'claude-chat-pro');
        }
        if (!$github_api_status['valid']) {
            $recommendations[] = __('Skonfiguruj token GitHub w ustawieniach', 'claude-chat-pro');
        }

        // Załadowanie widoku
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
        check_ajax_referer('claude_chat_github', 'nonce');
        $repo = sanitize_text_field($_POST['repo'] ?? '');
        $branch = sanitize_text_field($_POST['branch'] ?? 'main');
        $path = sanitize_text_field($_POST['path'] ?? '');
        if (empty($repo) || empty($path)) {
            wp_send_json_error(['message' => __('Brak wymaganych parametrów', 'claude-chat-pro')]);
        }
        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $content = $github_api->get_file_content($repo, $path, $branch);
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
        check_ajax_referer('claude_diagnostics_nonce', 'nonce');
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
        error_log('handle_get_branches: nonce=' . ($_GET['nonce'] ?? 'brak'));
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

    public function handle_get_system_requirements() {
        check_ajax_referer('claude_diagnostics_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'claude-chat-pro')]);
        }
        $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
        $requirements = $diagnostics->check_system_requirements();
        wp_send_json_success($requirements);
    }

    public function handle_get_api_connections() {
        check_ajax_referer('claude_diagnostics_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'claude-chat-pro')]);
        }
        $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
        $connections = $diagnostics->check_api_connections();
        wp_send_json_success($connections);
    }

    public function handle_get_database_tables() {
        check_ajax_referer('claude_diagnostics_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'claude-chat-pro')]);
        }
        $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
        $tables = $diagnostics->check_database_tables();
        wp_send_json_success($tables);
    }

    public function handle_run_performance_test() {
        check_ajax_referer('claude_diagnostics_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'claude-chat-pro')]);
        }
        $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
        // Test API
        $api_start = microtime(true);
        $api_test = $diagnostics->check_api_connections();
        $api_time = round((microtime(true) - $api_start) * 1000, 2);
        // Test bazy danych
        $db_start = microtime(true);
        $db_test = $diagnostics->check_database_tables();
        $db_time = round((microtime(true) - $db_start) * 1000, 2);
        // Test pamięci
        $memory_start = memory_get_usage();
        $memory_test = $diagnostics->check_system_requirements();
        $memory_used = round((memory_get_usage() - $memory_start) / 1024 / 1024, 2);
        $results = [
            'api' => [
                'time' => $api_time,
                'status' => !empty($api_test['claude_api']['status']),
                'message' => $api_time < 1000 ? __('Szybkie', 'claude-chat-pro') : __('Wolne', 'claude-chat-pro')
            ],
            'database' => [
                'time' => $db_time,
                'status' => !empty($db_test),
                'message' => $db_time < 500 ? __('Szybkie', 'claude-chat-pro') : __('Wolne', 'claude-chat-pro')
            ],
            'memory' => [
                'used' => $memory_used,
                'status' => $memory_used < 10,
                'message' => $memory_used < 10 ? __('Optymalne', 'claude-chat-pro') : __('Wysokie zużycie', 'claude-chat-pro')
            ]
        ];
        wp_send_json_success($results);
    }

    public function handle_repair_database_tables() {
        check_ajax_referer('claude_diagnostics_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'claude-chat-pro')]);
        }
        global $wpdb;
        $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
        $tables = $diagnostics->check_database_tables();
        $repaired = [];
        foreach ($tables as $table => $info) {
            if (!$info['status']) {
                $wpdb->query("REPAIR TABLE {$table}");
                $repaired[] = $table;
            }
        }
        if (empty($repaired)) {
            wp_send_json_success(['message' => __('Wszystkie tabele są w dobrym stanie', 'claude-chat-pro')]);
        } else {
            wp_send_json_success([
                'message' => sprintf(
                    __('Naprawiono tabele: %s', 'claude-chat-pro'),
                    implode(', ', $repaired)
                ),
                'repaired' => $repaired
            ]);
        }
    }

    public function handle_export_diagnostic_report() {
        check_ajax_referer('claude_diagnostics_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'claude-chat-pro')]);
        }
        $diagnostics = new \ClaudeChatPro\Includes\Diagnostics();
        $report = $diagnostics->generate_diagnostic_report();
        wp_send_json_success([
            'report' => $report,
            'filename' => 'claude-chat-diagnostic-report-' . date('Y-m-d-H-i-s') . '.json'
        ]);
    }

    public function handle_get_repo_tree() {
        check_ajax_referer('claude_chat_github', 'nonce');
        $repo = sanitize_text_field($_POST['repo'] ?? '');
        $branch = sanitize_text_field($_POST['branch'] ?? 'main');
        $path = sanitize_text_field($_POST['path'] ?? '');
        try {
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            $items = $github_api->get_repository_tree($repo, $path, $branch);
            wp_send_json_success($items);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}