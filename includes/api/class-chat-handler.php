// Dodaj w konstruktorze:
add_action('wp_ajax_claude_chat_refresh_models', [$this, 'handle_refresh_models']);

// Dodaj nową metodę:
public function handle_refresh_models() {
    check_ajax_referer('claude_chat_refresh_models', 'nonce');
    
    try {
        $this->claude_api->fetch_available_models();
        $models = $this->claude_api->get_available_models();
        
        wp_send_json_success([
            'models' => $models,
            'last_update' => wp_date(
                get_option('date_format') . ' ' . get_option('time_format'), 
                strtotime(get_option('claude_models_last_update'))
            )
        ]);
    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
    }
}