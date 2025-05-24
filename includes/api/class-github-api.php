<?php
namespace ClaudeChatPro\Includes\Api;

class Github_Api {
    private $api_base_url = 'https://api.github.com';
    private $token;
    private $username;
    private $client_headers;

    public function __construct($token = null) {
        if ($token !== null) {
            $this->token = $token;
        } else {
            $encrypted_token = \ClaudeChatPro\Includes\Database\Settings_DB::get('claude_github_token', '');
            $this->token = !empty($encrypted_token) ? 
                \ClaudeChatPro\Includes\Security::decrypt($encrypted_token) : '';
        }
        $this->username = get_option('github_username', '');
        $this->setup_headers();
    }

    /**
     * Konfiguracja nagłówków dla API GitHub
     */
    private function setup_headers() {
        $this->client_headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'token ' . $this->token,
            'User-Agent' => 'Claude-Chat-Pro-WordPress-Plugin'
        ];
    }

    /**
     * Test połączenia z API GitHub
     */
    public function test_connection() {
        if (empty($this->token)) {
            return ['status' => false, 'error' => 'Token GitHub jest pusty.'];
        }

        try {
            $response = wp_remote_get(
                $this->api_base_url . '/user',
                [
                    'headers' => $this->client_headers,
                    'timeout' => 10
                ]
            );

            if (is_wp_error($response)) {
                error_log('GitHub API WP Error: ' . $response->get_error_message());
                return ['status' => false, 'error' => $response->get_error_message()];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $headers = wp_remote_retrieve_headers($response);
            error_log('GitHub API response code: ' . $status_code);
            error_log('GitHub API response body: ' . $body);
            error_log('GitHub API response headers: ' . print_r($headers, true));

            if ($status_code === 200) {
                $body_arr = json_decode($body, true);
                if (isset($body_arr['login'])) {
                    update_option('github_username', $body_arr['login']);
                    $this->username = $body_arr['login'];
                    return ['status' => true];
                }
            }

            return ['status' => false, 'error' => 'Nieprawidłowa odpowiedź z API GitHub (kod: ' . $status_code . ')'];
        } catch (\Exception $e) {
            error_log('GitHub API Exception: ' . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Pobieranie repozytoriów użytkownika
     */
    public function get_user_repos($params = []) {
        $default_params = [
            'sort' => 'updated',
            'direction' => 'desc',
            'per_page' => 100,
            'page' => 1
        ];

        $params = array_merge($default_params, $params);
        $query = http_build_query($params);

        try {
            $response = wp_remote_get(
                $this->api_base_url . '/user/repos?' . $query,
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $repos = json_decode(wp_remote_retrieve_body($response), true);

            if (!is_array($repos) || isset($repos['message'])) {
                // Zwróć błąd z message, jeśli jest
                $msg = isset($repos['message']) ? $repos['message'] : __('Nieprawidłowa odpowiedź z API GitHub', 'claude-chat-pro');
                throw new \Exception($msg);
            }
            // Jeśli $repos nie jest tablicą indeksowaną, zwróć pustą tablicę
            if (array_values($repos) !== $repos) {
                return [];
            }
            return array_map(function($repo) {
                return [
                    'id' => $repo['id'],
                    'name' => $repo['full_name'],
                    'description' => $repo['description'],
                    'url' => $repo['html_url'],
                    'default_branch' => $repo['default_branch'],
                    'visibility' => $repo['visibility'] ?? ($repo['private'] ? 'private' : 'public'),
                    'updated_at' => $repo['updated_at'],
                    'language' => $repo['language'],
                    'permissions' => $repo['permissions'] ?? []
                ];
            }, $repos);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas pobierania repozytoriów: %s', 'claude-chat-pro'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Pobieranie zawartości pliku z repozytorium
     */
    public function get_file_content($repo, $path, $ref = null) {
        try {
            $url = $this->api_base_url . "/repos/{$repo}/contents/{$path}";
            if ($ref) {
                $url .= "?ref=" . urlencode($ref);
            }

            $response = wp_remote_get(
                $url,
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                error_log('GitHub API error: ' . $response->get_error_message());
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            // LOGUJEMY ODPOWIEDŹ Z GITHUBA
            error_log('GitHub API response for file ' . $repo . '/' . $path . ': ' . print_r($body, true));

            if (!isset($body['content'])) {
                throw new \Exception(__('Nie można pobrać zawartości pliku', 'claude-chat-pro'));
            }

            return base64_decode($body['content']);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas pobierania pliku %s: %s', 'claude-chat-pro'),
                    $path,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Pobieranie struktury katalogu w repozytorium
     */
    public function get_repository_tree($repo, $path = '', $ref = null) {
        try {
            $url = $this->api_base_url . "/repos/{$repo}/contents/{$path}";
            if ($ref) {
                $url .= "?ref=" . urlencode($ref);
            }

            $response = wp_remote_get(
                $url,
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $items = json_decode(wp_remote_retrieve_body($response), true);

            if (!is_array($items) || isset($items['message'])) {
                // Zwróć błąd z message, jeśli jest
                $msg = isset($items['message']) ? $items['message'] : __('Nieprawidłowa odpowiedź z API GitHub', 'claude-chat-pro');
                throw new \Exception($msg);
            }

            return array_map(function($item) {
                return [
                    'name' => $item['name'],
                    'path' => $item['path'],
                    'type' => $item['type'],
                    'size' => $item['size'] ?? 0,
                    'url' => $item['html_url']
                ];
            }, $items);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas pobierania struktury katalogu: %s', 'claude-chat-pro'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Pobieranie branchy repozytorium
     */
    public function get_repository_branches($repo) {
        try {
            $url = $this->api_base_url . "/repos/{$repo}/branches";
            $response = wp_remote_get(
                $url,
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $branches = json_decode(wp_remote_retrieve_body($response), true);

            if (!is_array($branches) || isset($branches['message'])) {
                $msg = isset($branches['message']) ? $branches['message'] : __('Nieprawidłowa odpowiedź z API GitHub', 'claude-chat-pro');
                throw new \Exception($msg);
            }

            return array_map(function($branch) {
                return [
                    'name' => $branch['name'],
                    'commit' => $branch['commit']['sha']
                ];
            }, $branches);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas pobierania branchy: %s', 'claude-chat-pro'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Sprawdzenie czy token jest skonfigurowany
     */
    public function is_configured() {
        return !empty($this->token);
    }

    /**
     * Pobranie nazwy użytkownika
     */
    public function get_username() {
        return $this->username;
    }
}