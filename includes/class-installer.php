<?php
namespace ClaudeChatPro\Includes;

class Claude_Installer {
    public static function activate() {
        self::create_tables();
        self::set_default_options();
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela ustawień
        $table_settings = $wpdb->prefix . 'claude_chat_settings';
        $sql_settings = "CREATE TABLE IF NOT EXISTS $table_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            option_key VARCHAR(191) NOT NULL UNIQUE,
            option_value LONGTEXT NOT NULL
        ) $charset_collate;";

        // Tabela historii czatu
        $table_history = $wpdb->prefix . 'claude_chat_history';
        $sql_history = "CREATE TABLE IF NOT EXISTS $table_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            message_content longtext NOT NULL,
            message_type varchar(20) NOT NULL,
            files_data longtext,
            github_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Tabela metadanych czatu
        $table_meta = $wpdb->prefix . 'claude_chat_meta';
        $sql_meta = "CREATE TABLE IF NOT EXISTS $table_meta (
            meta_id bigint(20) NOT NULL AUTO_INCREMENT,
            chat_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext NOT NULL,
            PRIMARY KEY  (meta_id),
            KEY chat_id (chat_id),
            KEY meta_key (meta_key(191))
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_settings);
        dbDelta($sql_history);
        dbDelta($sql_meta);

        return true;
    }

    public static function set_default_options() {
        $default_options = [
            'claude_auto_save_history' => 1,
            'claude_debug_mode' => 0,
            'claude_enable_github' => 1,
            'claude_theme' => 'light',
            'claude_allowed_file_extensions' => 'txt,pdf,php,js,css,html,json,md',
            'claude_auto_purge_enabled' => 0,
            'claude_auto_purge_days' => 30,
            // ... inne opcje
        ];
        foreach ($default_options as $key => $value) {
            \ClaudeChatPro\Includes\Database\Settings_DB::set($key, $value);
        }
    }
} 