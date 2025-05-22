<?php
namespace ClaudeChatPro\Includes\Database;

class Chat_History {
    private $table_name;
    private $meta_table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'claude_chat_history';
        $this->meta_table_name = $wpdb->prefix . 'claude_chat_meta';
    }

    /**
     * Zapisywanie wiadomości w historii
     */
    public function save_message($data) {
        global $wpdb;

        $defaults = [
            'user_id' => get_current_user_id(),
            'message_content' => '',
            'message_type' => 'user', // user lub assistant
            'files_data' => null,
            'github_data' => null,
            'created_at' => current_time('mysql', true)
        ];

        $data = wp_parse_args($data, $defaults);
        
        // Konwersja tablic na JSON
        if (is_array($data['files_data'])) {
            $data['files_data'] = json_encode($data['files_data']);
        }
        if (is_array($data['github_data'])) {
            $data['github_data'] = json_encode($data['github_data']);
        }

        $result = $wpdb->insert(
            $this->table_name,
            $data,
            [
                '%d',    // user_id
                '%s',    // message_content
                '%s',    // message_type
                '%s',    // files_data
                '%s',    // github_data
                '%s'     // created_at
            ]
        );

        if ($result === false) {
            throw new \Exception($wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Wyszukiwanie w historii rozmów
     */
    public function search_history($params = []) {
        global $wpdb;

        $defaults = [
            'search' => '',
            'date_from' => '',
            'date_to' => '',
            'user_id' => '',
            'message_type' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $params = wp_parse_args($params, $defaults);
        $where = [];
        $values = [];

        // Wyszukiwanie po słowie kluczowym
        if (!empty($params['search'])) {
            $where[] = 'message_content LIKE %s';
            $values[] = '%' . $wpdb->esc_like($params['search']) . '%';
        }

        // Filtrowanie po dacie
        if (!empty($params['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $params['date_to'];
        }

        // Filtrowanie po użytkowniku
        if (!empty($params['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $params['user_id'];
        }

        // Filtrowanie po typie wiadomości
        if (!empty($params['message_type'])) {
            $where[] = 'message_type = %s';
            $values[] = $params['message_type'];
        }

        // Budowanie zapytania
        $sql = "SELECT * FROM {$this->table_name}";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Sortowanie
        $sql .= $wpdb->prepare(" ORDER BY %s %s", 
            $params['orderby'],
            $params['order']
        );

        // Paginacja
        $offset = ($params['page'] - 1) * $params['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        );

        // Wykonanie zapytania
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        // Pobierz całkowitą liczbę wyników (bez paginacji)
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name}";
        if (!empty($where)) {
            $count_sql .= ' WHERE ' . implode(' AND ', $where);
        }
        if (!empty($values)) {
            $count_sql = $wpdb->prepare($count_sql, $values);
        }
        $total = $wpdb->get_var($count_sql);

        // Przetwarzanie wyników
        foreach ($results as &$result) {
            if (!empty($result['files_data'])) {
                $result['files_data'] = json_decode($result['files_data'], true);
            }
            if (!empty($result['github_data'])) {
                $result['github_data'] = json_decode($result['github_data'], true);
            }
        }

        return [
            'items' => $results,
            'total' => (int) $total,
            'pages' => ceil($total / $params['per_page'])
        ];
    }

    /**
     * Usuwanie historii rozmów
     */
    public function delete_history($ids) {
        global $wpdb;

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)",
                $ids
            )
        );
    }

    /**
     * Eksport historii do CSV
     */
    public function export_to_csv($params = []) {
        $results = $this->search_history(array_merge(
            $params,
            ['per_page' => 1000000] // Duża liczba, aby pobrać wszystkie wyniki
        ));

        $output = fopen('php://temp', 'r+');

        // Nagłówki CSV
        fputcsv($output, [
            'ID',
            'Data UTC',
            'Użytkownik',
            'Typ',
            'Treść',
            'Załączniki',
            'Dane GitHub'
        ]);

        foreach ($results['items'] as $row) {
            $user_info = get_userdata($row['user_id']);
            $username = $user_info ? $user_info->user_login : 'N/A';

            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $username,
                $row['message_type'],
                $row['message_content'],
                is_array($row['files_data']) ? json_encode($row['files_data']) : $row['files_data'],
                is_array($row['github_data']) ? json_encode($row['github_data']) : $row['github_data']
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}