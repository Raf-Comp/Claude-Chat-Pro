<?php
namespace ClaudeChatPro\Includes;

class Deactivator {
    /**
     * Czyszczenie po dezaktywacji wtyczki
     */
    public static function deactivate() {
        // Usuń tabele tylko jeśli użytkownik tego chce
        if (get_option('claude_chat_delete_tables_on_deactivation', false)) {
            self::drop_tables();
        }

        // Usuń wszystkie opcje wtyczki
        self::delete_options();
    }

    /**
     * Usuwanie tabel
     */
    private static function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'claude_chat_history',
            $wpdb->prefix . 'claude_chat_meta'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Usuwanie opcji
     */
    private static function delete_options() {
        $options = [
            'claude_api_key',
            'github_token',
            'claude_default_model',
            'claude_available_models',
            'claude_models_last_update',
            'claude_chat_delete_tables_on_deactivation',
            'github_username'
        ];

        foreach ($options as $option) {
            delete_option($option);
        }
    }
}