<?php
namespace ClaudeChatPro\Includes;

class Security {
    /**
     * Inicjalizacja zabezpieczeń
     */
    public static function init() {
        if (!is_admin()) {
            wp_die(__('Dostęp zabroniony', 'claude-chat-pro'));
        }

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

        if (!extension_loaded('openssl')) {
            error_log('OpenSSL extension not available, storing value without encryption');
            return base64_encode($value);
        }

        $cipher = "aes-256-cbc";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $key = wp_salt('auth');
        
        $encrypted = openssl_encrypt($value, $cipher, $key, 0, $iv);
        if ($encrypted === false) {
            return base64_encode($value);
        }
        
        return base64_encode($iv . $encrypted);
    }

    /**
     * Deszyfrowanie wrażliwych danych
     */
    public static function decrypt($encrypted_value) {
        if (empty($encrypted_value)) {
            return '';
        }

        if (!extension_loaded('openssl')) {
            return base64_decode($encrypted_value);
        }

        $cipher = "aes-256-cbc";
        $ivlen = openssl_cipher_iv_length($cipher);
        $key = wp_salt('auth');
        
        $decoded = base64_decode($encrypted_value);
        if (strlen($decoded) < $ivlen) {
            return base64_decode($encrypted_value);
        }
        
        $iv = substr($decoded, 0, $ivlen);
        $encrypted = substr($decoded, $ivlen);
        
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        if ($decrypted === false) {
            return base64_decode($encrypted_value);
        }
        
        return $decrypted;
    }

    /**
     * Walidacja klucza API
     */
    public static function validate_api_key($key) {
        return !empty($key) && preg_match('/^[a-zA-Z0-9_-]+$/', $key);
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