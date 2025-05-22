<?php
/**
 * Plugin Name: Claude Chat Pro
 * Plugin URI: https://github.com/raf-comp/claude-chat-pro
 * Description: Zaawansowana wtyczka do komunikacji z Claude AI z integracją GitHub
 * Version: 1.0.0
 * Author: Raf-Comp
 * Text Domain: claude-chat-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('CLAUDE_CHAT_PRO_VERSION', '1.0.0');
define('CLAUDE_CHAT_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLAUDE_CHAT_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'ClaudeChatPro\\') !== 0) {
        return;
    }

    $class = str_replace('ClaudeChatPro\\', '', $class);
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    $class_parts = explode(DIRECTORY_SEPARATOR, $class);
    $file_name = 'class-' . strtolower(str_replace('_', '-', end($class_parts))) . '.php';
    array_pop($class_parts);
    
    $file_path = CLAUDE_CHAT_PRO_PLUGIN_DIR . strtolower(implode(DIRECTORY_SEPARATOR, $class_parts)) . DIRECTORY_SEPARATOR . $file_name;
    
    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

// Aktywacja i dezaktywacja wtyczki
register_activation_hook(__FILE__, ['ClaudeChatPro\Includes\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['ClaudeChatPro\Includes\Deactivator', 'deactivate']);

// Inicjalizacja wtyczki
add_action('plugins_loaded', function() {
    if (version_compare(get_bloginfo('version'), '6.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            _e('Claude Chat Pro wymaga WordPress w wersji 6.0 lub nowszej.', 'claude-chat-pro');
            echo '</p></div>';
        });
        return;
    }

    new ClaudeChatPro\Includes\Core();
});

// Ładowanie tłumaczeń
add_action('init', function() {
    load_plugin_textdomain('claude-chat-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
});