<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

$diagnostics = new \ClaudeChatPro\Includes\Diagnostics();

// Obsługa eksportu i naprawy (bez zmian)
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

if (isset($_POST['repair_tables']) && check_admin_referer('claude_chat_repair_tables')) {
    $repair_results = $diagnostics->repair_database_tables();
}

// Pobierz dane
$system_checks = $diagnostics->check_system_requirements();
$api_connections = $diagnostics->check_api_connections();
$database_tables = $diagnostics->check_database_tables();
$file_permissions = $diagnostics->check_file_permissions();

// Funkcje pomocnicze
function format_bytes_helper($size) {
    if (!is_numeric($size)) return $size;
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}

function get_system_recommendation($check_key) {
    $recommendations = [
        'php_version' => __('Zaktualizuj PHP do najnowszej wersji', 'claude-chat-pro'),
        'wp_version' => __('Zaktualizuj WordPress do najnowszej wersji', 'claude-chat-pro'),
        'curl' => __('Zainstaluj rozszerzenie cURL dla PHP', 'claude-chat-pro'),
        'ssl' => __('Zainstaluj rozszerzenie OpenSSL dla PHP', 'claude-chat-pro'),
        'memory_limit' => __('Zwiększ limit pamięci PHP do minimum 128MB', 'claude-chat-pro'),
        'max_execution_time' => __('Zwiększ maksymalny czas wykonania do minimum 60s', 'claude-chat-pro')
    ];
    
    return $recommendations[$check_key] ?? __('Skontaktuj się z administratorem serwera', 'claude-chat-pro');
}
?>

