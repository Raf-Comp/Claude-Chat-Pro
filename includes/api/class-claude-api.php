<?php
namespace ClaudeChatPro\Includes\Api;

class Claude_Api {
    private $api_key;
    private $api_base_url = 'https://api.anthropic.com/v1';
    private $available_models = [];

    public function __construct() {
        $this->api_key = get_option('claude_api_key');
        $this->fetch_available_models();
    }

    /**
     * Pobieranie dostępnych modeli
     */
    public function fetch_available_models() {
        try {
            $response = wp_remote_get(
                $this->api_base_url . '/models',
                [
                    'headers' => [
                        'x-api-key' => $this->api_key,
                        'anthropic-version' => '2023-06-01'
                    ]
                ]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($body['models'])) {
                $this->available_models = array_filter($body['models'], function($model) {
                    return strpos($model['id'], 'claude') !== false;
                });

                // Zapisz modele w opcjach WordPress
                update_option('claude_available_models', $this->available_models);
                update_option('claude_models_last_update', current_time('mysql', true));
            }

        } catch (\Exception $e) {
            // Jeśli nie udało się pobrać modeli, spróbuj użyć zapisanych
            $saved_models = get_option('claude_available_models', []);
            if (!empty($saved_models)) {
                $this->available_models = $saved_models;
            } else {
                // Domyślne modele jako fallback
                $this->available_models = [
                    ['id' => 'claude-3-opus-20240229'],
                    ['id' => 'claude-3-sonnet-20240229'],
                    ['id' => 'claude-3-haiku-20240229'],
                    ['id' => 'claude-2.1'],
                    ['id' => 'claude-2.0'],
                ];
            }
        }
    }

    /**
     * Wysyłanie wiadomości do API Claude
     */
    public function send_message($message, $attachments = [], $model = null) {
        if (empty($this->api_key)) {
            throw new \Exception(__('Klucz API Claude nie został skonfigurowany', 'claude-chat-pro'));
        }

        if (!$model) {
            $model = $this->get_default_model();
        }

        $messages = $this->prepare_messages($message, $attachments);

        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ];

        $response = wp_remote_post(
            $this->api_base_url . '/messages',
            [
                'headers' => [
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($data),
                'timeout' => 60
            ]
        );

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new \Exception($body['error']['message'] ?? __('Błąd API Claude', 'claude-chat-pro'));
        }

        return $this->format_response($body);
    }

    /**
     * Pobieranie listy dostępnych modeli
     */
    public function get_available_models() {
        // Sprawdź, czy minęło 24h od ostatniej aktualizacji
        $last_update = get_option('claude_models_last_update');
        if ($last_update) {
            $diff = strtotime(current_time('mysql', true)) - strtotime($last_update);
            if ($diff > 24 * HOUR_IN_SECONDS) {
                $this->fetch_available_models();
            }
        }

        return $this->available_models;
    }

    /**
     * Pobieranie domyślnego modelu
     */
    private function get_default_model() {
        $default_model = get_option('claude_default_model');
        if (!$default_model) {
            // Użyj najnowszego dostępnego modelu
            $models = $this->get_available_models();
            $default_model = !empty($models) ? $models[0]['id'] : 'claude-3-opus-20240229';
        }
        return $default_model;
    }

    /**
     * Przygotowanie wiadomości z załącznikami
     */
    private function prepare_messages($message, $attachments) {
        $messages = [
            [
                'role' => 'user',
                'content' => $message
            ]
        ];

        // Dodaj załączniki do wiadomości
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if ($attachment['type'] === 'file') {
                    $messages[] = [
                        'role' => 'user',
                        'content' => "Zawartość pliku {$attachment['name']}:\n\n{$attachment['content']}"
                    ];
                } elseif ($attachment['type'] === 'github') {
                    $messages[] = [
                        'role' => 'user',
                        'content' => "Kod z GitHub ({$attachment['name']}):\n\n{$attachment['content']}"
                    ];
                }
            }
        }

        return $messages;
    }

    /**
     * Formatowanie odpowiedzi od API
     */
    private function format_response($response) {
        if (isset($response['content'][0]['text'])) {
            return $response['content'][0]['text'];
        }
        throw new \Exception(__('Nieprawidłowa odpowiedź od API', 'claude-chat-pro'));
    }
}