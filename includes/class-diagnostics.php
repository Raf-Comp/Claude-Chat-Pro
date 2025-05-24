<?php
namespace ClaudeChatPro\Includes;

use ClaudeChatPro\Includes\Database\Settings_DB;

class Diagnostics {
    private $required_php_version = '7.4.0';
    private $required_wp_version = '6.0.0';
    private $plugin_tables;
    private $cache_group = 'claude_chat_diagnostics';
    
    public function __construct() {
        global $wpdb;
        $this->plugin_tables = [
            $wpdb->prefix . 'claude_chat_history',
            $wpdb->prefix . 'claude_chat_meta',
            $wpdb->prefix . 'claude_chat_settings'
        ];
    }

    /**
     * Sprawdzanie wymagań systemowych
     */
    public function check_system_requirements() {
        $cache_key = 'system_requirements';
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }

        $checks = [
            'php_version' => [
                'name' => __('Wersja PHP', 'claude-chat-pro'),
                'current' => PHP_VERSION,
                'required' => $this->required_php_version,
                'status' => version_compare(PHP_VERSION, $this->required_php_version, '>='),
                'label' => PHP_VERSION,
                'recommendation' => __('Zalecana wersja PHP 8.0+', 'claude-chat-pro')
            ],
            'wp_version' => [
                'name' => __('Wersja WordPress', 'claude-chat-pro'),
                'current' => get_bloginfo('version'),
                'required' => $this->required_wp_version,
                'status' => version_compare(get_bloginfo('version'), $this->required_wp_version, '>='),
                'label' => get_bloginfo('version'),
                'recommendation' => __('Zalecana najnowsza wersja WordPress', 'claude-chat-pro')
            ],
            'curl' => [
                'name' => __('Rozszerzenie cURL', 'claude-chat-pro'),
                'status' => function_exists('curl_version'),
                'label' => function_exists('curl_version') ? 
                    curl_version()['version'] : 
                    __('Niedostępne', 'claude-chat-pro'),
                'recommendation' => __('Wymagane do komunikacji z API', 'claude-chat-pro')
            ],
            'ssl' => [
                'name' => __('Wsparcie OpenSSL', 'claude-chat-pro'),
                'status' => extension_loaded('openssl'),
                'label' => extension_loaded('openssl') ? 
                    __('Dostępne', 'claude-chat-pro') : 
                    __('Niedostępne', 'claude-chat-pro'),
                'recommendation' => __('Wymagane do szyfrowania danych', 'claude-chat-pro')
            ],
            'json' => [
                'name' => __('Wsparcie JSON', 'claude-chat-pro'),
                'status' => function_exists('json_encode') && function_exists('json_decode'),
                'label' => function_exists('json_encode') ? 
                    __('Dostępne', 'claude-chat-pro') : 
                    __('Niedostępne', 'claude-chat-pro'),
                'recommendation' => __('Wymagane do przetwarzania danych API', 'claude-chat-pro')
            ],
            'memory_limit' => [
                'name' => __('Limit pamięci PHP', 'claude-chat-pro'),
                'status' => $this->check_memory_limit(),
                'label' => ini_get('memory_limit'),
                'required' => '128M',
                'recommendation' => __('Zalecane minimum 256MB', 'claude-chat-pro')
            ],
            'max_execution_time' => [
                'name' => __('Maksymalny czas wykonania', 'claude-chat-pro'),
                'status' => ini_get('max_execution_time') >= 30 || ini_get('max_execution_time') == 0,
                'label' => ini_get('max_execution_time') . 's',
                'required' => '30s',
                'recommendation' => __('Zalecane minimum 60s', 'claude-chat-pro')
            ],
            'upload_max_filesize' => [
                'name' => __('Maksymalny rozmiar pliku', 'claude-chat-pro'),
                'status' => $this->convert_to_bytes(ini_get('upload_max_filesize')) >= 1048576,
                'label' => ini_get('upload_max_filesize'),
                'required' => '1MB',
                'recommendation' => __('Zalecane minimum 10MB', 'claude-chat-pro')
            ],
            'post_max_size' => [
                'name' => __('Maksymalny rozmiar POST', 'claude-chat-pro'),
                'status' => $this->convert_to_bytes(ini_get('post_max_size')) >= 2097152,
                'label' => ini_get('post_max_size'),
                'required' => '2MB',
                'recommendation' => __('Zalecane minimum 20MB', 'claude-chat-pro')
            ],
            'mbstring' => [
                'name' => __('Rozszerzenie Multibyte String', 'claude-chat-pro'),
                'status' => extension_loaded('mbstring'),
                'label' => extension_loaded('mbstring') ? 
                    __('Dostępne', 'claude-chat-pro') : 
                    __('Niedostępne', 'claude-chat-pro'),
                'recommendation' => __('Zalecane dla obsługi Unicode', 'claude-chat-pro')
            ],
            'gd' => [
                'name' => __('Rozszerzenie GD', 'claude-chat-pro'),
                'status' => extension_loaded('gd'),
                'label' => extension_loaded('gd') ? 
                    __('Dostępne', 'claude-chat-pro') : 
                    __('Niedostępne', 'claude-chat-pro'),
                    'recommendation' => __('Przydatne do przetwarzania obrazów', 'claude-chat-pro')
                ],
                'zip' => [
                    'name' => __('Rozszerzenie ZIP', 'claude-chat-pro'),
                    'status' => extension_loaded('zip'),
                    'label' => extension_loaded('zip') ? 
                        __('Dostępne', 'claude-chat-pro') : 
                        __('Niedostępne', 'claude-chat-pro'),
                    'recommendation' => __('Przydatne do archiwizacji danych', 'claude-chat-pro')
                ]
            ];
     
