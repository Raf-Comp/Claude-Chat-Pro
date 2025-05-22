<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

$diagnostics = new \ClaudeChatPro\Includes\Diagnostics();

// Obsługa eksportu
if (isset($_POST['export_tables']) && check_admin_referer('claude_chat_export_tables')) {
    $format = sanitize_text_field($_POST['export_format']);
    $table = sanitize_text_field($_POST['table']);
    
    if ($format === 'sql') {
        $content = $diagnostics->export_tables_sql();
        $filename = 'claude-chat-tables-' . date('Y-m-d') . '.sql';
        header('Content-Type: text/sql; charset=utf-8');
    } else {
        $content = $diagnostics->export_tables_csv($table);
        $filename = 'claude-chat-' . $table . '-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
    }
    
    header('Content-Disposition: attachment; filename=' . $filename);
    echo $content;
    exit;
}

// Obsługa naprawy tabel
if (isset($_POST['repair_tables']) && check_admin_referer('claude_chat_repair_tables')) {
    $repair_results = $diagnostics->repair_database_tables();
}

// Pobierz dane diagnostyczne
$system_checks = $diagnostics->check_system_requirements();
$api_connections = $diagnostics->check_api_connections();
$database_tables = $diagnostics->check_database_tables();
$file_permissions = $diagnostics->check_file_permissions();
?>

