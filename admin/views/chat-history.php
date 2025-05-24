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
    <div class="chat-history-container">
        <div class="chat-history-header">
            <h1 class="chat-history-title">Historia czatu</h1>
            <div class="dark-mode-toggle">
                <label class="switch">
                    <input type="checkbox" id="dark-mode-toggle">
                    <span class="slider round"></span>
                </label>
                <span class="dark-mode-label">Tryb ciemny</span>
            </div>
        </div>

        <div class="chat-history-filters">
            <div class="chat-history-search">
                <input type="text" placeholder="Szukaj w historii..." aria-label="Szukaj w historii">
            </div>
            <div class="chat-history-date-filter">
                <input type="date" id="start-date" aria-label="Data początkowa">
                <span>do</span>
                <input type="date" id="end-date" aria-label="Data końcowa">
            </div>
        </div>

        <div class="chat-history-list">
            <?php
            if (!empty($history['items'])) :
                foreach ($history['items'] as $item) :
                    $chat_id = $item['id'];
                    $chat_title = $item['title'];
                    $chat_date = $item['date'];
                    $chat_content = $item['content'];
            ?>
                <div class="chat-history-item" data-chat-id="<?php echo esc_attr($chat_id); ?>" data-date="<?php echo esc_attr($chat_date); ?>">
                    <div class="chat-history-item-header">
                        <h2 class="chat-history-item-title"><?php echo esc_html($chat_title); ?></h2>
                        <span class="chat-history-item-date"><?php echo esc_html($chat_date); ?></span>
                    </div>
                    <div class="chat-history-item-content">
                        <?php echo wp_kses_post($chat_content); ?>
                    </div>
                    <div class="chat-history-item-actions">
                        <button type="button" class="chat-history-button view-button">
                            <span class="dashicons dashicons-visibility"></span>
                            Podgląd
                        </button>
                        <button type="button" class="chat-history-button delete-button">
                            <span class="dashicons dashicons-trash"></span>
                            Usuń
                        </button>
                    </div>
                </div>
            <?php
                endforeach;
            else :
            ?>
                <div class="chat-history-empty">
                    <p>Brak historii czatu do wyświetlenia.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
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