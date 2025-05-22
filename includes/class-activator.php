<?php
namespace ClaudeChatPro\Includes;

class Activator {
    /**
     * Aktywacja wtyczki
     */
    public static function activate() {
        self::create_database_tables();
        self::set_default_options();
    }

    /**
     * Tworzenie tabel w bazie danych
     */
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela historii czatu
        $table_chat_history = $wpdb->prefix . 'claude_chat_history';
        $sql_chat_history = "CREATE TABLE IF NOT EXISTS $table_chat_history (
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
        $table_chat_meta = $wpdb->prefix . 'claude_chat_meta';
        $sql_chat_meta = "CREATE TABLE IF NOT EXISTS $table_chat_meta (
            meta_id bigint(20) NOT NULL AUTO_INCREMENT,
            chat_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext NOT NULL,
            PRIMARY KEY  (meta_id),
            KEY chat_id (chat_id),
            KEY meta_key (meta_key(191))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_chat_history);
        dbDelta($sql_chat_meta);
    }

    /**
     * Ustawianie domyślnych opcji
     */
    private static function set_default_options() {
        $default_options = [
            'claude_api_key' => '',
            'github_token' => '',
            'claude_model' => 'claude-2',
            'max_history_days' => 30,
            'debug_mode' => false
        ];

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}