<div class="claude-chat-diagnostics">
    <div class="diagnostics-container">
        <!-- Ultra nowoczesny header -->
        <div class="diagnostics-header fade-in">
            <h1 class="diagnostics-title">
                Diagnostyka Claude Chat Pro
            </h1>
            <p class="diagnostics-subtitle">
            <?php _e('Zaawansowana diagnostyka systemu z monitorowaniem w czasie rzeczywistym', 'claude-chat-pro'); ?>
           </p>
       </div>

       <!-- System info grid -->
       <div class="system-info-grid">
           <div class="info-card fade-in fade-in-delay-1">
               <div class="info-item">
                   <div class="info-icon">
                       <i class="dashicons dashicons-admin-users"></i>
                   </div>
                   <div class="info-content">
                       <h3><?php _e('Aktywny użytkownik', 'claude-chat-pro'); ?></h3>
                       <p><?php echo esc_html(wp_get_current_user()->display_name); ?></p>
                   </div>
               </div>
           </div>
           
           <div class="info-card fade-in fade-in-delay-2">
               <div class="info-item">
                   <div class="info-icon">
                       <i class="dashicons dashicons-clock"></i>
                   </div>
                   <div class="info-content">
                       <h3><?php _e('Czas systemowy', 'claude-chat-pro'); ?></h3>
                       <p id="current-time"><?php echo current_time('H:i:s', true); ?></p>
                   </div>
               </div>
           </div>
           
           <div class="info-card fade-in fade-in-delay-3">
               <div class="info-item">
                   <div class="info-icon">
                       <i class="dashicons dashicons-admin-plugins"></i>
                   </div>
                   <div class="info-content">
                       <h3><?php _e('Wersja wtyczki', 'claude-chat-pro'); ?></h3>
                       <p>v<?php echo CLAUDE_CHAT_PRO_VERSION; ?></p>
                   </div>
               </div>
           </div>
           
           <div class="info-card fade-in fade-in-delay-4">
               <div class="info-item">
                   <div class="info-icon">
                       <i class="dashicons dashicons-wordpress"></i>
                   </div>
                   <div class="info-content">
                       <h3><?php _e('WordPress Core', 'claude-chat-pro'); ?></h3>
                       <p>v<?php echo get_bloginfo('version'); ?></p>
                   </div>
               </div>
           </div>
       </div>

       <!-- Status API -->
       <div class="diagnostic-section fade-in">
           <div class="section-header">
               <h2 class="section-title">
                   <div class="section-icon">
                       <i class="dashicons dashicons-cloud"></i>
                   </div>
                   <?php _e('Monitorowanie API', 'claude-chat-pro'); ?>
               </h2>
               <div class="section-actions">
                   <label class="modern-btn modern-btn-secondary">
                       <input type="checkbox" id="auto-refresh-toggle" checked style="margin-right: 0.5rem;">
                       <?php _e('Auto-refresh', 'claude-chat-pro'); ?>
                   </label>
                   <button type="button" id="refresh-status-btn" class="modern-btn modern-btn-primary">
                       <i class="dashicons dashicons-update"></i>
                       <?php _e('Odśwież teraz', 'claude-chat-pro'); ?>
                   </button>
               </div>
           </div>
           
           <div class="section-content">
               <div class="api-status-grid">
                   <?php foreach ($api_connections as $api_key => $api): ?>
                       <div class="status-card <?php echo $api['status'] ? 'success' : 'error'; ?>" data-api="<?php echo esc_attr($api_key); ?>">
                           <div class="status-header">
                               <h3 class="status-title">
                                   <?php echo esc_html($api['name']); ?>
                               </h3>
                               <div class="status-indicator <?php echo $api['status'] ? 'success' : 'error'; ?>">
                                   <?php echo $api['status'] ? '✨' : '⚠️'; ?>
                               </div>
                           </div>
                           
                           <div class="status-message">
                               <?php echo esc_html($api['message']); ?>
                           </div>
                           
                           <div class="status-details">
                               <div><strong>🌐 Endpoint:</strong> <code><?php echo esc_html($api['endpoint'] ?? 'N/A'); ?></code></div>
                               <div><strong>⏰ Ostatni test:</strong> <?php echo esc_html(date('H:i:s', strtotime($api['last_tested'] ?? 'now'))); ?></div>
                               <div><strong>⚙️ Konfiguracja:</strong> <?php echo $api['configured'] ? '<span class="status-badge success">✅ OK</span>' : '<span class="status-badge error">❌ Brak</span>'; ?></div>
                           </div>
                           
                           <div class="status-actions" style="margin-top: 1.5rem;">
                               <button type="button" class="modern-btn modern-btn-secondary test-api-btn" data-api="<?php echo esc_attr($api_key); ?>">
                                   <i class="dashicons dashicons-admin-tools"></i>
                                   <?php _e('Test połączenia', 'claude-chat-pro'); ?>
                               </button>
                           </div>
                       </div>
                   <?php endforeach; ?>
               </div>
           </div>
       </div>

       <!-- Baza danych -->
       <div class="diagnostic-section fade-in">
           <div class="section-header">
               <h2 class="section-title">
                   <div class="section-icon">
                       <i class="dashicons dashicons-database"></i>
                   </div>
                   <?php _e('Zarządzanie bazą danych', 'claude-chat-pro'); ?>
               </h2>
               <div class="section-actions">
                   <button type="button" id="repair-tables-btn" class="modern-btn modern-btn-warning">
                       <i class="dashicons dashicons-admin-tools"></i>
                       <?php _e('Napraw i optymalizuj', 'claude-chat-pro'); ?>
                   </button>
               </div>
           </div>
           
           <div class="section-content">
               <?php if (isset($repair_results)): ?>
                   <div class="repair-results" style="margin-bottom: 2rem;">
                       <h4 style="color: var(--text-primary); margin-bottom: 1rem;">
                           <i class="dashicons dashicons-yes-alt"></i>
                           <?php _e('Wyniki operacji naprawy:', 'claude-chat-pro'); ?>
                       </h4>
                       <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                           <?php foreach ($repair_results as $table => $result): ?>
                               <div class="status-badge <?php echo $result['status'] ? 'success' : 'error'; ?>">
                                   <?php echo esc_html(basename($table)); ?>: <?php echo esc_html($result['message']); ?>
                               </div>
                           <?php endforeach; ?>
                       </div>
                   </div>
               <?php endif; ?>

               <div style="overflow-x: auto;">
                   <table class="modern-table">
                       <thead>
                           <tr>
                               <th><i class="dashicons dashicons-database"></i> <?php _e('Tabela', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-info"></i> <?php _e('Status', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-chart-line"></i> <?php _e('Rekordy', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-chart-pie"></i> <?php _e('Rozmiar', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-download"></i> <?php _e('Eksport', 'claude-chat-pro'); ?></th>
                           </tr>
                       </thead>
                       <tbody>
                           <?php foreach ($database_tables as $table => $info): ?>
                               <tr data-table-name="<?php echo esc_attr($table); ?>">
                                   <td>
                                       <strong><?php echo esc_html(basename($table)); ?></strong>
                                       <br><small style="color: var(--text-muted);"><?php echo esc_html($table); ?></small>
                                   </td>
                                   <td class="table-status">
                                       <?php if ($info['status']): ?>
                                           <span class="status-badge success">
                                               <i class="dashicons dashicons-yes-alt"></i>
                                               <?php _e('Zdrowa', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php else: ?>
                                           <span class="status-badge error">
                                               <i class="dashicons dashicons-warning"></i>
                                               <?php _e('Wymaga uwagi', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php endif; ?>
                                   </td>
                                   <td class="table-rows">
                                       <strong style="font-size: 1.1em;" data-counter="<?php echo esc_attr($info['rows']); ?>">
                                           <?php echo number_format($info['rows'], 0, ',', ' '); ?>
                                       </strong>
                                   </td>
                                   <td>
                                       <span class="tooltip" data-tooltip="<?php _e('Przybliżony rozmiar na dysku', 'claude-chat-pro'); ?>">
                                           <strong><?php echo esc_html($info['size'] ?? '0 MB'); ?></strong>
                                       </span>
                                   </td>
                                   <td>
                                       <div class="export-controls">
                                           <select class="export-format-select">
                                               <option value="csv">📊 CSV</option>
                                               <option value="sql">🗄️ SQL</option>
                                               <option value="json">📋 JSON</option>
                                           </select>
                                           <button type="button" class="modern-btn modern-btn-secondary export-btn" 
                                                   data-table="<?php echo esc_attr($table); ?>" 
                                                   data-format="csv">
                                               <i class="dashicons dashicons-download"></i>
                                               <?php _e('Pobierz', 'claude-chat-pro'); ?>
                                           </button>
                                       </div>
                                   </td>
                               </tr>
                           <?php endforeach; ?>
                       </tbody>
                   </table>
               </div>
           </div>
       </div>

       <!-- Środowisko systemowe -->
       <div class="diagnostic-section fade-in">
           <div class="section-header">
               <h2 class="section-title">
                   <div class="section-icon">
                       <i class="dashicons dashicons-admin-settings"></i>
                   </div>
                   <?php _e('Środowisko systemowe', 'claude-chat-pro'); ?>
               </h2>
               <div class="section-actions">
                   <button type="button" id="clear-cache-btn" class="modern-btn modern-btn-secondary">
                       <i class="dashicons dashicons-trash"></i>
                       <?php _e('Wyczyść cache', 'claude-chat-pro'); ?>
                   </button>
               </div>
           </div>
           
           <div class="section-content">
               <div style="overflow-x: auto;">
                   <table class="modern-table">
                       <thead>
                           <tr>
                               <th><i class="dashicons dashicons-admin-generic"></i> <?php _e('Komponent', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-info"></i> <?php _e('Wymagane', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-chart-bar"></i> <?php _e('Aktualne', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-yes-alt"></i> <?php _e('Status', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-lightbulb"></i> <?php _e('Rekomendacje', 'claude-chat-pro'); ?></th>
                           </tr>
                       </thead>
                       <tbody>
                           <?php foreach ($system_checks as $check_key => $check): ?>
                               <tr>
                                   <td>
                                       <strong><?php echo esc_html($check['name']); ?></strong>
                                       <?php if (isset($check['recommendation'])): ?>
                                           <br><small style="color: var(--text-muted);"><?php echo esc_html($check['recommendation']); ?></small>
                                       <?php endif; ?>
                                   </td>
                                   <td>
                                       <?php if (isset($check['required'])): ?>
                                           <code><?php echo esc_html($check['required']); ?></code>
                                       <?php else: ?>
                                           <span style="color: var(--text-muted);">—</span>
                                       <?php endif; ?>
                                   </td>
                                   <td data-system-info="<?php echo esc_attr($check_key); ?>">
                                       <strong><?php echo esc_html($check['label']); ?></strong>
                                   </td>
                                   <td>
                                       <?php if ($check['status']): ?>
                                           <span class="status-badge success">
                                               <i class="dashicons dashicons-yes-alt"></i>
                                               <?php _e('Optimal', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php else: ?>
                                           <span class="status-badge error">
                                               <i class="dashicons dashicons-warning"></i>
                                               <?php _e('Uwaga', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php endif; ?>
                                   </td>
                                   <td>
                                       <?php if (!$check['status']): ?>
                                           <div style="color: var(--warning-color); font-weight: 500;">
                                               <i class="dashicons dashicons-info"></i>
                                               <?php echo esc_html(get_system_recommendation($check_key)); ?>
                                           </div>
                                       <?php else: ?>
                                           <span style="color: var(--success-color); font-weight: 500;">
                                               <i class="dashicons dashicons-yes-alt"></i>
                                               <?php _e('Wszystko w porządku', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php endif; ?>
                                   </td>
                               </tr>
                           <?php endforeach; ?>
                       </tbody>
                   </table>
               </div>
           </div>
       </div>

       <!-- Bezpieczeństwo plików -->
       <div class="diagnostic-section fade-in">
           <div class="section-header">
               <h2 class="section-title">
                   <div class="section-icon">
                       <i class="dashicons dashicons-lock"></i>
                   </div>
                   <?php _e('Bezpieczeństwo i uprawnienia', 'claude-chat-pro'); ?>
               </h2>
           </div>
           
           <div class="section-content">
               <div style="max-height: 500px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
                   <table class="modern-table" style="margin: 0;">
                       <thead>
                           <tr>
                               <th><i class="dashicons dashicons-portfolio"></i> <?php _e('Ścieżka', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-category"></i> <?php _e('Typ', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-admin-network"></i> <?php _e('Uprawnienia', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-admin-users"></i> <?php _e('Właściciel', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-chart-pie"></i> <?php _e('Rozmiar', 'claude-chat-pro'); ?></th>
                               <th><i class="dashicons dashicons-shield-alt"></i> <?php _e('Bezpieczeństwo', 'claude-chat-pro'); ?></th>
                           </tr>
                       </thead>
                       <tbody>
                           <?php foreach (array_slice($file_permissions, 0, 15) as $file): ?>
                               <tr>
                                   <td>
                                       <code style="background: var(--bg-secondary); padding: 0.25rem 0.5rem; border-radius: 4px;">
                                           <?php echo esc_html($file['path']); ?>
                                       </code>
                                   </td>
                                   <td>
                                       <div style="display: flex; align-items: center; gap: 0.5rem;">
                                           <?php if ($file['type'] === 'directory'): ?>
                                               <span style="font-size: 1.2em;">📁</span>
                                               <span style="color: var(--info-color);"><?php _e('Folder', 'claude-chat-pro'); ?></span>
                                           <?php else: ?>
                                               <span style="font-size: 1.2em;">📄</span>
                                               <span style="color: var(--success-color);"><?php _e('Plik', 'claude-chat-pro'); ?></span>
                                           <?php endif; ?>
                                       </div>
                                   </td>
                                   <td>
                                       <code style="font-weight: 600; color: var(--primary-solid);">
                                           <?php echo esc_html($file['permissions']); ?>
                                       </code>
                                   </td>
                                   <td>
                                       <span style="font-weight: 500;">
                                           <?php echo esc_html($file['owner']); ?>
                                       </span>
                                   </td>
                                   <td>
                                       <?php if ($file['type'] === 'file' && $file['size'] > 0): ?>
                                           <strong><?php echo format_bytes_helper($file['size']); ?></strong>
                                       <?php else: ?>
                                           <span style="color: var(--text-muted);">—</span>
                                       <?php endif; ?>
                                   </td>
                                   <td>
                                       <?php if ($file['readable'] && $file['writable']): ?>
                                           <span class="status-badge success">
                                               <i class="dashicons dashicons-shield-alt"></i>
                                               <?php _e('Pełny dostęp', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php elseif ($file['readable']): ?>
                                           <span class="status-badge warning">
                                               <i class="dashicons dashicons-warning"></i>
                                               <?php _e('Tylko odczyt', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php else: ?>
                                           <span class="status-badge error">
                                               <i class="dashicons dashicons-dismiss"></i>
                                               <?php _e('Brak dostępu', 'claude-chat-pro'); ?>
                                           </span>
                                       <?php endif; ?>
                                   </td>
                               </tr>
                           <?php endforeach; ?>
                       </tbody>
                   </table>
               </div>
               
               <?php if (count($file_permissions) > 15): ?>
                   <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-secondary); border-radius: 12px; text-align: center;">
                       <p style="margin: 0; color: var(--text-muted);">
                           <i class="dashicons dashicons-info"></i>
                           <?php printf(__('Pokazano 15 z %d plików. Pełna analiza dostępna w eksporcie danych.', 'claude-chat-pro'), count($file_permissions)); ?>
                       </p>
                   </div>
               <?php endif; ?>
           </div>
       </div>

       <!-- Health Check -->
       <div class="diagnostic-section fade-in">
           <div class="section-header">
               <h2 class="section-title">
                   <div class="section-icon">
                       <i class="dashicons dashicons-heart"></i>
                   </div>
                   <?php _e('Kompleksowy Health Check', 'claude-chat-pro'); ?>
               </h2>
               <div class="section-actions">
                   <button type="button" id="run-health-check-btn" class="modern-btn modern-btn-primary">
                       <i class="dashicons dashicons-update"></i>
                       <?php _e('Uruchom pełną diagnostykę', 'claude-chat-pro'); ?>
                   </button>
               </div>
           </div>
           
           <div class="section-content">
               <div id="health-check-results">
                   <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                       <i class="dashicons dashicons-heart" style="font-size: 3rem; margin-bottom: 1rem; color: var(--primary-solid);"></i>
                       <h3 style="margin: 0 0 1rem 0; color: var(--text-primary);">
                           <?php _e('Gotowy do pełnej diagnostyki', 'claude-chat-pro'); ?>
                       </h3>
                       <p style="margin: 0;">
                           <?php _e('Kliknij przycisk powyżej aby uruchomić kompletną analizę stanu systemu', 'claude-chat-pro'); ?>
                       </p>
                   </div>
               </div>
           </div>
       </div>
   </div>
</div>

<script>
// Przekaż dane do JavaScript
window.diagnosticsData = {
   currentTime: '<?php echo current_time('Y-m-d H:i:s', true); ?>',
   pluginVersion: '<?php echo CLAUDE_CHAT_PRO_VERSION; ?>',
   wpVersion: '<?php echo get_bloginfo('version'); ?>',
   strings: {
       refreshSuccess: '<?php echo esc_js(__('Status odświeżony pomyślnie', 'claude-chat-pro')); ?>',
       refreshError: '<?php echo esc_js(__('Błąd odświeżania statusu', 'claude-chat-pro')); ?>',
       repairSuccess: '<?php echo esc_js(__('Tabele naprawione pomyślnie', 'claude-chat-pro')); ?>',
       repairError: '<?php echo esc_js(__('Błąd naprawy tabel', 'claude-chat-pro')); ?>',
       exportSuccess: '<?php echo esc_js(__('Dane wyeksportowane pomyślnie', 'claude-chat-pro')); ?>',
       exportError: '<?php echo esc_js(__('Błąd eksportu danych', 'claude-chat-pro')); ?>',
       confirmRepair: '<?php echo esc_js(__('Czy na pewno chcesz naprawić i zoptymalizować tabele?', 'claude-chat-pro')); ?>',
       confirmClearCache: '<?php echo esc_js(__('Czy na pewno chcesz wyczyścić cache systemowy?', 'claude-chat-pro')); ?>',
       healthCheckRunning: '<?php echo esc_js(__('Analizuję system...', 'claude-chat-pro')); ?>',
       healthCheckComplete: '<?php echo esc_js(__('Analiza zakończona', 'claude-chat-pro')); ?>'
   }
};

// Aktualizacja czasu w czasie rzeczywistym
setInterval(() => {
   const timeElement = document.getElementById('current-time');
   if (timeElement) {
       const now = new Date();
       timeElement.textContent = now.toTimeString().slice(0, 8);
   }
}, 1000);

// Enhanced Health Check
document.addEventListener('DOMContentLoaded', function() {
   const healthCheckBtn = document.getElementById('run-health-check-btn');
   const healthCheckResults = document.getElementById('health-check-results');
   
   if (healthCheckBtn) {
       healthCheckBtn.addEventListener('click', async function() {
           const originalHTML = this.innerHTML;
           this.disabled = true;
           this.innerHTML = '<span class="loading-spinner"></span> ' + window.diagnosticsData.strings.healthCheckRunning;
           
           try {
               const response = await fetch(claudeChatPro.ajaxUrl, {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded',
                   },
                   body: new URLSearchParams({
                       action: 'claude_chat_health_check',
                       nonce: claudeChatPro.nonce
                   })
               });
               
               const data = await response.json();
               
               if (data.success) {
                   const healthData = data.data;
                   let html = '<div class="health-check-summary">';
                   
                   if (healthData.status === 'healthy') {
                       html += `
                           <div style="text-align: center; padding: 2rem;">
                               <div style="font-size: 4rem; margin-bottom: 1rem;">✨</div>
                               <div class="status-badge success" style="font-size: 1.1rem; padding: 1rem 2rem;">
                                   <i class="dashicons dashicons-yes-alt"></i>
                                   System jest w pełni sprawny!
                               </div>
                               <p style="margin-top: 1rem; color: var(--text-secondary);">
                                   Wszystkie komponenty działają optymalnie
                               </p>
                           </div>
                       `;
                   } else {
                       html += `
                           <div style="text-align: center; padding: 2rem;">
                               <div style="font-size: 4rem; margin-bottom: 1rem;">⚠️</div>
                               <div class="status-badge warning" style="font-size: 1.1rem; padding: 1rem 2rem;">
                                   <i class="dashicons dashicons-warning"></i>
                                   Wykryto ${healthData.summary.total_issues} problemów
                               </div>
                           </div>
                       `;
                       
                       html += '<div class="health-issues" style="margin-top: 2rem;">';
                       html += '<h4 style="color: var(--text-primary); margin-bottom: 1rem;"><i class="dashicons dashicons-info"></i> Szczegóły problemów:</h4>';
                       
                       healthData.issues.forEach((issue, index) => {
                           const severity = issue.severity === 'critical' ? 'error' : 'warning';
                           const icon = issue.severity === 'critical' ? 'dashicons-dismiss' : 'dashicons-warning';
                           html += `
                               <div class="status-badge ${severity}" style="margin: 0.75rem 0; width: 100%; justify-content: flex-start;">
                                   <i class="dashicons ${icon}"></i>
                                   <strong>${issue.type}:</strong> ${issue.message}
                               </div>
                           `;
                       });
                       
                       html += '</div>';
                   }
                   
                   html += `
                       <div class="health-summary" style="margin-top: 2rem;">
                           <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                               <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 12px;">
                                   <div style="font-size: 2rem; font-weight: 700; color: var(--text-primary);">${healthData.summary.total_issues}</div>
                                   <div style="color: var(--text-muted); font-size: 0.9rem;">Łącznie problemów</div>
                               </div>
                               <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 12px;">
                                   <div style="font-size: 2rem; font-weight: 700; color: var(--error-color);">${healthData.summary.critical_issues}</div>
                                   <div style="color: var(--text-muted); font-size: 0.9rem;">Krytyczne</div>
                               </div>
                               <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 12px;">
                                   <div style="font-size: 2rem; font-weight: 700; color: var(--warning-color);">${healthData.summary.warning_issues}</div>
                                   <div style="color: var(--text-muted); font-size: 0.9rem;">Ostrzeżenia</div>
                               </div>
                           </div>
                       </div>
                   `;
                   
                   html += '</div>';
                   healthCheckResults.innerHTML = html;
               } else {
                   healthCheckResults.innerHTML = `
                       <div style="text-align: center; padding: 2rem;">
                           <div style="font-size: 4rem; margin-bottom: 1rem;">❌</div>
                           <div class="status-badge error">Błąd podczas wykonywania diagnostyki</div>
                       </div>
                   `;
               }
           } catch (error) {
               healthCheckResults.innerHTML = `
                   <div style="text-align: center; padding: 2rem;">
                       <div style="font-size: 4rem; margin-bottom: 1rem;">⚡</div>
                       <div class="status-badge error">Błąd komunikacji z serwerem</div>
                   </div>
               `;
           } finally {
               this.disabled = false;
               this.innerHTML = originalHTML;
           }
       });
   }
});
</script>