<div class="wrap claude-chat-diagnostics">
    <h1><?php _e('Diagnostyka Claude Chat Pro', 'claude-chat-pro'); ?></h1>

    <!-- Informacje o użytkowniku i czasie -->
    <div class="info-box">
        <div class="info-item">
            <span class="dashicons dashicons-admin-users"></span>
            <strong><?php _e('Użytkownik:', 'claude-chat-pro'); ?></strong>
            <?php echo esc_html('Raf-Comp'); ?>
        </div>
        <div class="info-item">
            <span class="dashicons dashicons-clock"></span>
            <strong><?php _e('Data i czas (UTC):', 'claude-chat-pro'); ?></strong>
            <?php echo esc_html('2025-05-22 13:47:27'); ?>
        </div>
    </div>

    <!-- Status API -->
    <div class="diagnostic-section">
        <h2><?php _e('Status połączeń API', 'claude-chat-pro'); ?></h2>
        <div class="api-status-grid">
            <?php foreach ($api_connections as $api): ?>
                <div class="status-card <?php echo $api['status'] ? 'success' : 'error'; ?>">
                    <div class="status-header">
                        <h3><?php echo esc_html($api['name']); ?></h3>
                        <span class="status-icon">
                            <?php echo $api['status'] ? '✅' : '❌'; ?>
                        </span>
                    </div>
                    <div class="status-message">
                        <?php echo esc_html($api['message']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Status bazy danych -->
    <div class="diagnostic-section">
        <h2><?php _e('Status bazy danych', 'claude-chat-pro'); ?></h2>
        
        <?php if (isset($repair_results)): ?>
            <div class="notice notice-info">
                <p><?php _e('Wyniki naprawy tabel:', 'claude-chat-pro'); ?></p>
                <ul>
                    <?php foreach ($repair_results as $table => $result): ?>
                        <li>
                            <?php echo esc_html($table); ?>: 
                            <?php echo $result['status'] ? 
                                '<span class="success">✓</span>' : 
                                '<span class="error">✗</span>'; ?>
                            <?php echo esc_html($result['message']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Tabela', 'claude-chat-pro'); ?></th>
                    <th><?php _e('Status', 'claude-chat-pro'); ?></th>
                    <th><?php _e('Wiersze', 'claude-chat-pro'); ?></th>
                    <th><?php _e('Akcje', 'claude-chat-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($database_tables as $table => $info): ?>
                    <tr>
                        <td><?php echo esc_html($table); ?></td>
                        <td>
                            <span class="status-badge <?php echo $info['status'] ? 'success' : 'error'; ?>">
                                <?php echo esc_html($info['message']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($info['rows']); ?></td>
                        <td class="action-buttons">
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('claude_chat_export_tables'); ?>
                                <input type="hidden" name="export_tables" value="1">
                                <input type="hidden" name="table" value="<?php echo esc_attr($table); ?>">
                                <select name="export_format" class="export-format">
                                    <option value="csv">CSV</option>
                                    <option value="sql">SQL</option>
                                </select>
                                <button type="submit" class="button">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Eksportuj', 'claude-chat-pro'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" class="repair-form">
            <?php wp_nonce_field('claude_chat_repair_tables'); ?>
            <input type="hidden" name="repair_tables" value="1">
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Napraw tabele', 'claude-chat-pro'); ?>
            </button>
        </form>
    </div>

    <!-- Wymagania systemowe -->
    <div class="diagnostic-section">
        <h2><?php _e('Wymagania systemowe', 'claude-chat-pro'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Komponent', 'claude-chat-pro'); ?></th>
                    <th><?php _e('Wymagane', 'claude-chat-pro'); ?></th>
                    <th><?php _e('Aktualne', 'claude-chat-pro'); ?></th>
                    <th><?php _e('Status', 'claude-chat-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($system_checks as $check): ?>
                    <tr>
                        <td><?php echo esc_html($check['name']); ?></td>
                        <td>
                            <?php 
                            if (isset($check['required'])) {
                                echo esc_html($check['required']);
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($check['label']); ?></td>
                        <td>
                            <span class="status-icon <?php echo $check['status'] ? 'success' : 'error'; ?>">
                                <?php echo $check['status'] ? '✅' : '❌'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Uprawnienia plików -->
    <div class="diagnostic-section">
        <h2><?php _e('Uprawnienia plików', 'claude-chat-pro'); ?></h2>
        <div class="file-permissions-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Ścieżka', 'claude-chat-pro'); ?></th>
                        <th><?php _e('Uprawnienia', 'claude-chat-pro'); ?></th>
                        <th><?php _e('Właściciel', 'claude-chat-pro'); ?></th>
                        <th><?php _e('Status', 'claude-chat-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($file_permissions as $file): ?>
                        <tr>
                            <td><?php echo esc_html($file['path']); ?></td>
                            <td><?php echo esc_html($file['permissions']); ?></td>
                            <td><?php echo esc_html($file['owner']); ?></td>
                            <td>
                                <span class="permission-status">
                                    <?php if ($file['readable'] && $file['writable']): ?>
                                        <span class="success">✅</span>
                                        <?php _e('Pełny dostęp', 'claude-chat-pro'); ?>
                                    <?php elseif ($file['readable']): ?>
                                        <span class="warning">⚠️</span>
                                        <?php _e('Tylko odczyt', 'claude-chat-pro'); ?>
                                    <?php else: ?>
                                        <span class="error">❌</span>
                                        <?php _e('Brak dostępu', 'claude-chat-pro'); ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.claude-chat-diagnostics {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.info-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
    display: flex;
    gap: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.diagnostic-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.diagnostic-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.api-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.status-card {
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
}

.status-card.success {
    border-left: 4px solid #46b450;
}

.status-card.error {
    border-left: 4px solid #dc3232;
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.status-header h3 {
    margin: 0;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 0.9em;
}

.status-badge.success {
    background: #edfaef;
    color: #46b450;
}

.status-badge.error {
    background: #fbeaea;
    color: #dc3232;
}

.repair-form {
    margin-top: 15px;
}

.file-permissions-wrapper {
    max-height: 400px;
    overflow-y: auto;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.export-format {
    margin-right: 5px;
}

.permission-status {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Responsywność */
@media screen and (max-width: 782px) {
    .api-status-grid {
        grid-template-columns: 1fr;
    }

    .info-box {
        flex-direction: column;
    }

    .action-buttons {
        flex-direction: column;
    }

    .export-format {
        margin-bottom: 5px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Potwierdzenie naprawy tabel
    $('.repair-form').on('submit', function(e) {
        if (!confirm('<?php _e('Czy na pewno chcesz naprawić tabele bazy danych?', 'claude-chat-pro'); ?>')) {
            e.preventDefault();
        }
    });

    // Odświeżanie statusu co 30 sekund
    setInterval(function() {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'claude_chat_check_status',
                nonce: '<?php echo wp_create_nonce("claude_chat_status"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Aktualizuj status API
                    Object.entries(response.data.api).forEach(([key, status]) => {
                        const card = $(`.status-card:contains("${key}")`);
                        card.removeClass('success error')
                            .addClass(status.status ? 'success' : 'error');
                        card.find('.status-icon')
                            .html(status.status ? '✅' : '❌');
                        card.find('.status-message')
                            .text(status.message);
                    });
                }
            }
        });
    }, 30000);
});
</script>