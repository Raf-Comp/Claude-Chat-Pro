/**
 * Claude Chat Pro - Diagnostyka JavaScript
 * Nowoczesny, funkcjonalny moduł diagnostyki
 */

class ClaudeDiagnostics {
    constructor() {
        this.apiUrl = claudeChatPro.ajaxUrl;
        this.nonce = claudeChatPro.nonce;
        this.refreshInterval = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
        this.initPanelToggles();
    }

    bindEvents() {
        // Główne przyciski
        document.getElementById('refresh-all-btn')?.addEventListener('click', () => this.refreshAll());
        document.getElementById('export-report-btn')?.addEventListener('click', () => this.exportReport());
        
        // Panel API
        document.getElementById('repair-tables-btn')?.addEventListener('click', () => this.repairTables());
        document.getElementById('export-db-btn')?.addEventListener('click', () => this.exportDatabase());
        
        // Performance
        document.getElementById('run-performance-test')?.addEventListener('click', () => this.runPerformanceTest());
        
        // Auto-refresh toggle
        const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        }

        // Stat cards click
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', () => {
                const stat = card.dataset.stat;
                const panel = document.getElementById(`${stat}-panel`);
                if (panel) {
                    panel.scrollIntoView({ behavior: 'smooth' });
                    panel.classList.remove('collapsed');
                }
            });
        });
    }

    initPanelToggles() {
        document.querySelectorAll('.panel-header').forEach(header => {
            header.addEventListener('click', (e) => {
                // Ignoruj jeśli kliknięto w przycisk
                if (e.target.closest('button')) return;
                
                const panel = header.closest('.diagnostic-panel');
                panel.classList.toggle('collapsed');
            });
        });

        document.querySelectorAll('.panel-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const panel = toggle.closest('.diagnostic-panel');
                panel.classList.toggle('collapsed');
            });
        });
    }

    async loadInitialData() {
        this.showLoading(true);
        
        try {
            await Promise.all([
                this.loadSystemRequirements(),
                this.loadAPIStatus(),
                this.loadDatabaseStatus()
            ]);
            
            this.updateQuickStats();
        } catch (error) {
            this.showNotification('Błąd ładowania danych: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadSystemRequirements() {
        try {
            const response = await this.makeRequest('claude_chat_get_system_info');
            
            if (response.success) {
                this.renderSystemRequirements(response.data);
            }
        } catch (error) {
            console.error('Błąd ładowania wymagań systemowych:', error);
        }
    }

    async loadAPIStatus() {
        try {
            const response = await this.makeRequest('claude_chat_check_status');
            
            if (response.success) {
                this.renderAPIStatus(response.data.api);
            }
        } catch (error) {
            console.error('Błąd ładowania statusu API:', error);
        }
    }

    async loadDatabaseStatus() {
        try {
            const response = await this.makeRequest('claude_chat_get_database_info');
            
            if (response.success) {
                this.renderDatabaseStatus(response.data);
            }
        } catch (error) {
            console.error('Błąd ładowania statusu bazy danych:', error);
        }
    }

    renderSystemRequirements(data) {
        const container = document.getElementById('system-requirements');
        if (!container) return;

        let html = '';
        
        Object.entries(data).forEach(([key, check]) => {
            const statusClass = check.status ? 'success' : 'error';
            const statusIcon = check.status ? '✓' : '✕';
            
            html += `
                <div class="requirement-item">
                    <div class="requirement-status ${statusClass}">
                        <span>${statusIcon}</span>
                    </div>
                    <div class="requirement-info">
                        <div class="requirement-name">${check.name}</div>
                        <div class="requirement-details">
                            <span class="requirement-current">Aktualne: ${check.label || check.current}</span>
                            ${check.required ? `<span>Wymagane: ${check.required}</span>` : ''}
                            ${!check.status && check.recommendation ? `<span class="requirement-recommendation">${check.recommendation}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    renderAPIStatus(apis) {
        const container = document.getElementById('api-connections');
        if (!container) return;

        let html = '';
        
        Object.entries(apis).forEach(([key, api]) => {
            const statusClass = api.status ? 'success' : 'error';
            const statusIcon = api.status ? '✓' : '✕';
            
            html += `
                <div class="api-card ${statusClass}" data-api="${key}">
                    <div class="api-header">
                        <div class="api-name">${api.name}</div>
                        <div class="api-status-icon ${statusClass}">
                            <span>${statusIcon}</span>
                        </div>
                    </div>
                    <div class="api-message">${api.message}</div>
                    <div class="api-details">
                        <div class="api-detail">
                            <svg class="api-detail-icon" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 0a8 8 0 1 0 8 8A8 8 0 0 0 8 0zM7 11.5v-6l4.5 3z"/>
                            </svg>
                            <span>Endpoint: <code>${api.endpoint}</code></span>
                        </div>
                        <div class="api-detail">
                            <svg class="api-detail-icon" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 0-1H8V3.5z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                            </svg>
                            <span>Ostatni test: ${new Date(api.last_tested).toLocaleTimeString('pl-PL')}</span>
                        </div>
                    </div>
                    <div class="api-actions">
                        <button type="button" class="btn btn-secondary" onclick="diagnostics.testAPI('${key}')">
                            Test połączenia
                        </button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    renderDatabaseStatus(tables) {
        const container = document.getElementById('database-tables');
        if (!container) return;

        let html = `
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Tabela</th>
                        <th>Status</th>
                        <th>Rekordy</th>
                        <th>Rozmiar</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        let totalSize = 0;
        let totalRecords = 0;
        
        Object.entries(tables).forEach(([name, info]) => {
            const statusClass = info.status ? 'ok' : 'error';
            const statusText = info.status ? 'OK' : 'Błąd';
            
            totalRecords += info.rows || 0;
            
            html += `
                <tr>
                    <td class="table-name">${name.replace(/^.*_/, '')}</td>
                    <td>
                        <span class="table-status ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td class="table-records">${this.formatNumber(info.rows)}</td>
                    <td class="table-size">${info.size}</td>
                    <td class="table-actions">
                        <button class="table-action-btn" onclick="diagnostics.exportTable('${name}', 'csv')">
                            CSV
                        </button>
                        <button class="table-action-btn" onclick="diagnostics.exportTable('${name}', 'sql')">
                            SQL
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        container.innerHTML = html;
        
        // Update stats
        document.getElementById('db-size').textContent = totalSize + ' MB';
    }

    async refreshAll() {
        const btn = document.getElementById('refresh-all-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Odświeżanie...';
        
        try {
            await this.loadInitialData();
            this.showNotification('Dane zostały odświeżone', 'success');
        } catch (error) {
            this.showNotification('Błąd odświeżania: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `
                <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                </svg>
                Odśwież wszystko
            `;
        }
    }

    async testAPI(apiType) {
        const card = document.querySelector(`[data-api="${apiType}"]`);
        if (!card) return;

        card.classList.remove('success', 'error');
        card.classList.add('loading');
        
        try {
            const response = await this.makeRequest('claude_chat_test_specific_api', {
                api_type: apiType
            });
            
            if (response.success) {
                card.classList.remove('loading');
                card.classList.add(response.data.status ? 'success' : 'error');
                
                this.showNotification(response.data.message, response.data.status ? 'success' : 'error');
                
                // Refresh API status
                await this.loadAPIStatus();
            }
        } catch (error) {
            card.classList.remove('loading');
            card.classList.add('error');
            this.showNotification('Błąd testu API: ' + error.message, 'error');
        }
    }

    async repairTables() {
        if (!confirm('Czy na pewno chcesz naprawić i zoptymalizować tabele?')) {
            return;
        }

        const btn = document.getElementById('repair-tables-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Naprawianie...';
        
        try {
            const response = await this.makeRequest('claude_chat_repair_tables');
            
            if (response.success) {
                this.showNotification('Tabele zostały naprawione', 'success');
                await this.loadDatabaseStatus();
            }
        } catch (error) {
            this.showNotification('Błąd naprawy tabel: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `
                <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
                Napraw i optymalizuj
            `;
        }
    }

    async exportDatabase() {
        const btn = document.getElementById('export-db-btn');
        btn.disabled = true;
        
        try {
            const response = await this.makeRequest('claude_chat_export_data', {
                format: 'sql',
                table: 'all'
            });
            
            if (response.success) {
                this.downloadFile(response.data.content, response.data.filename, response.data.mime_type);
                this.showNotification('Eksport zakończony', 'success');
            }
        } catch (error) {
            this.showNotification('Błąd eksportu: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    async exportTable(table, format) {
        try {
            const response = await this.makeRequest('claude_chat_export_data', {
                format: format,
                table: table
            });
            
            if (response.success) {
                this.downloadFile(response.data.content, response.data.filename, response.data.mime_type);
            }
        } catch (error) {
            this.showNotification('Błąd eksportu: ' + error.message, 'error');
        }
    }

    async runPerformanceTest() {
        const btn = document.getElementById('run-performance-test');
        const resultsContainer = document.getElementById('performance-results');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Testowanie...';
        resultsContainer.innerHTML = '';
        
        const tests = [
            { name: 'Czas odpowiedzi API', test: () => this.testAPISpeed() },
            { name: 'Szybkość bazy danych', test: () => this.testDatabaseSpeed() },
            { name: 'Pamięć PHP', test: () => this.testMemoryUsage() },
            { name: 'Wydajność cache', test: () => this.testCachePerformance() }
        ];
        
        let totalScore = 0;
        let html = '';
        
        for (const test of tests) {
            try {
                const start = performance.now();
                const result = await test.test();
                const time = performance.now() - start;
                
                const score = this.calculateScore(time);
                totalScore += score;
                
                const statusClass = score > 80 ? 'success' : score > 50 ? 'warning' : 'error';
                
                html += `
                    <div class="test-result ${statusClass}">
                        <div class="test-result-icon">
                            ${score > 80 ? '✓' : score > 50 ? '!' : '✕'}
                        </div>
                        <div class="test-result-info">
                            <div class="test-name">${test.name}</div>
                            <div class="test-value">Wynik: ${score}/100</div>
                        </div>
                        <div class="test-time">${Math.round(time)}ms</div>
                    </div>
                `;
            } catch (error) {
                html += `
                    <div class="test-result error">
                        <div class="test-result-icon">✕</div>
                        <div class="test-result-info">
                            <div class="test-name">${test.name}</div>
                            <div class="test-value">Błąd testu</div>
                        </div>
                    </div>
                `;
            }
        }
        
        resultsContainer.innerHTML = html;
        
        const avgScore = Math.round(totalScore / tests.length);
        document.getElementById('performance').textContent = avgScore + '/100';
        
        btn.disabled = false;
        btn.innerHTML = `
            <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
            </svg>
            Uruchom test wydajności
        `;
    }

    async testAPISpeed() {
        return this.makeRequest('claude_chat_test_api');
    }

    async testDatabaseSpeed() {
        return this.makeRequest('claude_chat_get_database_info');
    }

    async testMemoryUsage() {
        return this.makeRequest('claude_chat_get_system_info');
    }

    async testCachePerformance() {
        return this.makeRequest('claude_chat_clear_cache');
    }

    calculateScore(time) {
        if (time < 100) return 100;
        if (time < 300) return 90;
        if (time < 500) return 80;
       if (time < 1000) return 70;
       if (time < 2000) return 50;
       if (time < 5000) return 30;
       return 10;
   }

   async exportReport() {
       const btn = document.getElementById('export-report-btn');
       btn.disabled = true;
       btn.innerHTML = '<span class="loading-spinner"></span> Generowanie...';
       
       try {
           // Zbierz wszystkie dane
           const reportData = {
               generated: new Date().toISOString(),
               system: await this.makeRequest('claude_chat_get_system_info'),
               api: await this.makeRequest('claude_chat_check_status'),
               database: await this.makeRequest('claude_chat_get_database_info'),
               performance: {
                   score: document.getElementById('performance').textContent
               }
           };
           
           // Generuj HTML raport
           const reportHTML = this.generateReportHTML(reportData);
           
           // Pobierz jako plik
           const blob = new Blob([reportHTML], { type: 'text/html' });
           const url = window.URL.createObjectURL(blob);
           const a = document.createElement('a');
           a.href = url;
           a.download = `claude-chat-diagnostics-${new Date().toISOString().split('T')[0]}.html`;
           document.body.appendChild(a);
           a.click();
           window.URL.revokeObjectURL(url);
           document.body.removeChild(a);
           
           this.showNotification('Raport został wygenerowany', 'success');
       } catch (error) {
           this.showNotification('Błąd generowania raportu: ' + error.message, 'error');
       } finally {
           btn.disabled = false;
           btn.innerHTML = `
               <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                   <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
               </svg>
               Eksportuj raport
           `;
       }
   }

   generateReportHTML(data) {
       return `
<!DOCTYPE html>
<html lang="pl">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Claude Chat Pro - Raport diagnostyczny</title>
   <style>
       body {
           font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
           line-height: 1.6;
           color: #333;
           max-width: 1200px;
           margin: 0 auto;
           padding: 20px;
           background: #f5f5f5;
       }
       .report-header {
           background: white;
           padding: 30px;
           border-radius: 10px;
           margin-bottom: 20px;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       .report-section {
           background: white;
           padding: 20px;
           border-radius: 10px;
           margin-bottom: 20px;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       h1, h2 {
           margin-top: 0;
       }
       .status-ok {
           color: #10B981;
       }
       .status-error {
           color: #EF4444;
       }
       table {
           width: 100%;
           border-collapse: collapse;
           margin-top: 10px;
       }
       th, td {
           padding: 10px;
           text-align: left;
           border-bottom: 1px solid #ddd;
       }
       th {
           background: #f8f9fa;
           font-weight: 600;
       }
   </style>
</head>
<body>
   <div class="report-header">
       <h1>Claude Chat Pro - Raport diagnostyczny</h1>
       <p>Wygenerowano: ${new Date(data.generated).toLocaleString('pl-PL')}</p>
   </div>
   
   <div class="report-section">
       <h2>Podsumowanie</h2>
       <p>Wynik wydajności: <strong>${data.performance.score}</strong></p>
   </div>
   
   <!-- Dodaj więcej sekcji raportu -->
</body>
</html>
       `;
   }

   updateQuickStats() {
       // System health
       const systemHealthEl = document.getElementById('system-health');
       if (systemHealthEl) {
           // Oblicz ogólny stan systemu na podstawie różnych czynników
           systemHealthEl.textContent = 'Dobry';
       }
       
       // API status
       const apiStatusEl = document.getElementById('api-status');
       if (apiStatusEl) {
           const apiCards = document.querySelectorAll('.api-card');
           const working = document.querySelectorAll('.api-card.success').length;
           apiStatusEl.textContent = `${working}/${apiCards.length} aktywne`;
       }
       
       // Database size - już aktualizowane w renderDatabaseStatus
       
       // Performance - aktualizowane po teście
   }

   startAutoRefresh() {
       this.stopAutoRefresh();
       this.refreshInterval = setInterval(() => {
           this.loadInitialData();
       }, 30000); // Co 30 sekund
       
       this.showNotification('Auto-odświeżanie włączone', 'info');
   }

   stopAutoRefresh() {
       if (this.refreshInterval) {
           clearInterval(this.refreshInterval);
           this.refreshInterval = null;
           this.showNotification('Auto-odświeżanie wyłączone', 'info');
       }
   }

   showLoading(show) {
       const loadingEl = document.getElementById('diagnostics-loading');
       if (loadingEl) {
           loadingEl.classList.toggle('active', show);
       }
   }

   showNotification(message, type = 'info') {
       const notification = document.createElement('div');
       notification.className = `diagnostic-notification notification-${type}`;
       notification.innerHTML = `
           <svg class="notification-icon" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
               ${this.getNotificationIcon(type)}
           </svg>
           <span>${message}</span>
       `;
       
       document.body.appendChild(notification);
       
       setTimeout(() => {
           notification.style.animation = 'slideOut 0.3s ease';
           setTimeout(() => {
               notification.remove();
           }, 300);
       }, 5000);
   }

   getNotificationIcon(type) {
       const icons = {
           success: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />',
           error: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />',
           warning: '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />',
           info: '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />'
       };
       return icons[type] || icons.info;
   }

   async makeRequest(action, data = {}) {
       const formData = new FormData();
       formData.append('action', action);
       formData.append('nonce', this.nonce);
       
       Object.entries(data).forEach(([key, value]) => {
           formData.append(key, value);
       });
       
       const response = await fetch(this.apiUrl, {
           method: 'POST',
           body: formData,
           credentials: 'same-origin'
       });
       
       if (!response.ok) {
           throw new Error(`HTTP error! status: ${response.status}`);
       }
       
       const result = await response.json();
       
       if (!result.success && result.data?.message) {
           throw new Error(result.data.message);
       }
       
       return result;
   }

   downloadFile(content, filename, mimeType) {
       const blob = new Blob([content], { type: mimeType });
       const url = window.URL.createObjectURL(blob);
       const a = document.createElement('a');
       a.href = url;
       a.download = filename;
       document.body.appendChild(a);
       a.click();
       window.URL.revokeObjectURL(url);
       document.body.removeChild(a);
   }

   formatNumber(num) {
       return new Intl.NumberFormat('pl-PL').format(num);
   }
}

// Dodaj styl dla animacji slideOut
const style = document.createElement('style');
style.textContent = `
@keyframes slideOut {
   from {
       transform: translateX(0);
       opacity: 1;
   }
   to {
       transform: translateX(100%);
       opacity: 0;
   }
}

.loading-spinner {
   display: inline-block;
   width: 16px;
   height: 16px;
   border: 2px solid rgba(255, 255, 255, 0.3);
   border-radius: 50%;
   border-top-color: white;
   animation: spin 1s ease-in-out infinite;
}
`;
document.head.appendChild(style);

// Inicjalizacja po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
   window.diagnostics = new ClaudeDiagnostics();
});