            wp_cache_set($cache_key, $checks, $this->cache_group, 300); // Cache na 5 minut
            return $checks;
        }
     
        /**
         * Sprawdzanie połączeń API
         */
        public function check_api_connections() {
            $cache_key = 'api_connections';
            $cached = wp_cache_get($cache_key, $this->cache_group);
            
            if ($cached !== false) {
                return $cached;
            }
     
            $connections = [];
     
            try {
                $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
                $claude_result = $claude_api->test_connection();
                $api_key = Settings_DB::get('claude_api_key', '');
                
                $connections['claude'] = [
                    'name' => 'Claude AI API',
                    'status' => isset($claude_result['status']) ? $claude_result['status'] : false,
                    'message' => isset($claude_result['status']) && $claude_result['status']
                        ? __('Połączenie aktywne', 'claude-chat-pro')
                        : (isset($claude_result['error']) ? $claude_result['error'] : __('Błąd połączenia', 'claude-chat-pro')),
                    'last_tested' => current_time('mysql', true),
                    'endpoint' => 'https://api.anthropic.com/v1',
                    'configured' => !empty($api_key)
                ];
            } catch (\Exception $e) {
                $connections['claude'] = [
                    'name' => 'Claude AI API',
                    'status' => false,
                    'message' => sprintf(__('Błąd: %s', 'claude-chat-pro'), $e->getMessage()),
                    'last_tested' => current_time('mysql', true),
                    'endpoint' => 'https://api.anthropic.com/v1',
                    'configured' => false
                ];
            }
     
            try {
                $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
                $github_status = $github_api->test_connection();
                $github_token = get_option('github_token');
                $connections['github'] = [
                    'name' => 'GitHub API',
                    'status' => is_array($github_status) && isset($github_status['status']) ? $github_status['status'] : (is_bool($github_status) ? $github_status : false),
                    'message' => (is_array($github_status) && isset($github_status['status']) && $github_status['status']) || $github_status === true
                        ? __('Połączenie aktywne', 'claude-chat-pro')
                        : (is_array($github_status) && isset($github_status['error']) ? $github_status['error'] : __('Brak połączenia lub nieprawidłowy token', 'claude-chat-pro')),
                    'last_tested' => current_time('mysql', true),
                    'endpoint' => 'https://api.github.com',
                    'configured' => !empty($github_token)
                ];
            } catch (\Exception $e) {
                $connections['github'] = [
                    'name' => 'GitHub API',
                    'status' => false,
                    'message' => sprintf(__('Błąd: %s', 'claude-chat-pro'), $e->getMessage()),
                    'last_tested' => current_time('mysql', true),
                    'endpoint' => 'https://api.github.com',
                    'configured' => false
                ];
            }
     
            wp_cache_set($cache_key, $connections, $this->cache_group, 60); // Cache na 1 minutę
            return $connections;
        }
     
        /**
         * Sprawdzanie tabel bazy danych
         */
        public function check_database_tables() {
            global $wpdb;
            $cache_key = 'database_tables';
            $cached = wp_cache_get($cache_key, $this->cache_group);
            
            if ($cached !== false) {
                return $cached;
            }
     
            $results = [];
     
            foreach ($this->plugin_tables as $table) {
                // Sprawdź czy tabela istnieje
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                        DB_NAME,
                        $table
                    )
                );
     
                if ($exists) {
                    // Pobierz liczbę rekordów
                    $records = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    
                    $results[$table] = [
                        'exists' => true,
                        'records' => (int)$records,
                        'status' => 'OK'
                    ];
                } else {
                    $results[$table] = [
                        'exists' => false,
                        'records' => 0,
                        'status' => 'Missing'
                    ];
                }
            }
     
            wp_cache_set($cache_key, $results, $this->cache_group, 300); // Cache na 5 minut
            return $results;
        }
     
        /**
         * Naprawa tabel bazy danych
         */
        public function repair_database_tables() {
            global $wpdb;
            $results = [];
     
            foreach ($this->plugin_tables as $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                
                if ($exists) {
                    // Napraw tabelę
                    $repair_result = $wpdb->get_row("REPAIR TABLE {$table}", ARRAY_A);
                    
                    // Optymalizuj tabelę
                    $optimize_result = $wpdb->get_row("OPTIMIZE TABLE {$table}", ARRAY_A);
                    
                    $results[$table] = [
                        'repair_status' => $repair_result['Msg_text'] === 'OK',
                        'repair_message' => $repair_result['Msg_text'],
                        'optimize_status' => $optimize_result['Msg_text'] === 'OK',
                        'optimize_message' => $optimize_result['Msg_text'],
                        'status' => ($repair_result['Msg_text'] === 'OK' && $optimize_result['Msg_text'] === 'OK'),
                        'message' => ($repair_result['Msg_text'] === 'OK' && $optimize_result['Msg_text'] === 'OK') ? 
                            __('Tabela naprawiona i zoptymalizowana', 'claude-chat-pro') : 
                            __('Błąd podczas naprawy tabeli', 'claude-chat-pro')
                    ];
                } else {
                    // Spróbuj odtworzyć tabelę
                    try {
                        \ClaudeChatPro\Includes\Activator::activate();
                        $results[$table] = [
                            'status' => true,
                            'message' => __('Tabela została odtworzona', 'claude-chat-pro')
                        ];
                    } catch (\Exception $e) {
                        $results[$table] = [
                            'status' => false,
                            'message' => sprintf(__('Nie można odtworzyć tabeli: %s', 'claude-chat-pro'), $e->getMessage())
                        ];
                    }
                }
            }
     
            // Wyczyść cache po naprawie
            wp_cache_delete('database_tables', $this->cache_group);
            
            return $results;
        }
     
        /**
         * Export tabel do SQL
         */
        public function export_tables_sql() {
            global $wpdb;
            $output = "-- Claude Chat Pro Database Export\n";
            $output .= "-- Generated: " . current_time('mysql', true) . "\n";
            $output .= "-- WordPress Version: " . get_bloginfo('version') . "\n";
            $output .= "-- Plugin Version: " . CLAUDE_CHAT_PRO_VERSION . "\n\n";
     
            foreach ($this->plugin_tables as $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                
                if (!$exists) {
                    continue;
                }
     
                $output .= "-- --------------------------------------------------------\n";
                $output .= "-- Table structure for `{$table}`\n";
                $output .= "-- --------------------------------------------------------\n\n";
     
                // Dodaj strukturę tabeli
                $create_table = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);
                if ($create_table) {
                    $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $output .= $create_table[1] . ";\n\n";
     
                    // Dodaj dane
                    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    
                    if ($row_count > 0) {
                        $output .= "-- Dumping data for table `{$table}`\n";
                        $output .= "-- Rows: {$row_count}\n\n";
                        
                        $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
                        
                        if ($rows) {
                            $columns = array_keys($rows[0]);
                            $column_list = '`' . implode('`, `', $columns) . '`';
                            
                            $output .= "INSERT INTO `{$table}` ({$column_list}) VALUES\n";
                            
                            $values = [];
                            foreach ($rows as $row) {
                                $row_values = [];
                                foreach ($row as $value) {
                                    if (is_null($value)) {
                                        $row_values[] = 'NULL';
                                    } else {
                                        $row_values[] = "'" . $wpdb->_escape($value) . "'";
                                    }
                                }
                                $values[] = '(' . implode(', ', $row_values) . ')';
                            }
                            
                            $output .= implode(",\n", $values) . ";\n\n";
                        }
                    }
                }
            }
     
            return $output;
        }
     
        /**
         * Export tabel do CSV
         */
        public function export_tables_csv($table_name) {
            global $wpdb;
            
            if ($table_name === 'all') {
                // Eksportuj wszystkie tabele do jednego pliku CSV
                $output = fopen('php://temp', 'r+');
                foreach ($this->plugin_tables as $tbl) {
                    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$tbl}'");
                    if (!$exists) continue;
                    // Nagłówek z nazwą tabeli
                    fputcsv($output, ["Tabela: $tbl"]);
                    // Kolumny
                    $columns = $wpdb->get_col("SHOW COLUMNS FROM {$tbl}");
                    fputcsv($output, $columns);
                    // Dane
                    $rows = $wpdb->get_results("SELECT * FROM {$tbl}", ARRAY_N);
                    foreach ($rows as $row) {
                        fputcsv($output, $row);
                    }
                    fputcsv($output, []); // Pusta linia między tabelami
                }
                rewind($output);
                $csv = stream_get_contents($output);
                fclose($output);
                return $csv;
            }
            if (!in_array($table_name, $this->plugin_tables)) {
                throw new \Exception(__('Nieprawidłowa nazwa tabeli', 'claude-chat-pro'));
            }

            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            if (!$exists) {
                throw new \Exception(__('Tabela nie istnieje', 'claude-chat-pro'));
            }

            $output = fopen('php://temp', 'r+');

            // Pobierz nazwy kolumn
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_name}");
            fputcsv($output, $columns);

            // Pobierz dane w paczkach aby nie przeciążyć pamięci
            $offset = 0;
            $limit = 1000;
            
            do {
                $rows = $wpdb->get_results(
                    "SELECT * FROM {$table_name} LIMIT {$limit} OFFSET {$offset}",
                    ARRAY_N
                );
                
                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }
                
                $offset += $limit;
            } while (count($rows) === $limit);

            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);

            return $csv;
        }
     
        /**
         * Export tabel do JSON
         */
        public function export_tables_json($table_name) {
            global $wpdb;
            
            if ($table_name === 'all') {
                $data = [];
                foreach ($this->plugin_tables as $table) {
                    $data[basename($table)] = $this->export_single_table_json($table);
                }
            } else {
                if (!in_array($table_name, $this->plugin_tables)) {
                    throw new \Exception(__('Nieprawidłowa nazwa tabeli', 'claude-chat-pro'));
                }
                $data = $this->export_single_table_json($table_name);
            }
     
            return json_encode([
                'export_info' => [
                    'plugin' => 'Claude Chat Pro',
                    'version' => CLAUDE_CHAT_PRO_VERSION,
                    'wp_version' => get_bloginfo('version'),
                    'export_date' => current_time('mysql', true),
                    'site_url' => get_site_url()
                ],
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
     
        /**
         * Export pojedynczej tabeli do JSON
         */
        private function export_single_table_json($table_name) {
            global $wpdb;
            
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            if (!$exists) {
                return null;
            }
     
            // Pobierz strukturę tabeli
            $structure = $wpdb->get_results("DESCRIBE {$table_name}", ARRAY_A);
            
            // Pobierz dane
            $rows = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
            
            // Pobierz statystyki
            $stats = $wpdb->get_row(
                "SELECT COUNT(*) as row_count, 
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                 FROM information_schema.tables 
                 WHERE table_schema = DATABASE() AND table_name = '{$table_name}'",
                ARRAY_A
            );
     
            return [
                'table_name' => $table_name,
                'structure' => $structure,
                'rows' => $rows,
                'statistics' => $stats
            ];
        }
     
        /**
         * Sprawdzanie uprawnień plików
         */
        public function check_file_permissions() {
            $plugin_dir = CLAUDE_CHAT_PRO_PLUGIN_DIR;
            $results = [];
            $important_paths = [
                'claude-chat-pro.php',
                'admin/',
                'includes/',
                'admin/css/',
                'admin/js/',
                'admin/views/'
            ];
     
            foreach ($important_paths as $path) {
                $full_path = $plugin_dir . $path;
                
                if (!file_exists($full_path)) {
                    continue;
                }
     
                $relative_path = str_replace($plugin_dir, '', $full_path);
                $results[$relative_path] = [
                    'path' => $relative_path,
                    'full_path' => $full_path,
                    'exists' => file_exists($full_path),
                    'readable' => is_readable($full_path),
                    'writable' => is_writable($full_path),
                    'permissions' => substr(sprintf('%o', fileperms($full_path)), -4),
                    'owner' => function_exists('posix_getpwuid') && function_exists('fileowner') ? 
                        (posix_getpwuid(fileowner($full_path))['name'] ?? 'unknown') : 'unknown',
                    'size' => is_file($full_path) ? filesize($full_path) : 0,
                    'modified' => filemtime($full_path),
                    'type' => is_dir($full_path) ? 'directory' : 'file'
                ];
            }
     
            return $results;
        }
     
        /**
         * Sprawdzanie dostępności funkcji
         */
        public function check_function_availability() {
            $functions = [
                'curl_init' => __('cURL inicjalizacja', 'claude-chat-pro'),
                'json_encode' => __('JSON enkodowanie', 'claude-chat-pro'),
                'json_decode' => __('JSON dekodowanie', 'claude-chat-pro'),
                'openssl_encrypt' => __('OpenSSL szyfrowanie', 'claude-chat-pro'),
                'gzencode' => __('Kompresja GZIP', 'claude-chat-pro'),
                'mb_strlen' => __('Multibyte String', 'claude-chat-pro'),
                'file_get_contents' => __('Odczyt plików', 'claude-chat-pro'),
                'wp_remote_get' => __('WordPress HTTP GET', 'claude-chat-pro'),
                'wp_remote_post' => __('WordPress HTTP POST', 'claude-chat-pro')
            ];
            
            $results = [];
            foreach ($functions as $function => $name) {
                $results[$function] = [
                    'name' => $name,
                    'available' => function_exists($function),
                    'critical' => in_array($function, ['curl_init', 'json_encode', 'json_decode'])
                ];
            }
            
            return $results;
        }
     
        /**
         * Generowanie raportu diagnostycznego
         */
        public function generate_diagnostic_report() {
            $report = [
                'generated_at' => current_time('mysql', true),
                'plugin_version' => CLAUDE_CHAT_PRO_VERSION,
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'site_url' => get_site_url(),
                'system_requirements' => $this->check_system_requirements(),
                'api_connections' => $this->check_api_connections(),
                'database_tables' => $this->check_database_tables(),
                'file_permissions' => $this->check_file_permissions(),
                'function_availability' => $this->check_function_availability()
            ];
     
            return $report;
        }
     
        /**
         * Sprawdzanie limitu pamięci
         */
        private function check_memory_limit() {
            $memory_limit = ini_get('memory_limit');
            $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
            return $memory_limit_bytes >= 67108864; // Minimum 64MB
        }
     
        /**
         * Konwersja na bajty
         */
        private function convert_to_bytes($value) {
            $value = trim($value);
            if (empty($value)) {
                return 0;
            }
            
            $last = strtolower($value[strlen($value)-1]);
            $value = (int)$value;
            
            switch($last) {
                case 'g': $value *= 1024;
                case 'm': $value *= 1024;
                case 'k': $value *= 1024;
            }
            
            return $value;
        }
     
        /**
         * Formatowanie bajtów
         */
        public function format_bytes($size) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
                $size /= 1024;
            }
            return round($size, 2) . ' ' . $units[$i];
        }
     
        /**
         * Sprawdzanie healthcheck
         */
        public function health_check() {
            $issues = [];
            
            // Sprawdź wymagania systemowe
            $system_checks = $this->check_system_requirements();
            foreach ($system_checks as $key => $check) {
                if (!$check['status']) {
                    $issues[] = [
                        'type' => 'system',
                        'severity' => in_array($key, ['php_version', 'wp_version', 'curl', 'ssl']) ? 'critical' : 'warning',
                        'message' => sprintf(__('%s: %s', 'claude-chat-pro'), $check['name'], $check['recommendation'] ?? __('Wymaga uwagi', 'claude-chat-pro'))
                    ];
                }
            }
            
            // Sprawdź połączenia API
            $api_checks = $this->check_api_connections();
            foreach ($api_checks as $api => $check) {
                if (!$check['status'] && $check['configured']) {
                    $issues[] = [
                        'type' => 'api',
                        'severity' => 'warning',
                        'message' => sprintf(__('Problem z %s: %s', 'claude-chat-pro'), $check['name'], $check['message'])
                    ];
                }
            }
            
            // Sprawdź tabele bazy danych
            $db_checks = $this->check_database_tables();
            foreach ($db_checks as $table => $check) {
                if (!$check['status']) {
                    $issues[] = [
                        'type' => 'database',
                        'severity' => 'critical',
                        'message' => sprintf(__('Problem z tabelą %s: %s', 'claude-chat-pro'), $table, $check['message'])
                    ];
                }
            }
            
            return [
                'status' => empty($issues) ? 'healthy' : 'issues_found',
                'issues' => $issues,
                'summary' => [
                    'total_issues' => count($issues),
                    'critical_issues' => count(array_filter($issues, function($issue) { return $issue['severity'] === 'critical'; })),
                    'warning_issues' => count(array_filter($issues, function($issue) { return $issue['severity'] === 'warning'; }))
                ]
            ];
        }
     
        /**
         * Czyszczenie cache diagnostyki
         */
        public function clear_cache() {
            $cache_keys = [
                'system_requirements',
                'api_connections', 
                'database_tables',
                'file_permissions',
                'function_availability'
            ];
            
            foreach ($cache_keys as $key) {
                wp_cache_delete($key, $this->cache_group);
            }
            
            // Usuń transients
            delete_transient('claude_chat_system_info');
            delete_transient('claude_chat_api_status');
            delete_transient('claude_chat_health_check');
            
            return true;
        }
     }       