<?php
namespace ClaudeChatPro\Includes;

/**
 * Klasa odpowiedzialna za bezpieczeństwo wtyczki
 */
class Security {
    /**
     * Inicjalizacja zabezpieczeń
     */
    public static function init() {
        // Sprawdź czy jesteśmy w wp-admin
        if (!is_admin()) {
            wp_die(__('Dostęp zabroniony', 'claude-chat-pro'));
        }

        // Sprawdź uprawnienia użytkownika
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz wystarczających uprawnień', 'claude-chat-pro'));
        }
    }

    /**
     * Sanityzacja danych wejściowych
     */
    public static function sanitize_input($data, $type = 'text') {
        switch ($type) {
            case 'text':
                return sanitize_text_field($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'int':
                return intval($data);
            case 'float':
                return floatval($data);
            case 'array':
                if (!is_array($data)) {
                    return [];
                }
                return array_map(function($item) {
                    return self::sanitize_input($item);
                }, $data);
            default:
                return sanitize_text_field($data);
        }
    }

    /**
     * Weryfikacja nonce
     */
    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa', 'claude-chat-pro')
            ]);
        }
        return true;
    }

    /**
     * Szyfrowanie wrażliwych danych
     */
    public static function encrypt($value) {
        if (empty($value)) {
            return '';
        }

        $cipher = "aes-256-cbc";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $key = wp_salt('auth');
        
        $encrypted = openssl_encrypt($value, $cipher, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Deszyfrowanie wrażliwych danych
     */
    public static function decrypt($encrypted_value) {
        if (empty($encrypted_value)) {
            return '';
        }

        $cipher = "aes-256-cbc";
        $ivlen = openssl_cipher_iv_length($cipher);
        $key = wp_salt('auth');
        
        $decoded = base64_decode($encrypted_value);
        $iv = substr($decoded, 0, $ivlen);
        $encrypted = substr($decoded, $ivlen);
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }

    /**
     * Walidacja klucza API
     */
    public static function validate_api_key($key) {
        return !empty($key) && preg_match('/^[a-zA-Z0-9_-]+$/', $key);
    }

    /**
     * Bezpieczne zapisywanie do pliku
     */
    public static function safe_file_put_contents($file, $content) {
        // Sprawdź czy plik istnieje i czy można do niego zapisywać
        if (file_exists($file) && !is_writable($file)) {
            return false;
        }

        // Sprawdź uprawnienia katalogu
        $dir = dirname($file);
        if (!is_writable($dir)) {
            return false;
        }

        // Zapisz plik atomowo
        $temp = tempnam($dir, 'tmp_');
        if (!$temp) {
            return false;
        }

        if (!file_put_contents($temp, $content)) {
            @unlink($temp);
            return false;
        }

        if (!rename($temp, $file)) {
            @unlink($temp);
            return false;
        }

        return true;
    }

    /**
     * Bezpieczne odczytywanie pliku
     */
    public static function safe_file_get_contents($file) {
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }

        return file_get_contents($file);
    }

    /**
     * Sprawdzanie bezpieczeństwa ścieżki
     */
    public static function is_safe_path($path) {
        // Normalizuj ścieżkę
        $path = str_replace('\\', '/', $path);
        
        // Sprawdź czy ścieżka nie zawiera niebezpiecznych elementów
        if (
            strpos($path, '../') !== false || 
            strpos($path, '..\\') !== false ||
            strpos($path, '~') !== false ||
            strpos($path, '/') === 0 ||
            preg_match('/[<>:"\\|?*]/', $path)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Sprawdzanie poprawności tokenu GitHub
     */
    public static function validate_github_token($token) {
        return !empty($token) && preg_match('/^gh[ps]_[a-zA-Z0-9_]+$/', $token);
    }

    /**
     * Logowanie błędów bezpieczeństwa
     */
    public static function log_security_issue($message, $data = []) {
        if (WP_DEBUG) {
            error_log(sprintf(
                '[Claude Chat Pro Security] %s - Data: %s',
                $message,
                json_encode($data)
            ));
        }
    }
}