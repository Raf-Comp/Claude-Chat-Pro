<?php
namespace ClaudeChatPro\Includes\Database;

class Settings_DB {
    private static $table = '';

    public static function table_name() {
        global $wpdb;
        if (!self::$table) {
            self::$table = $wpdb->prefix . 'claude_chat_settings';
        }
        return self::$table;
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            option_key VARCHAR(191) NOT NULL UNIQUE,
            option_value LONGTEXT NOT NULL
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function get($key, $default = null) {
        global $wpdb;
        $table = self::table_name();
        $value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $table WHERE option_key = %s", $key));
        return $value !== null ? maybe_unserialize($value) : $default;
    }

    public static function set($key, $value) {
        global $wpdb;
        $table = self::table_name();
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE option_key = %s", $key));
        if ($exists) {
            $wpdb->update($table, ['option_value' => maybe_serialize($value)], ['option_key' => $key]);
        } else {
            $wpdb->insert($table, ['option_key' => $key, 'option_value' => maybe_serialize($value)]);
        }
    }

    public static function delete($key) {
        global $wpdb;
        $table = self::table_name();
        $wpdb->delete($table, ['option_key' => $key]);
    }
} 