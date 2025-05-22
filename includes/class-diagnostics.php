<?php
namespace ClaudeChatPro\Includes;

class Diagnostics {
    private $required_php_version = '7.4.0';
    private $required_wp_version = '6.0.0';
    private $plugin_tables;
    
    public function __construct() {
        global $wpdb;
        $this->plugin_tables = [
            $wpdb->prefix . 'claude_chat_history',
            $wpdb->prefix . 'claude_chat_meta'
        ];
    }

    /**
     * Sprawdzanie systemu
     */
    public function check_system_requirements() {
        return [
            'php_version' => [
                'name' => __('Wersja PHP', 'claude-chat-pro'),
                'current' => PHP_VERSION,
                'required' => $this->required_php_version,
                'status' => version_compare(PHP_VERSION, $this->required_php_version, '>='),
                'label' => PHP_VERSION
            ],
            'wp_version' => [
                'name' => __('Wersja WordPress', 'claude-chat-pro'),
                'current' => get_bloginfo('version'),
                'required' => $this->required_wp_version,
                'status' => version_compare(get_bloginfo('version'), $this->required_wp_version, '>='),
                'label' => get_bloginfo('version')
            ],
            'curl' => [
                'name' => __('Dostępność cURL', 'claude-chat-pro'),
                'status' => function_exists('curl_version'),
                'label' => function_exists('curl_version') ? curl_version()['version'] : __('Niedostępny', 'claude-chat-pro')
            ],
            'ssl' => [
                'name' => __('Wsparcie SSL', 'claude-chat-pro'),
                'status' => extension_loaded('openssl'),
                'label' => extension_loaded('openssl') ? __('Dostępne', 'claude-chat-pro') : __('Niedostępne', 'claude-chat-pro')
            ],
            'memory_limit' => [
                'name' => __('Limit pamięci PHP', 'claude-chat-pro'),
                'status' => $this->check_memory_limit(),
                'label' => ini_get('memory_limit')
            ],
            'max_execution_time' => [
                'name' => __('Maksymalny czas wykonania', 'claude-chat-pro'),
                'status' => ini_get('max_execution_time') >= 30,
                'label' => ini_get('max_execution_time') . 's'
            ],
            'upload_max_filesize' => [
                'name' => __('Maksymalny rozmiar pliku', 'claude-chat-pro'),
                'status' => true,
                'label' => ini_get('upload_max_filesize')
            ]
        ];
    }

    /**
     * Sprawdzanie API
     */
    public function check_api_connections() {
        $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
        $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();

        return [
            'claude' => [
                'name' => 'Claude AI API',
                'status' => $claude_api->test_connection(),
                'message' => $claude_api->test_connection() ? 
                    __('Połączenie aktywne', 'claude-chat-pro') : 
                    __('Brak połączenia', 'claude-chat-pro')
            ],
            'github' => [
                'name' => 'GitHub API',
                'status' => $github_api->test_connection(),
                'message' => $github_api->test_connection() ? 
                    __('Połączenie aktywne', 'claude-chat-pro') : 
                    __('Brak połączenia', 'claude-chat-pro')
            ]
        ];
    }

    /**
     * Sprawdzanie bazy danych
     */
    public function check_database_tables() {
        global $wpdb;
        $results = [];

        foreach ($this->plugin_tables as $table) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $table
                )
            );

            if ($exists) {
                // Sprawdź integralność tabeli
                $check_result = $wpdb->get_row(
                    "CHECK TABLE {$table}"
                );

                $results[$table] = [
                    'exists' => true,
                    'status' => $check_result->Msg_text === 'OK',
                    'message' => $check_result->Msg_text,
                    'rows' => $wpdb->get_var("SELECT COUNT(*) FROM {$table}")
                ];
            } else {
                $results[$table] = [
                    'exists' => false,
                    'status' => false,
                    'message' => __('Tabela nie istnieje', 'claude-chat-pro'),
                    'rows' => 0
                ];
            }
        }

        return $results;
    }

    /**
     * Naprawa tabel bazy danych
     */
    public function repair_database_tables() {
        global $wpdb;
        $results = [];

        foreach ($this->plugin_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'")) {
                $repair_result = $wpdb->get_row("REPAIR TABLE {$table}");
                $results[$table] = [
                    'status' => $repair_result->Msg_text === 'OK',
                    'message' => $repair_result->Msg_text
                ];
            }
        }

        return $results;
    }

    /**
     * Eksport tabel do SQL
     */
    public function export_tables_sql() {
        global $wpdb;
        $output = '';

        foreach ($this->plugin_tables as $table) {
            // Dodaj strukturę tabeli
            $create_table = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);
            if ($create_table) {
                $output .= "DROP TABLE IF EXISTS {$table};\n";
                $output .= $create_table[1] . ";\n\n";

                // Dodaj dane
                $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_N);
                if ($rows) {
                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($wpdb) {
                            return is_null($value) ? 'NULL' : $wpdb->prepare('%s', $value);
                        }, $row);
                        $output .= "INSERT INTO {$table} VALUES (" . implode(',', $values) . ");\n";
                    }
                }
                $output .= "\n";
            }
        }

        return $output;
    }

    /**
     * Eksport tabel do CSV
     */
    public function export_tables_csv($table) {
        global $wpdb;
        
        if (!in_array($table, $this->plugin_tables)) {
            return false;
        }

        $output = fopen('php://temp', 'r+');

        // Pobierz nazwy kolumn
        $columns = $wpdb->get_col("DESCRIBE {$table}");
        fputcsv($output, $columns);

        // Pobierz dane
        $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_N);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Sprawdzanie uprawnień plików
     */
    public function check_file_permissions() {
        $plugin_dir = CLAUDE_CHAT_PRO_PLUGIN_DIR;
        $results = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($plugin_dir)
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $path = $file->getPathname();
            $relative_path = str_replace($plugin_dir, '', $path);

            $results[$relative_path] = [
                'path' => $relative_path,
                'writable' => is_writable($path),
                'readable' => is_readable($path),
                'permissions' => substr(sprintf('%o', fileperms($path)), -4),
                'owner' => posix_getpwuid(fileowner($path))['name'] ?? 'unknown'
            ];
        }

        return $results;
    }

    /**
     * Sprawdzanie limitu pamięci
     */
    private function check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        return $memory_limit_bytes >= 64 * 1024 * 1024; // Minimum 64MB
    }

    /**
     * Konwersja na bajty
     */
    private function convert_to_bytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int)$value;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
}