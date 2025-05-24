/**
 * Claude Chat Pro - Diagnostyka JavaScript
 * Nowoczesny, funkcjonalny moduł diagnostyki
 */

jQuery(document).ready(function($) {
    // Cache DOM elements
    const $loading = $('#diagnostics-loading');
    const $systemPanel = $('#system-panel');
    const $apiPanel = $('#api-panel');
    const $databasePanel = $('#database-panel');
    const $quickStats = $('.quick-stats');

    // Show message function
    function showMessage(message, type = 'error') {
        const $message = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible')
            .html('<p>' + message + '</p>')
            .insertAfter('.diagnostics-header');

        // Automatyczne usunięcie po 5 sekundach
        setTimeout(() => {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Initialize panels
    function initPanels() {
        $('.panel-toggle').on('click', function() {
            const $panel = $(this).closest('.diagnostic-panel');
            const $content = $panel.find('.panel-content');
            const $icon = $(this).find('.dashicons');
            
            $content.slideToggle(200);
            $icon.toggleClass('rotated');
        });
    }

    // Load system requirements
    function loadSystemRequirements() {
        $loading.addClass('active');
        $.ajax({
            url: claudeDiagnostics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'claude_get_system_requirements',
                nonce: claudeDiagnostics.nonce
            },
            success: function(response) {
                if (response.success) {
                    const requirements = response.data;
                    let html = '';
                    let okCount = 0;
                    
                    for (const [key, requirement] of Object.entries(requirements)) {
                        if (requirement.status) okCount++;
                        
                        html += `
                            <div class="requirement-item ${requirement.status ? 'status-ok' : 'status-error'}">
                                <div class="requirement-name">${requirement.name}</div>
                                <div class="requirement-value">${requirement.label}</div>
                                <div class="requirement-status">
                                    <span class="status-icon"></span>
                                    ${requirement.status ? 'OK' : 'Błąd'}
                                </div>
                                ${requirement.recommendation ? `
                                    <div class="requirement-recommendation">
                                        ${requirement.recommendation}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }
                    
                    $('#system-requirements').html(html);
                    updateQuickStats('system', {
                        value: `${okCount}/${Object.keys(requirements).length}`,
                        status: okCount === Object.keys(requirements).length
                    });
                } else {
                    showMessage(response.data.message || 'Błąd podczas ładowania wymagań systemowych');
                }
            },
            error: function() {
                showMessage('Błąd połączenia podczas ładowania wymagań systemowych');
            },
            complete: function() {
                $loading.removeClass('active');
            }
        });
    }

    // Load API connections
    function loadApiConnections() {
        $loading.addClass('active');
        $.ajax({
            url: claudeDiagnostics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'claude_get_api_connections',
                nonce: claudeDiagnostics.nonce
            },
            success: function(response) {
                if (response.success) {
                    const connections = response.data;
                    let html = '';
                    let okCount = 0;
                    
                    for (const [key, connection] of Object.entries(connections)) {
                        if (connection.status) okCount++;
                        
                        let logo = '';
                        if (key === 'claude') {
                            logo = '<img src="' + claudeDiagnostics.pluginUrl + 'admin/images/logo-claude.png" alt="Claude AI" class="api-logo">';
                        } else if (key === 'github') {
                            logo = '<img src="' + claudeDiagnostics.pluginUrl + 'admin/images/logo-github.png" alt="GitHub" class="api-logo">';
                        }
                        
                        html += `
                            <div class="api-connection-item ${connection.status ? 'status-ok' : 'status-error'}">
                                <div class="connection-logo">${logo}</div>
                                <div class="connection-name">${connection.name}</div>
                                <div class="connection-status">
                                    <span class="status-icon"></span>
                                    ${connection.status ? 'Połączono' : 'Brak połączenia'}
                                </div>
                                <div class="connection-message ${connection.status ? 'success' : ''}">
                                    ${connection.message}
                                </div>
                            </div>
                        `;
                    }
                    
                    $('#api-connections').html(html);
                    updateQuickStats('api', {
                        value: `${okCount}/${Object.keys(connections).length}`,
                        status: okCount === Object.keys(connections).length
                    });
                } else {
                    showMessage(response.data.message || 'Błąd podczas sprawdzania połączeń API');
                }
            },
            error: function() {
                showMessage('Błąd połączenia podczas sprawdzania API');
            },
            complete: function() {
                $loading.removeClass('active');
            }
        });
    }

    // Load database tables
    function loadDatabaseTables() {
        $loading.addClass('active');
        $.ajax({
            url: claudeDiagnostics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'claude_get_database_tables',
                nonce: claudeDiagnostics.nonce
            },
            success: function(response) {
                if (response.success) {
                    const tables = response.data;
                    let html = '';
                    let okCount = 0;
                    
                    for (const [table, info] of Object.entries(tables)) {
                        if (info.status === 'OK') okCount++;
                        
                        html += `
                            <div class="database-table-item ${info.status === 'OK' ? 'status-ok' : 'status-error'}">
                                <div class="table-name">${table}</div>
                                <div class="table-stats">
                                    <div class="table-records">${info.records} rekordów</div>
                                </div>
                                <div class="table-status">
                                    <span class="status-icon"></span>
                                    ${info.status}
                                </div>
                            </div>
                        `;
                    }
                    
                    $('#database-tables').html(html);
                    updateQuickStats('database', {
                        value: `${okCount}/${Object.keys(tables).length}`,
                        status: okCount === Object.keys(tables).length
                    });
                } else {
                    showMessage(response.data.message || 'Błąd podczas sprawdzania tabel bazy danych');
                }
            },
            error: function() {
                showMessage('Błąd połączenia podczas sprawdzania bazy danych');
            },
            complete: function() {
                $loading.removeClass('active');
            }
        });
    }

    // Uruchomienie testu wydajności
    function runPerformanceTest() {
        $loading.addClass('active');
        $.ajax({
            url: claudeDiagnostics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'claude_run_performance_test',
                nonce: claudeDiagnostics.nonce
            },
            success: function(response) {
                if (response.success) {
                    updatePerformanceResults(response.data);
                }
                hideLoading();
            }
        });
    }

    // Repair database tables
    function repairDatabaseTables() {
        if (!confirm('Czy na pewno chcesz naprawić tabele bazy danych?')) {
            return;
        }

        $loading.addClass('active');
        $.ajax({
            url: claudeDiagnostics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'claude_repair_database_tables',
                nonce: claudeDiagnostics.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    loadDatabaseTables();
                } else {
                    showMessage(response.data.message || 'Błąd podczas naprawy tabel');
                }
            },
            error: function() {
                showMessage('Błąd połączenia podczas naprawy tabel');
            },
            complete: function() {
                $loading.removeClass('active');
            }
        });
    }

    // Export diagnostic report
    function exportDiagnosticReport() {
        $loading.addClass('active');
        $.ajax({
            url: claudeDiagnostics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'claude_export_diagnostic_report',
                nonce: claudeDiagnostics.nonce
            },
            success: function(response) {
                if (response.success) {
                    const blob = new Blob([JSON.stringify(response.data.report, null, 2)], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    showMessage('Raport został wyeksportowany', 'success');
                } else {
                    showMessage(response.data.message || 'Błąd podczas eksportu raportu');
                }
            },
            error: function() {
                showMessage('Błąd połączenia podczas eksportu raportu');
            },
            complete: function() {
                $loading.removeClass('active');
            }
        });
    }

    // Update quick stats
    function updateQuickStats(type, data) {
        const $stat = $(`#${type}-stat`);
        const $value = $stat.find('.stat-value');
        const $icon = $stat.find('.stat-icon');
        
        $value.text(data.value);
        
        if (data.status) {
            $stat.addClass('status-ok').removeClass('status-error');
            $icon.addClass('dashicons-yes-alt').removeClass('dashicons-warning');
        } else {
            $stat.addClass('status-error').removeClass('status-ok');
            $icon.addClass('dashicons-warning').removeClass('dashicons-yes-alt');
        }
    }

    // Odświeżanie wszystkich paneli
    function refreshAll() {
        $loading.addClass('active');
        Promise.all([
            new Promise(resolve => { loadSystemRequirements(); resolve(); }),
            new Promise(resolve => { loadApiConnections(); resolve(); }),
            new Promise(resolve => { loadDatabaseTables(); resolve(); })
        ]).then(() => {
            showMessage('Odświeżenie zakończone sukcesem!', 'success');
        }).catch(() => {
            showMessage('Odświeżenie nie powiodło się!', 'error');
        }).finally(() => {
            $loading.removeClass('active');
        });
    }

    // Initialize
    function init() {
        initPanels();
        refreshAll();
        
        // Event listeners
        $('#refresh-all').on('click', refreshAll);
        $('#repair-tables').on('click', function() {
            if (!confirm('Czy na pewno chcesz naprawić tabele bazy danych?')) {
                return;
            }
            
            $loading.addClass('active');
            $.ajax({
                url: claudeDiagnostics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'claude_repair_database_tables',
                    nonce: claudeDiagnostics.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        loadDatabaseTables();
                    } else {
                        showMessage(response.data.message || 'Błąd podczas naprawy tabel');
                    }
                },
                error: function() {
                    showMessage('Błąd połączenia podczas naprawy tabel');
                },
                complete: function() {
                    $loading.removeClass('active');
                }
            });
        });
    }

    // Start initialization
    init();
});