// Chat History - Nowoczesny JavaScript
jQuery(document).ready(function($) {
    // Inicjalizacja komponentów
    initSearch();
    initDateFilters();
    initExpandableContent();
    initActions();
    initDarkMode();

    // Funkcja inicjalizująca wyszukiwarkę
    function initSearch() {
        const $searchInput = $('.chat-history-search input');
        let searchTimeout;

        $searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val().toLowerCase();

            searchTimeout = setTimeout(() => {
                $('.chat-history-item').each(function() {
                    const $item = $(this);
                    const content = $item.find('.chat-history-item-content').text().toLowerCase();
                    const title = $item.find('.chat-history-item-title').text().toLowerCase();

                    if (content.includes(searchTerm) || title.includes(searchTerm)) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                });
            }, 300);
        });
    }

    // Funkcja inicjalizująca filtry dat
    function initDateFilters() {
        const $startDate = $('#start-date');
        const $endDate = $('#end-date');

        function filterByDate() {
            const startDate = new Date($startDate.val());
            const endDate = new Date($endDate.val());

            $('.chat-history-item').each(function() {
                const $item = $(this);
                const itemDate = new Date($item.data('date'));

                if (itemDate >= startDate && itemDate <= endDate) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        }

        $startDate.on('change', filterByDate);
        $endDate.on('change', filterByDate);
    }

    // Funkcja inicjalizująca rozwijane treści
    function initExpandableContent() {
        $('.chat-history-item-content').each(function() {
            const $content = $(this);
            const contentHeight = $content[0].scrollHeight;

            if (contentHeight > 200) {
                $content.after(`
                    <button class="expand-content" type="button">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                        Rozwiń
                    </button>
                `);
            }
        });

        $(document).on('click', '.expand-content', function() {
            const $button = $(this);
            const $content = $button.prev('.chat-history-item-content');
            const isExpanded = $content.hasClass('expanded');

            if (isExpanded) {
                $content.removeClass('expanded');
                $button.html('<span class="dashicons dashicons-arrow-down-alt2"></span>Rozwiń');
            } else {
                $content.addClass('expanded');
                $button.html('<span class="dashicons dashicons-arrow-up-alt2"></span>Zwiń');
            }
        });
    }

    // Funkcja inicjalizująca akcje (podgląd, usuwanie)
    function initActions() {
        // Podgląd historii
        $(document).on('click', '.view-button', function() {
            const $item = $(this).closest('.chat-history-item');
            const chatId = $item.data('chat-id');
            
            // Tutaj możesz dodać logikę podglądu historii
            console.log('Podgląd historii:', chatId);
        });

        // Usuwanie historii
        $(document).on('click', '.delete-button', function() {
            const $item = $(this).closest('.chat-history-item');
            const chatId = $item.data('chat-id');

            if (confirm('Czy na pewno chcesz usunąć tę historię?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'claude_delete_chat_history',
                        chat_id: chatId,
                        nonce: window.claudeChat.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $item.fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            alert('Wystąpił błąd podczas usuwania historii.');
                        }
                    },
                    error: function() {
                        alert('Wystąpił błąd podczas usuwania historii.');
                    }
                });
            }
        });
    }

    // Funkcja inicjalizująca tryb ciemny
    function initDarkMode() {
        const $darkModeToggle = $('#dark-mode-toggle');
        
        // Sprawdź zapisane preferencje
        const isDarkMode = localStorage.getItem('claude_dark_mode') === 'true';
        if (isDarkMode) {
            $('body').addClass('dark-mode');
            $darkModeToggle.prop('checked', true);
        }

        // Obsługa przełącznika
        $darkModeToggle.on('change', function() {
            const isDark = $(this).prop('checked');
            $('body').toggleClass('dark-mode', isDark);
            localStorage.setItem('claude_dark_mode', isDark);
        });
    }

    // Animacje i efekty
    $('.chat-history-item').each(function(index) {
        $(this).css({
            'animation-delay': (index * 0.1) + 's'
        });
    });
}); 