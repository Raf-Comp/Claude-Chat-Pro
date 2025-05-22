<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

// Inicjalizacja klasy historii
$chat_history = new \ClaudeChatPro\Includes\Database\Chat_History();

// Obsługa eksportu CSV
if (isset($_POST['export_csv']) && check_admin_referer('claude_chat_export_csv')) {
    $search_params = [
        'search' => sanitize_text_field($_POST['search'] ?? ''),
        'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
        'date_to' => sanitize_text_field($_POST['date_to'] ?? '')
    ];
    
    $csv = $chat_history->export_to_csv($search_params);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=chat-history-' . date('Y-m-d') . '.csv');
    echo $csv;
    exit;
}

// Parametry wyszukiwania
$search = sanitize_text_field($_GET['search'] ?? '');
$date_from = sanitize_text_field($_GET['date_from'] ?? '');
$date_to = sanitize_text_field($_GET['date_to'] ?? '');
$page = max(1, intval($_GET['paged'] ?? 1));

// Pobierz historię
$history = $chat_history->search_history([
    'search' => $search,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'page' => $page
]);
?>

<div class="wrap">
    <h1><?php _e('Historia rozmów', 'claude-chat-pro'); ?></h1>

    <!-- Formularz wyszukiwania -->
    <div class="tablenav top">
        <form method="get" class="search-form">
            <input type="hidden" name="page" value="claude-chat-history">
            
            <div class="alignleft actions">
                <input type="text" 
                       name="search" 
                       value="<?php echo esc_attr($search); ?>" 
                       placeholder="<?php _e('Szukaj w historii...', 'claude-chat-pro'); ?>"
                       class="regular-text">
                
                <input type="date" 
                       name="date_from" 
                       value="<?php echo esc_attr($date_from); ?>"
                       class="date-input"
                       placeholder="<?php _e('Od', 'claude-chat-pro'); ?>">
                
                <input type="date" 
                       name="date_to" 
                       value="<?php echo esc_attr($date_to); ?>"
                       class="date-input"
                       placeholder="<?php _e('Do', 'claude-chat-pro'); ?>">
                
                <input type="submit" 
                       class="button" 
                       value="<?php _e('Filtruj', 'claude-chat-pro'); ?>">
            </div>
        </form>

        <!-- Eksport do CSV -->
        <div class="alignright">
            <form method="post" class="export-form">
                <?php wp_nonce_field('claude_chat_export_csv'); ?>
                <input type="hidden" name="search" value="<?php echo esc_attr($search); ?>">
                <input type="hidden" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                <input type="hidden" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                <input type="submit" 
                       name="export_csv" 
                       class="button" 
                       value="<?php _e('Eksportuj do CSV', 'claude-chat-pro'); ?>">
            </form>
        </div>
        <br class="clear">
    </div>

    <!-- Tabela z historią -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Data (UTC)', 'claude-chat-pro'); ?></th>
                <th scope="col"><?php _e('Użytkownik', 'claude-chat-pro'); ?></th>
                <th scope="col"><?php _e('Typ', 'claude-chat-pro'); ?></th>
                <th scope="col"><?php _e('Treść', 'claude-chat-pro'); ?></th>
                <th scope="col"><?php _e('Załączniki', 'claude-chat-pro'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($history['items'])): ?>
                <tr>
                    <td colspan="5"><?php _e('Brak wyników.', 'claude-chat-pro'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($history['items'] as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item['created_at']); ?></td>
                        <td>
                            <?php 
                            $user_info = get_userdata($item['user_id']);
                            echo esc_html($user_info ? $user_info->user_login : 'N/A');
                            ?>
                        </td>
                        <td><?php echo esc_html($item['message_type']); ?></td>
                        <td>
                            <?php
                            // Wyświetl pierwsze 100 znaków treści
                            $content = wp_trim_words($item['message_content'], 20, '...');
                            echo esc_html($content);
                            ?>
                            <?php if (strlen($item['message_content']) > strlen($content)): ?>
                                <button type="button" 
                                        class="button-link show-full-content" 
                                        data-full-content="<?php echo esc_attr($item['message_content']); ?>">
                                    <?php _e('Pokaż więcej', 'claude-chat-pro'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $attachments = [];
                            if (!empty($item['files_data'])) {
                                $files = is_string($item['files_data']) ? 
                                    json_decode($item['files_data'], true) : 
                                    $item['files_data'];
                                if (is_array($files)) {
                                    foreach ($files as $file) {
                                        $attachments[] = esc_html($file['name']);
                                    }
                                }
                            }
                            if (!empty($item['github_data'])) {
                                $github_data = is_string($item['github_data']) ? 
                                    json_decode($item['github_data'], true) : 
                                    $item['github_data'];
                                if (is_array($github_data)) {
                                    foreach ($github_data as $data) {
                                        $attachments[] = sprintf(
                                            'GitHub: %s',
                                            esc_html($data['path'])
                                        );
                                    }
                                }
                            }
                            echo implode('<br>', $attachments);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginacja -->
    <?php if ($history['pages'] > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $history['pages'],
                    'current' => $page
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal z pełną treścią wiadomości -->
<div id="message-content-modal" class="claude-chat-modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?php _e('Pełna treść wiadomości', 'claude-chat-pro'); ?></h2>
        <div class="message-content"></div>
    </div>
</div>

<style>
/* Style dla historii rozmów */
.search-form {
    margin: 15px 0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.date-input {
    width: 150px;
}

.export-form {
    margin: 15px 0;
}

/* Style dla modalu */
.claude-chat-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    position: relative;
    border-radius: 4px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.show-full-content {
    color: #0073aa;
    text-decoration: underline;
    cursor: pointer;
}

/* Responsywność */
@media screen and (max-width: 782px) {
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .date-input {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Obsługa modalu z pełną treścią wiadomości
    const modal = $('#message-content-modal');
    const modalContent = modal.find('.message-content');
    const closeBtn = modal.find('.close');

    $('.show-full-content').on('click', function() {
        const fullContent = $(this).data('full-content');
        modalContent.text(fullContent);
        modal.show();
    });

    closeBtn.on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.hide();
        }
    });

    // Obsługa filtrów daty
    $('.date-input').on('change', function() {
        const dateFrom = $('input[name="date_from"]').val();
        const dateTo = $('input[name="date_to"]').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('<?php _e('Data początkowa nie może być późniejsza niż końcowa', 'claude-chat-pro'); ?>');
            $(this).val('');
        }
    });
});
</script>