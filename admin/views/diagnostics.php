<?php
if (!defined('ABSPATH')) exit;

$nonce = wp_create_nonce('claude_diagnostics_nonce');
wp_enqueue_script('claude-diagnostics', CLAUDE_CHAT_PRO_PLUGIN_URL . 'admin/js/diagnostics.js', ['jquery'], CLAUDE_CHAT_PRO_VERSION, true);
wp_localize_script('claude-diagnostics', 'claudeDiagnostics', [
    'nonce' => $nonce,
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'pluginUrl' => CLAUDE_CHAT_PRO_PLUGIN_URL
]);
?>

<div class="wrap claude-diagnostics-wrapper">
    <div class="diagnostics-container">
        <div class="diagnostics-header">
            <div class="header-content">
                <h1 class="page-title">
                    <span class="title-icon dashicons dashicons-admin-tools"></span>
                    <?php _e('Diagnostyka Claude Chat Pro', 'claude-chat-pro'); ?>
                </h1>
                <p class="page-subtitle"><?php _e('Narzędzie do monitorowania i rozwiązywania problemów z wtyczką', 'claude-chat-pro'); ?></p>
            </div>
            <div class="header-actions">
                <button id="refresh-all" class="btn btn-primary">
                    <span class="btn-icon dashicons dashicons-update"></span>
                    <?php _e('Odśwież wszystko', 'claude-chat-pro'); ?>
                </button>
            </div>
        </div>

        <?php if (!empty($recommendations)): ?>
        <div class="diagnostic-panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <span class="panel-icon dashicons dashicons-lightbulb"></span>
                    <?php _e('Zalecane działania', 'claude-chat-pro'); ?>
                </h2>
            </div>
            <div class="panel-content">
                <ul class="recommendations-list">
                    <?php foreach ($recommendations as $recommendation): ?>
                        <li class="recommendation-item">
                            <?php echo esc_html($recommendation); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="quick-stats">
            <div class="stat-card" id="system-stat">
                <div class="stat-icon dashicons dashicons-desktop"></div>
                <div class="stat-content">
                    <div class="stat-value">-</div>
                    <div class="stat-label"><?php _e('System', 'claude-chat-pro'); ?></div>
                </div>
            </div>
            <div class="stat-card" id="api-stat">
                <div class="stat-icon dashicons dashicons-rest-api"></div>
                <div class="stat-content">
                    <div class="stat-value">-</div>
                    <div class="stat-label"><?php _e('API', 'claude-chat-pro'); ?></div>
                </div>
            </div>
            <div class="stat-card" id="database-stat">
                <div class="stat-icon dashicons dashicons-database"></div>
                <div class="stat-content">
                    <div class="stat-value">-</div>
                    <div class="stat-label"><?php _e('Baza danych', 'claude-chat-pro'); ?></div>
                </div>
            </div>
        </div>

        <div class="diagnostics-content">
            <!-- Panel systemowy -->
            <div class="diagnostic-panel" id="system-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <span class="panel-icon dashicons dashicons-desktop"></span>
                        <?php _e('Wymagania systemowe', 'claude-chat-pro'); ?>
                    </h2>
                    <button class="panel-toggle">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="requirements-grid" id="system-requirements">
                        <!-- Wymagania systemowe będą załadowane przez JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Panel API -->
            <div class="diagnostic-panel" id="api-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <span class="panel-icon dashicons dashicons-rest-api"></span>
                        <?php _e('Status API', 'claude-chat-pro'); ?>
                    </h2>
                    <button class="panel-toggle">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="api-status-grid" id="api-connections">
                        <!-- Status API będzie załadowany przez JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Panel bazy danych -->
            <div class="diagnostic-panel" id="database-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <span class="panel-icon dashicons dashicons-database"></span>
                        <?php _e('Status bazy danych', 'claude-chat-pro'); ?>
                    </h2>
                    <button class="panel-toggle">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="database-tables" id="database-tables">
                        <!-- Status bazy danych będzie załadowany przez JavaScript -->
                    </div>
                    <div class="database-actions">
                        <button id="repair-tables" class="btn btn-warning">
                            <span class="btn-icon dashicons dashicons-admin-tools"></span>
                            <?php _e('Napraw tabele', 'claude-chat-pro'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="diagnostics-loading" class="diagnostics-loading">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <div class="loading-text"><?php _e('Ładowanie...', 'claude-chat-pro'); ?></div>
    </div>
</div>