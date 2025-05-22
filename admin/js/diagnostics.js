/**
 * Claude Chat Pro - Diagnostyka JavaScript
 * Nowoczesny moduł diagnostyki z animacjami i interaktywnością
 */

class DiagnosticsManager {
    constructor() {
        this.isInitialized = false;
        this.refreshInterval = null;
        this.statusChecking = false;
        this.init();
    }

    /**
     * Inicjalizacja modułu diagnostyki
     */
    async init() {
        if (this.isInitialized) return;

        try {
            this.bindEvents();
            this.initAnimations();
            await this.loadInitialData();
            this.startAutoRefresh();
            this.isInitialized = true;
            
            this.showNotification('Diagnostyka załadowana pomyślnie', 'success');
        } catch (error) {
            console.error('Błąd inicjalizacji diagnostyki:', error);
            this.showNotification('Błąd inicjalizacji diagnostyki', 'error');
        }
    }

    /**
     * Bindowanie eventów
     */
    bindEvents() {
        // Przycisk odświeżania statusu
        document.addEventListener('click', (e) => {
            if (e.target.matches('#refresh-status-btn')) {
                e.preventDefault();
                this.refreshStatus();
            }

            // Naprawa tabel
            if (e.target.matches('#repair-tables-btn')) {
                e.preventDefault();
                this.repairTables();
            }

            // Export danych
            if (e.target.matches('.export-btn')) {
                e.preventDefault();
                this.exportData(e.target);
            }

            // Czyszczenie cache
            if (e.target.matches('#clear-cache-btn')) {
                e.preventDefault();
                this.clearCache();
            }

            // Test konkretnego API
            if (e.target.matches('.test-api-btn')) {
                e.preventDefault();
                this.testSpecificAPI(e.target.dataset.api);
            }
        });

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

        // Export format change
        document.addEventListener('change', (e) => {
            if (e.target.matches('.export-format-select')) {
                this.updateExportOptions(e.target);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'r':
                        e.preventDefault();
                        this.refreshStatus();
                        break;
                    case 'e':
                        e.preventDefault();
                        this.focusExportSection();
                        break;
                }
            }
        });
    }

    /**
     * Inicjalizacja animacji
     */
    initAnimations() {
        // Animacja pojawienia się kart
        const cards = document.querySelectorAll('.status-card, .info-card, .diagnostic-section');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Animacja liczników
        this.animateCounters();
    }

    /**
     * Ładowanie początkowych danych
     */
    async loadInitialData() {
        try {
            await Promise.all([
                this.refreshStatus(),
                this.loadSystemInfo(),
                this.loadDatabaseInfo()
            ]);
        } catch (error) {
            console.error('Błąd ładowania danych:', error);
        }
    }

    /**
     * Odświeżanie statusu API
     */
    async refreshStatus() {
        if (this.statusChecking) return;
        
        this.statusChecking = true;
        const refreshBtn = document.getElementById('refresh-status-btn');
        const originalText = refreshBtn?.textContent;
        
        try {
            // Pokaż loading
            if (refreshBtn) {
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<span class="loading-spinner"></span> Sprawdzanie...';
            }

            this.updateStatusIndicators('testing');

            const response = await this.makeRequest('claude_chat_check_status', {});
            
            if (response.success) {
                this.updateAPIStatus(response.data);
                this.showNotification('Status API zaktualizowany', 'success');
            } else {
                throw new Error(response.data?.message || 'Błąd sprawdzania statusu');
            }
        } catch (error) {
            console.error('Błąd odświeżania statusu:', error);
            this.showNotification('Błąd sprawdzania statusu: ' + error.message, 'error');
            this.updateStatusIndicators('error');
        } finally {
            this.statusChecking = false;
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.textContent = originalText;
            }
        }
    }

    /**
     * Aktualizacja statusu API w interfejsie
     */
    updateAPIStatus(data) {
        const apiCards = document.querySelectorAll('.status-card[data-api]');
        
        apiCards.forEach(card => {
            const apiType = card.dataset.api;
            const status = data.api?.[apiType];
            
            if (status) {
                const indicator = card.querySelector('.status-indicator');
                const message = card.querySelector('.status-message');
                const cardElement = card;
                
                // Aktualizuj wskaźnik
                indicator.className = `status-indicator ${status.status ? 'success' : 'error'}`;
                indicator.innerHTML = status.status ? '✅' : '❌';
                
                // Aktualizuj wiadomość
                if (message) {
                    message.textContent = status.message;
                }
                
                // Aktualizuj klasę karty
                cardElement.className = `status-card ${status.status ? 'success' : 'error'}`;
                
                // Animacja
                this.animateStatusUpdate(cardElement);
            }
        });
    }

    /**
     * Aktualizacja wskaźników statusu
     */
    updateStatusIndicators(status) {
        const indicators = document.querySelectorAll('.status-indicator');
        indicators.forEach(indicator => {
            indicator.className = `status-indicator ${status}`;
            
            switch (status) {
                case 'testing':
                    indicator.innerHTML = '⏳';
                    break;
                case 'success':
                    indicator.innerHTML = '✅';
                    break;
                case 'error':
                    indicator.innerHTML = '❌';
                    break;
            }
        });
    }

    /**
     * Naprawa tabel bazy danych
     */
    async repairTables() {
        if (!confirm('Czy na pewno chcesz naprawić tabele bazy danych? Ta operacja może potrwać kilka minut.')) {
            return;
        }

        const repairBtn = document.getElementById('repair-tables-btn');
        const originalText = repairBtn?.textContent;
        
        try {
            if (repairBtn) {
                repairBtn.disabled = true;
                repairBtn.innerHTML = '<span class="loading-spinner"></span> Naprawianie...';
            }

            this.showProgressBar('repair-progress', 0);

            const response = await this.makeRequest('claude_chat_repair_tables', {});
            
            if (response.success) {
                this.showProgressBar('repair-progress', 100);
                this.showNotification('Tabele zostały naprawione pomyślnie', 'success');
                
                // Odśwież informacje o bazie danych
                setTimeout(() => this.loadDatabaseInfo(), 1000);
            } else {
                throw new Error(response.data?.message || 'Błąd naprawy tabel');
            }
        } catch (error) {
            console.error('Błąd naprawy tabel:', error);
            this.showNotification('Błąd naprawy tabel: ' + error.message, 'error');
        } finally {
            if (repairBtn) {
                repairBtn.disabled = false;
                repairBtn.textContent = originalText;
            }
            setTimeout(() => this.hideProgressBar('repair-progress'), 2000);
        }
    }

    /**
     * Export danych
     */
    async exportData(button) {
        const format = button.dataset.format || 'csv';
        const table = button.dataset.table || 'all';
        const exportBtn = button;
        const originalText = exportBtn.textContent;
        
        try {
            exportBtn.disabled = true;
            exportBtn.innerHTML = '<span class="loading-spinner"></span> Eksportowanie...';

            this.showProgressBar('export-progress', 0);

            const response = await this.makeRequest('claude_chat_export_data', {
                format: format,
                table: table
            });
            
            if (response.success) {
                this.showProgressBar('export-progress', 100);
                
                // Pobierz plik
                this.downloadFile(response.data.content, response.data.filename, response.data.mime_type);
                
                this.showNotification('Dane zostały wyeksportowane', 'success');
            } else {
                throw new Error(response.data?.message || 'Błąd eksportu danych');
            }
        } catch (error) {
            console.error('Błąd eksportu:', error);
            this.showNotification('Błąd eksportu: ' + error.message, 'error');
        } finally {
            exportBtn.disabled = false;
            exportBtn.textContent = originalText;
            setTimeout(() => this.hideProgressBar('export-progress'), 2000);
        }
    }

    /**
     * Czyszczenie cache
     */
    async clearCache() {
        if (!confirm('Czy na pewno chcesz wyczyścić cache? To może wpłynąć na wydajność przez chwilę.')) {
            return;
        }

        try {
            const response = await this.makeRequest('claude_chat_clear_cache', {});
            
            if (response.success) {
                this.showNotification('Cache został wyczyszczony', 'success');
            } else {
                throw new Error(response.data?.message || 'Błąd czyszczenia cache');
            }
        } catch (error) {
            console.error('Błąd czyszczenia cache:', error);
            this.showNotification('Błąd czyszczenia cache: ' + error.message, 'error');
        }
    }

    /**
     * Test konkretnego API
     */
    async testSpecificAPI(apiType) {
        const card = document.querySelector(`[data-api="${apiType}"]`);
        if (!card) return;

        const indicator = card.querySelector('.status-indicator');
        const originalClass = indicator.className;
        
        try {
            indicator.className = 'status-indicator testing';
            indicator.innerHTML = '⏳';

            const response = await this.makeRequest('claude_chat_test_specific_api', {
                api_type: apiType
            });
            
            if (response.success) {
                const status = response.data.status;
                indicator.className = `status-indicator ${status ? 'success' : 'error'}`;
                indicator.innerHTML = status ? '✅' : '❌';
                
                const message = card.querySelector('.status-message');
                if (message) {
                    message.textContent = response.data.message;
                }
                
                this.animateStatusUpdate(card);
                this.showNotification(`Test ${apiType} zakończony`, status ? 'success' : 'warning');
            } else {
                throw new Error(response.data?.message || 'Błąd testu API');
            }
        } catch (error) {
            console.error('Błąd testu API:', error);
            indicator.className = 'status-indicator error';
            indicator.innerHTML = '❌';
            this.showNotification('Błąd testu API: ' + error.message, 'error');
        }
    }
 
    /**
     * Ładowanie informacji systemowych
     */
    async loadSystemInfo() {
        try {
            const response = await this.makeRequest('claude_chat_get_system_info', {});
            
            if (response.success) {
                this.updateSystemInfo(response.data);
            }
        } catch (error) {
            console.error('Błąd ładowania informacji systemowych:', error);
        }
    }
 
    /**
     * Ładowanie informacji o bazie danych
     */
    async loadDatabaseInfo() {
        try {
            const response = await this.makeRequest('claude_chat_get_database_info', {});
            
            if (response.success) {
                this.updateDatabaseInfo(response.data);
            }
        } catch (error) {
            console.error('Błąd ładowania informacji o bazie danych:', error);
        }
    }
 
    /**
     * Aktualizacja informacji systemowych w UI
     */
    updateSystemInfo(data) {
        Object.entries(data).forEach(([key, value]) => {
            const element = document.querySelector(`[data-system-info="${key}"]`);
            if (element) {
                element.textContent = value.current || value.label;
                
                // Aktualizuj status
                const statusElement = element.closest('.info-card');
                if (statusElement && typeof value.status !== 'undefined') {
                    statusElement.className = `info-card ${value.status ? 'success' : 'warning'}`;
                }
            }
        });
    }
 
    /**
     * Aktualizacja informacji o bazie danych
     */
    updateDatabaseInfo(data) {
        const tableRows = document.querySelectorAll('[data-table-name]');
        
        tableRows.forEach(row => {
            const tableName = row.dataset.tableName;
            const tableData = data[tableName];
            
            if (tableData) {
                const statusCell = row.querySelector('.table-status');
                const rowsCell = row.querySelector('.table-rows');
                
                if (statusCell) {
                    statusCell.innerHTML = `
                        <span class="status-badge ${tableData.status ? 'success' : 'error'}">
                            ${tableData.status ? '✅ OK' : '❌ Błąd'}
                        </span>
                    `;
                }
                
                if (rowsCell) {
                    rowsCell.textContent = this.formatNumber(tableData.rows);
                }
            }
        });
    }
 
    /**
     * Rozpoczęcie auto-odświeżania
     */
    startAutoRefresh() {
        this.stopAutoRefresh(); // Zatrzymaj istniejący interval
        
        this.refreshInterval = setInterval(() => {
            if (!this.statusChecking) {
                this.refreshStatus();
            }
        }, 30000); // Co 30 sekund
        
        this.showNotification('Auto-odświeżanie włączone', 'info');
    }
 
    /**
     * Zatrzymanie auto-odświeżania
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
            this.showNotification('Auto-odświeżanie wyłączone', 'info');
        }
    }
 
    /**
     * Animacja aktualizacji statusu
     */
    animateStatusUpdate(element) {
        element.style.transform = 'scale(1.05)';
        element.style.transition = 'transform 0.3s ease';
        
        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 300);
    }
 
    /**
     * Animacja liczników
     */
    animateCounters() {
        const counters = document.querySelectorAll('[data-counter]');
        
        counters.forEach(counter => {
            const target = parseInt(counter.dataset.counter);
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = Math.floor(current);
            }, 16);
        });
    }
 
    /**
     * Pokazanie paska postępu
     */
    showProgressBar(id, progress) {
        const container = document.getElementById(id);
        if (!container) {
            // Stwórz pasek postępu dynamicznie
            const progressContainer = document.createElement('div');
            progressContainer.id = id;
            progressContainer.className = 'progress-container';
            progressContainer.innerHTML = '<div class="progress-bar"></div>';
            
            // Znajdź miejsce do wstawienia
            const targetSection = document.querySelector('.diagnostic-section');
            if (targetSection) {
                targetSection.appendChild(progressContainer);
            }
        }
        
        const progressBar = document.querySelector(`#${id} .progress-bar`);
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
    }
 
    /**
     * Ukrycie paska postępu
     */
    hideProgressBar(id) {
        const container = document.getElementById(id);
        if (container) {
            container.style.opacity = '0';
            setTimeout(() => {
                container.remove();
            }, 300);
        }
    }
 
    /**
     * Pobieranie pliku
     */
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
 
    /**
     * Formatowanie liczb
     */
    formatNumber(num) {
        return new Intl.NumberFormat('pl-PL').format(num);
    }
 
    /**
     * Fokus na sekcji eksportu
     */
    focusExportSection() {
        const exportSection = document.querySelector('.export-section');
        if (exportSection) {
            exportSection.scrollIntoView({ behavior: 'smooth' });
            exportSection.classList.add('highlight');
            setTimeout(() => {
                exportSection.classList.remove('highlight');
            }, 2000);
        }
    }
 
    /**
     * Aktualizacja opcji eksportu
     */
    updateExportOptions(select) {
        const format = select.value;
        const optionsContainer = select.closest('.export-controls').querySelector('.export-options');
        
        if (optionsContainer) {
            // Pokaż/ukryj dodatkowe opcje w zależności od formatu
            if (format === 'sql') {
                optionsContainer.innerHTML = `
                    <label>
                        <input type="checkbox" name="include_structure" checked> 
                        Dołącz strukturę tabel
                    </label>
                    <label>
                        <input type="checkbox" name="include_data" checked> 
                        Dołącz dane
                    </label>
                `;
            } else if (format === 'csv') {
                optionsContainer.innerHTML = `
                    <label>
                        <input type="checkbox" name="include_headers" checked> 
                        Dołącz nagłówki
                    </label>
                `;
            } else {
                optionsContainer.innerHTML = '';
            }
        }
    }
 
    /**
     * Pokazanie powiadomienia
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Auto-usuwanie po 5 sekundach
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
        
        // Możliwość zamknięcia przez kliknięcie
        notification.addEventListener('click', () => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
    }
 
    /**
     * Wykonanie żądania AJAX
     */
    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', claudeChatPro.nonce);
        
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });
        
        const response = await fetch(claudeChatPro.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
 
    /**
     * Czyszczenie przy zniszczeniu obiektu
     */
    destroy() {
        this.stopAutoRefresh();
        this.isInitialized = false;
    }
 }
 
 // Inicjalizacja po załadowaniu DOM
 document.addEventListener('DOMContentLoaded', () => {
    window.diagnosticsManager = new DiagnosticsManager();
 });
 
 // Czyszczenie przy opuszczeniu strony
 window.addEventListener('beforeunload', () => {
    if (window.diagnosticsManager) {
        window.diagnosticsManager.destroy();
    }
 });   