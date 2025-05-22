<?php
namespace ClaudeChatPro\Includes\Api;

class Github_Api {
    private $api_base_url = 'https://api.github.com';
    private $token;
    private $username;
    private $client_headers;

    public function __construct() {
        $this->token = get_option('github_token');
        $this->username = get_option('github_username', '');
        $this->setup_headers();
    }

    /**
     * Konfiguracja nagłówków dla API GitHub
     */
    private function setup_headers() {
        $this->client_headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer ' . $this->token,
            'User-Agent' => 'Claude-Chat-Pro-WordPress-Plugin'
        ];
    }

    /**
     * Test połączenia z API GitHub
     */
    public function test_connection() {
        try {
            $response = wp_remote_get(
                $this->api_base_url . '/user',
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($body['login'])) {
                // Zapisz nazwę użytkownika do wykorzystania w przyszłości
                update_option('github_username', $body['login']);
                $this->username = $body['login'];
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
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

            if (!is_array($repos)) {
                throw new \Exception(__('Nieprawidłowa odpowiedź z API GitHub', 'claude-chat-pro'));
            }

            return array_map(function($repo) {
                return [
                    'id' => $repo['id'],
                    'name' => $repo['full_name'],
                    'description' => $repo['description'],
                    'url' => $repo['html_url'],
                    'default_branch' => $repo['default_branch'],
                    'visibility' => $repo['visibility'],
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
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

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
     * Wyszukiwanie plików w repozytorium
     */
    public function search_code($query, $repo = null) {
        try {
            $search_query = $query;
            if ($repo) {
                $search_query .= " repo:{$repo}";
            }

            $response = wp_remote_get(
                $this->api_base_url . '/search/code?q=' . urlencode($search_query),
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!isset($body['items'])) {
                return [];
            }

            return array_map(function($item) {
                return [
                    'name' => $item['name'],
                    'path' => $item['path'],
                    'repository' => $item['repository']['full_name'],
                    'url' => $item['html_url']
                ];
            }, $body['items']);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas wyszukiwania kodu: %s', 'claude-chat-pro'),
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

            if (!is_array($items)) {
                throw new \Exception(__('Nieprawidłowa odpowiedź z API GitHub', 'claude-chat-pro'));
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
     * Pobieranie informacji o gałęziach repozytorium
     */
    public function get_repository_branches($repo) {
        try {
            $response = wp_remote_get(
                $this->api_base_url . "/repos/{$repo}/branches",
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $branches = json_decode(wp_remote_retrieve_body($response), true);

            if (!is_array($branches)) {
                throw new \Exception(__('Nieprawidłowa odpowiedź z API GitHub', 'claude-chat-pro'));
            }

            return array_map(function($branch) {
                return [
                    'name' => $branch['name'],
                    'commit' => [
                        'sha' => $branch['commit']['sha'],
                        'url' => $branch['commit']['url']
                    ]
                ];
            }, $branches);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas pobierania gałęzi: %s', 'claude-chat-pro'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Pobieranie historii zmian dla pliku
     */
    public function get_file_history($repo, $path, $params = []) {
        $default_params = [
            'path' => $path,
            'per_page' => 10
        ];

        $params = array_merge($default_params, $params);
        $query = http_build_query($params);

        try {
            $response = wp_remote_get(
                $this->api_base_url . "/repos/{$repo}/commits?{$query}",
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $commits = json_decode(wp_remote_retrieve_body($response), true);

            if (!is_array($commits)) {
                throw new \Exception(__('Nieprawidłowa odpowiedź z API GitHub', 'claude-chat-pro'));
            }

            return array_map(function($commit) {
                return [
                    'sha' => $commit['sha'],
                    'message' => $commit['commit']['message'],
                    'author' => [
                        'name' => $commit['commit']['author']['name'],
                        'email' => $commit['commit']['author']['email'],
                        'date' => $commit['commit']['author']['date']
                    ],
                    'url' => $commit['html_url']
                ];
            }, $commits);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas pobierania historii pliku: %s', 'claude-chat-pro'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Sprawdzanie limitów API
     */
    public function get_rate_limit() {
        try {
            $response = wp_remote_get(
                $this->api_base_url . '/rate_limit',
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            return json_decode(wp_remote_retrieve_body($response), true);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas sprawdzania limitów API: %s', 'claude-chat-pro'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Pobieranie języków używanych w repozytorium
     */
    public function get_repository_languages($repo) {
        try {
            $response = wp_remote_get(
                $this->api_base_url . "/repos/{$repo}/languages",
                ['headers' => $this->client_headers]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            return json_decode(wp_remote_retrieve_body($response), true);

        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Błąd podczas pobierania języków: %s', 'claude-chat-pro'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Pobranie nazwy użytkownika
     */
    public function get_username() {
        return $this->username;
    }

    /**
     * Sprawdzenie czy token jest skonfigurowany
     */
    public function is_configured() {
        return !empty($this->token);
    }
}