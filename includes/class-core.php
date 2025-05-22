<?php
namespace ClaudeChatPro\Includes;

class Core {
    private $loader;
    
    public function __construct() {
        $this->loader = new Loader();
        $this->init_hooks();
        $this->loader->run();
    }

    private function init_hooks() {
        if (is_admin()) {
            new \ClaudeChatPro\Admin\Admin();
        }

        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_public_scripts');
        $this->loader->add_action('wp_ajax_claude_chat_test_api', $this, 'test_api_connection');
    }

    public function enqueue_public_scripts() {
        // Skrypty dla frontendu (jeśli potrzebne w przyszłości)
    }

    public function test_api_connection() {
        check_ajax_referer('claude-chat-pro-nonce');
        
        try {
            $claude_api = new \ClaudeChatPro\Includes\Api\Claude_Api();
            $github_api = new \ClaudeChatPro\Includes\Api\Github_Api();
            
            wp_send_json_success([
                'claude' => $claude_api->test_connection(),
                'github' => $github_api->test_connection()
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}