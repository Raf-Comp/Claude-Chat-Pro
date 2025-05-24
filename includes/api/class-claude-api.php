<?php
namespace ClaudeChatPro\Includes\Api;

use ClaudeChatPro\Includes\Database\Settings_DB;

class Claude_Api {
    private $api_key;
    private $api_url = 'https://api.anthropic.com/v1/messages';
    private $api_version = '2023-06-01';

    public function __construct() {
        $encrypted_key = Settings_DB::get('claude_api_key', '');
        $this->api_key = !empty($encrypted_key) ? 
            \ClaudeChatPro\Includes\Security::decrypt($encrypted_key) : '';
    }

    /**
     * Wysyła wiadomość do API Claude
     *
     * @param array $messages Lista wiadomości [['role' => 'user|assistant', 'content' => 'treść']]
     * @param string $model Model Claude
     * @param int $max_tokens
     * @param float $temperature
     * @return array ['success' => bool, 'message' => string, 'tokens_used' => int]
     */
    public function send_message($messages, $model = 'claude-3-haiku-20240307', $max_tokens = 4000, $temperature = 0.7) {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => __('Klucz API Claude nie jest skonfigurowany.', 'claude-chat-pro')
            ];
        }

        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];

        $response = wp_remote_post(
            $this->api_url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => $this->api_version
                ],
                'body' => json_encode($data),
                'timeout' => 60
            ]
        );

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = isset($body['error']['message'])
                ? $body['error']['message']
                : __('Wystąpił błąd podczas komunikacji z API Claude.', 'claude-chat-pro');
            return [
                'success' => false,
                'message' => $error_message
            ];
        }

        return [
            'success' => true,
            'message' => $body['content'][0]['text'] ?? '',
            'tokens_used' => $body['usage']['output_tokens'] ?? 0
        ];
    }

    /**
     * Testuje połączenie z API Claude
     *
     * @return bool Czy połączenie działa
     */
    public function test_connection() {
        $test_messages = [
            [
                'role' => 'user',
                'content' => 'Odpowiedz krótko: test połączenia'
            ]
        ];

        $response = $this->send_message($test_messages, 'claude-3-haiku-20240307', 20, 0.7);

        return $response['success'];
    }

    /**
     * Pobiera dostępne modele Claude z API
     *
     * @return array Lista modeli [['id' => ..., 'display_name' => ...], ...]
     */
    public function get_available_models() {
        if (empty($this->api_key)) {
            return [];
        }
        $response = wp_remote_get('https://api.anthropic.com/v1/models', [
            'headers' => [
                'x-api-key' => $this->api_key,
                'anthropic-version' => $this->api_version,
                'content-type' => 'application/json',
                'accept' => 'application/json'
            ],
            'timeout' => 30
        ]);
        if (is_wp_error($response)) {
            return [];
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (!isset($data['data']) || !is_array($data['data'])) {
            return [];
        }
        // Zwracamy uproszczoną listę modeli
        $models = [];
        foreach ($data['data'] as $model) {
            $models[] = [
                'id' => $model['id'],
                'display_name' => $model['display_name'] ?? $model['id']
            ];
        }
        return $models;
    }

    /**
     * Pobiera listę dostępnych modeli z API Claude.
     * Zapisuje je w opcji 'claude_available_models'.
     */
    public function fetch_available_models() {
        $response = wp_remote_get($this->api_base_url . '/models', [
            'headers' => [
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
                'accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('Claude API Error: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['data']) && is_array($data['data'])) {
            $this->available_models = $data['data'];
            update_option('claude_available_models', $this->available_models);
        }
    }

    /**
     * Uploads a file to Claude API (template for future API support).
     * 
     * @param string $file_path Path to the file to upload.
     * @return array|WP_Error Response from API or error.
     */
    public function upload_file($file_path) {
        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', 'File not found: ' . $file_path);
        }

        $boundary = wp_generate_password(24, false);
        $headers = [
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'multipart/form-data; boundary=' . $boundary,
            'accept' => 'application/json'
        ];

        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file_path) . '"' . "\r\n";
        $body .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
        $body .= file_get_contents($file_path) . "\r\n";
        $body .= '--' . $boundary . '--' . "\r\n";

        $response = wp_remote_post($this->api_base_url . '/files', [
            'headers' => $headers,
            'body' => $body
        ]);

        if (is_wp_error($response)) {
            error_log('Claude API Error: ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
