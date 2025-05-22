<?php
// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Usuń wszystkie tabele i opcje wtyczki
global $wpdb;

// Tabele do usunięcia
$tables = [
    $wpdb->prefix . 'claude_chat_history',
    $wpdb->prefix . 'claude_chat_meta'
];

// Usuń tabele
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Opcje do usunięcia
$options = [
    'claude_api_key',
    'github_token',
    'claude_default_model',
    'claude_available_models',
    'claude_models_last_update',
    'claude_chat_delete_tables_on_deactivation',
    'github_username'
];

// Usuń opcje
foreach ($options as $option) {
    delete_option($option);
}